<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index() {
        return response()->json(Category::with('products')->get());
    }

    public function store(Request $request) {
        $request->validate(['name' => 'required', 'slug' => 'required|unique:categories']);
        return response()->json(Category::create($request->all()), 201);
    }

    public function show(Category $category) {
        return response()->json($category->load('products'));
    }

    public function update(Request $request, Category $category) {
        $category->update($request->all());
        return response()->json($category);
    }

    public function destroy(Category $category) {
        $category->delete();
        return response()->json(['message' => 'Deleted']);
    }
}