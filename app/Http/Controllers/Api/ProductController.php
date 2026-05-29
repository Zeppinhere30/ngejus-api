<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index() {
        return response()->json(Product::with('category')->get());
    }

    public function store(Request $request) {
        $request->validate([
            'name'        => 'required',
            'category_id' => 'required|exists:categories,id',
            'price'       => 'required|numeric',
        ]);
        return response()->json(Product::create($request->all()), 201);
    }

    public function show(Product $product) {
        return response()->json($product->load(['category', 'recipes.ingredient']));
    }

    public function update(Request $request, Product $product) {
        $product->update($request->all());
        return response()->json($product);
    }

    public function destroy(Product $product) {
        $product->delete();
        return response()->json(['message' => 'Deleted']);
    }
}