<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $query = Product::with('category');

        // Apply filters using the CommonQueryScopes trait
        $query->filterByCategory(request()->input('category_id'))
              ->filterByPrice(request()->input('min_price'), request()->input('max_price'))
              ->searchByName(request()->input('search'));

        // Cache the results for 15 minutes
        $cacheKey = 'products_' . md5(serialize(request()->all()));
        $products = Cache::remember($cacheKey, 900, fn() => $query->get());

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'data' => [
                'products' => ProductResource::collection($products),
                'total_count' => $products->count(),
                'filters_applied' => [
                    'category_id' => request()->input('category_id'),
                    'min_price' => request()->input('min_price'),
                    'max_price' => request()->input('max_price'),
                    'search' => request()->input('search'),
                ],
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => '1.0',
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        // Clear products cache
        Cache::forget('products_*');

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => [
                'product' => new ProductResource($product->load('category')),
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => '1.0',
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::with('category')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully',
            'data' => [
                'product' => new ProductResource($product),
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => '1.0',
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->update($request->validated());

        // Clear products cache
        Cache::forget('products_*');

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => [
                'product' => new ProductResource($product->load('category')),
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => '1.0',
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        // Clear products cache
        Cache::forget('products_*');

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
            'data' => null,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => '1.0',
            ],
        ]);
    }
}