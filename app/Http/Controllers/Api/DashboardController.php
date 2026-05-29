<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'today_revenue'  => Order::paid()->whereDate('paid_at', today())->sum('total'),
            'today_orders'   => Order::paid()->whereDate('paid_at', today())->count(),
            'total_products' => Product::active()->count(),
            'top_products'   => OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->where('orders.status', 'paid')
                ->whereDate('orders.paid_at', today())
                ->selectRaw('products.name, SUM(order_items.qty) as qty_sold, SUM(order_items.subtotal) as revenue')
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('qty_sold')
                ->limit(5)
                ->get(),
        ]);
    }
}