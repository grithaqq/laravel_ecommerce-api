<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Helpers\ApiFormatter;

class ProductController extends Controller
{
    // Menampilkan semua data
    public function index()
    {
        $products = Product::all();

        return ApiFormatter::createJson(200, 'Daftar Produk', $products);
    }

    // Menyimpan data baru
    public function store(Request $request)
    {
        $user = auth('api')->user();
        if (!$user || $user->role !== 'admin') {
            return ApiFormatter::createJson(403, 'Forbidden', 'Hanya admin yang dapat menambah produk');
        }

        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'required|string',
            'price'       => 'required|integer',
            'stock'       => 'required|integer',
        ]);

        $product = Product::create($request->all());

        return ApiFormatter::createJson(201, 'Produk berhasil ditambahkan', $product);
    }

    // Menampilkan detail produk
    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return ApiFormatter::createJson(404, 'Not Found', 'Produk tidak ditemukan');
        }

        return ApiFormatter::createJson(200, 'Detail Produk', $product);
    }

    // Mengubah data produk
    public function update(Request $request, $id)
    {
        $user = auth('api')->user();
        if (!$user || $user->role !== 'admin') {
            return ApiFormatter::createJson(403, 'Forbidden', 'Hanya admin yang dapat mengubah produk');
        }

        $product = Product::find($id);

        if (!$product) {
            return ApiFormatter::createJson(404, 'Not Found', 'Produk tidak ditemukan');
        }

        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'required|string',
            'price'       => 'required|integer',
            'stock'       => 'required|integer',
        ]);

        $product->update($request->all());

        return ApiFormatter::createJson(200, 'Produk berhasil diperbarui', $product);
    }

    // Menghapus produk
    public function destroy($id)
    {
        $user = auth('api')->user();
        if (!$user || $user->role !== 'admin') {
            return ApiFormatter::createJson(403, 'Forbidden', 'Hanya admin yang dapat menghapus produk');
        }

        $product = Product::find($id);

        if (!$product) {
            return ApiFormatter::createJson(404, 'Not Found', 'Produk tidak ditemukan');
        }

        $product->delete();

        return ApiFormatter::createJson(200, 'Produk berhasil dihapus', null);
    }
}
