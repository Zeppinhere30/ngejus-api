<?php
// app/Http/Controllers/Api/OrderController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $q = Order::with(['customer', 'cashier', 'items.product'])
            ->orderByDesc('created_at');

        if ($request->status)     $q->where('status', $request->status);
        if ($request->date_from)  $q->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to)    $q->whereDate('created_at', '<=', $request->date_to);
        if ($request->search)     $q->where('order_number', 'like', "%{$request->search}%");

        return response()->json($q->paginate(20));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|integer|min:1',
            'payment_method'     => 'required|in:cash,qris,transfer,debit',
        ]);

        DB::beginTransaction();
        try {
            // Build order number: ORD-YYYYMMDD-XXXX
            $date   = now()->format('Ymd');
            $count  = Order::whereDate('created_at', today())->count() + 1;
            $number = 'ORD-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            $subtotal = 0;
            $itemsData = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $sub     = $product->price * $item['qty'];
                $subtotal += $sub;
                $itemsData[] = [
                    'product_id' => $product->id,
                    'qty'        => $item['qty'],
                    'unit_price' => $product->price,
                    'subtotal'   => $sub,
                    'notes'      => $item['notes'] ?? null,
                ];
            }

            $discount = $request->discount ?? 0;
            $tax      = round(($subtotal - $discount) * 0.00, 2); // 0% tax, adjust as needed
            $total    = $subtotal - $discount + $tax;

            $order = Order::create([
                'order_number'   => $number,
                'customer_id'    => $request->customer_id ?? 1,
                'cashier_id'     => auth()->id(),
                'status'         => 'pending',
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'tax'            => $tax,
                'total'          => $total,
                'payment_method' => $request->payment_method,
                'amount_paid'    => 0,
                'change_amount'  => 0,
                'notes'          => $request->notes,
            ]);

            foreach ($itemsData as $item) {
                $order->items()->create($item);
            }

            DB::commit();
            return response()->json([
                'message' => 'Order created',
                'data'    => $order->load(['items.product', 'customer']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function show(Order $order)
    {
        return response()->json($order->load(['items.product', 'customer', 'cashier']));
    }

    public function pay(Request $request, Order $order)
    {
        $request->validate([
            'amount_paid' => 'required|numeric|min:' . $order->total,
        ]);

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Order already processed'], 422);
        }

        DB::beginTransaction();
        try {
            $change = $request->amount_paid - $order->total;

            $order->update([
                'status'        => 'paid',
                'amount_paid'   => $request->amount_paid,
                'change_amount' => $change,
                'paid_at'       => now(),
            ]);

            // Deduct stock per recipe
            foreach ($order->items as $item) {
                foreach ($item->product->recipes as $recipe) {
                    $used = $recipe->qty_used * $item->qty;
                    $recipe->ingredient->decrement('stock', $used);
                    StockTransaction::create([
                        'ingredient_id' => $recipe->ingredient_id,
                        'type'          => 'out',
                        'qty'           => $used,
                        'note'          => 'Order ' . $order->order_number,
                        'reference_id'  => $order->id,
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'message'       => 'Payment successful',
                'change_amount' => $change,
                'data'          => $order->fresh(['items.product', 'customer']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function cancel(Order $order)
    {
        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Cannot cancel processed order'], 422);
        }
        $order->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Order cancelled']);
    }

    public function receipt(Order $order)
    {
        return response()->json($order->load(['items.product', 'customer', 'cashier']));
    }

    public function salesReport(Request $request)
    {
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to   = $request->date_to   ?? now()->toDateString();

        $summary = Order::paid()
            ->whereBetween(DB::raw('DATE(paid_at)'), [$from, $to])
            ->selectRaw('
                COUNT(*) as total_orders,
                SUM(total) as revenue,
                SUM(subtotal) as gross_sales,
                SUM(discount) as total_discount,
                AVG(total) as avg_order
            ')->first();

        $daily = Order::paid()
            ->whereBetween(DB::raw('DATE(paid_at)'), [$from, $to])
            ->selectRaw('DATE(paid_at) as date, COUNT(*) as orders, SUM(total) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topProducts = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.status', 'paid')
            ->whereBetween(DB::raw('DATE(orders.paid_at)'), [$from, $to])
            ->selectRaw('products.name, SUM(order_items.qty) as qty_sold, SUM(order_items.subtotal) as revenue')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('qty_sold')
            ->limit(10)
            ->get();

        return response()->json(compact('summary', 'daily', 'topProducts'));
    }
}
