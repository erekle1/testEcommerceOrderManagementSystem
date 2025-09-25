<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with('category');

        // Apply filters using the CommonQueryScopes trait
        $query->filterByCategory($request->input('category_id'))
              ->filterByPrice($request->input('min_price'), $request->input('max_price'))
              ->searchByName($request->input('search'));

        // Cache the results for 15 minutes
        $cacheKey = 'products_' . md5(serialize($request->all()));
        $products = Cache::remember($cacheKey, 900, function () use ($query) {
            return $query->get();
        });

        return response()->json([
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
        ]);

        $product = Product::create($request->all());

        // Clear products cache
        Cache::forget('products_*');

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with('category')->findOrFail($id);

        return response()->json([
            'product' => $product,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
        ]);

        $product->update($request->all());

        // Clear products cache
        Cache::forget('products_*');

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        // Clear products cache
        Cache::forget('products_*');

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }
}
