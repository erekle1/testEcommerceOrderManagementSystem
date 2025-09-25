<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $categories = Category::with('products')->get();
        
        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully',
            'data' => [
                'categories' => CategoryResource::collection($categories),
                'total_count' => $categories->count(),
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
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => [
                'category' => new CategoryResource($category),
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
        $category = Category::with('products')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Category retrieved successfully',
            'data' => [
                'category' => new CategoryResource($category),
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
    public function update(UpdateCategoryRequest $request, string $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $category->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => [
                'category' => new CategoryResource($category->load('products')),
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
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
            'data' => null,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => '1.0',
            ],
        ]);
    }
}