<?php
// app/Http/Controllers/Api/IngredientController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IngredientController extends Controller
{
    public function index(Request $request)
    {
        $q = Ingredient::query();
        if ($request->search) $q->where('name', 'like', "%{$request->search}%");
        if ($request->low_stock) $q->whereRaw('stock <= min_stock');
        return response()->json($q->orderBy('name')->paginate(50));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'unit'          => 'required|string|max:30',
            'stock'         => 'required|numeric|min:0',
            'min_stock'     => 'required|numeric|min:0',
            'cost_per_unit' => 'required|numeric|min:0',
        ]);
        $ingredient = Ingredient::create($data);
        return response()->json($ingredient, 201);
    }

    public function show(Ingredient $ingredient)
    {
        return response()->json($ingredient->load('transactions'));
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $data = $request->validate([
            'name'          => 'sometimes|string|max:150',
            'unit'          => 'sometimes|string|max:30',
            'min_stock'     => 'sometimes|numeric|min:0',
            'cost_per_unit' => 'sometimes|numeric|min:0',
        ]);
        $ingredient->update($data);
        return response()->json($ingredient);
    }

    public function destroy(Ingredient $ingredient)
    {
        $ingredient->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function lowStock()
    {
        $items = Ingredient::whereRaw('stock <= min_stock')->get();
        return response()->json($items);
    }

    public function restock(Request $request, Ingredient $ingredient)
    {
        $request->validate(['qty' => 'required|numeric|min:0.001', 'note' => 'nullable|string']);

        DB::beginTransaction();
        try {
            $ingredient->increment('stock', $request->qty);
            StockTransaction::create([
                'ingredient_id' => $ingredient->id,
                'type'          => 'in',
                'qty'           => $request->qty,
                'note'          => $request->note ?? 'Restock manual',
                'created_at'    => now(),
            ]);
            DB::commit();
            return response()->json(['message' => 'Restocked', 'stock' => $ingredient->fresh()->stock]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
