<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str; // Tambahkan ini untuk membuat slug otomatis
use Illuminate\Support\Facades\Storage; // Tambahkan ini untuk hapus/upload file

class ProductController extends Controller
{
    public function index() {
        return response()->json(Product::with('category')->get());
    }

    public function store(Request $request) {
        // 1. Validasi input
        $request->validate([
            'name'  => 'required',
            'price' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // Validasi file gambar maksimal 2MB
            // 'category_id' => 'required|exists:categories,id', // <-- DIMATIKAN SEMENTARA agar tidak error
        ]);

        // 2. Ambil semua data request kecuali file gambar
        $data = $request->except('image');

        // 3. Generate Slug otomatis dari nama produk
        $data['slug'] = Str::slug($request->name);

        // 4. Beri nilai default category_id jika dari Frontend belum ada dropdown kategori
        // Pastikan di database Anda minimal ada 1 kategori dengan ID 1
        $data['category_id'] = $request->category_id ?? 1;

        // 5. Proses upload gambar jika ada file yang dikirim
        if ($request->hasFile('image')) {
            // Simpan ke folder: storage/app/public/products
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = $path; // Masukkan path-nya ke database
        }

        // 6. Simpan ke database
        $product = Product::create($data);

        return response()->json($product, 201);
    }

    public function show(Product $product) {
        return response()->json($product->load(['category', 'recipes.ingredient']));
    }

    public function update(Request $request, Product $product) {
        // 1. Validasi (sometimes berarti hanya divalidasi kalau datanya dikirim)
        $request->validate([
            'name'  => 'sometimes|required',
            'price' => 'sometimes|required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data = $request->except('image');

        // Update slug jika namanya berubah
        if ($request->has('name')) {
            $data['slug'] = Str::slug($request->name);
        }

        // 2. Proses ganti gambar jika ada file baru yang dikirim
        if ($request->hasFile('image')) {
            // Hapus gambar lama dari storage jika ada
            if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
                Storage::disk('public')->delete($product->image_url);
            }

            // Simpan gambar baru
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = $path;
        }

        $product->update($data);
        return response()->json($product);
    }

    public function destroy(Product $product) {
        // Hapus file gambar dari folder sebelum menghapus data dari database
        if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
            Storage::disk('public')->delete($product->image_url);
        }

        $product->delete();
        return response()->json(['message' => 'Deleted']);
    }
}