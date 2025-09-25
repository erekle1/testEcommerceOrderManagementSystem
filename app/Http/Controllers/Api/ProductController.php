<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ProductController extends BaseApiController
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

        // Cache the results for 15 minutes with pagination
        $cacheKey = 'products_' . md5(serialize(request()->all()));
        $products = Cache::remember($cacheKey, 900, fn() => $query->paginate(15));

        return $this->paginatedResponse(
            ProductResource::collection($products),
            'Products retrieved successfully',
            additional: [
                'filters_applied' => [
                    'category_id' => request()->input('category_id'),
                    'min_price' => request()->input('min_price'),
                    'max_price' => request()->input('max_price'),
                    'search' => request()->input('search'),
                ],
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        // Clear products cache
        Cache::forget('products_*');

        return $this->createdResponse(
            ProductResource::make($product->load('category')),
            'Product created successfully'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::with('category')->findOrFail($id);

        return $this->resourceResponse(
            ProductResource::make($product),
            'Product retrieved successfully'
        );
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

        return $this->resourceResponse(
            ProductResource::make($product->load('category')),
            'Product updated successfully'
        );
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

        return $this->successResponse(
            null,
            'Product deleted successfully'
        );
    }
}