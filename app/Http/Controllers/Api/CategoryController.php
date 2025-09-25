<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends BaseApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $categories = Category::withCount('products')
            ->paginate(15);
        
        return $this->paginatedResponse(
            CategoryResource::collection($categories),
            'Categories retrieved successfully',
            additional: ['total_count' => $categories->total()]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return $this->createdResponse(
            CategoryResource::make($category),
            'Category created successfully'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        return $this->handleExceptions(function () use ($id) {
            $category = Category::withCount('products')->findOrFail($id);
            return $this->resourceResponse(
                CategoryResource::make($category),
                'Category retrieved successfully'
            );
        }, 'Failed to retrieve category');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, string $id): JsonResponse
    {
        return $this->handleExceptions(function () use ($request, $id) {
            $category = Category::findOrFail($id);
            $category->update($request->validated());

            return $this->resourceResponse(
                CategoryResource::make($category->load('products')),
                'Category updated successfully'
            );
        }, 'Failed to update category');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        return $this->handleExceptions(function () use ($id) {
            $category = Category::findOrFail($id);
            $category->delete();

            return $this->successResponse(
                null,
                'Category deleted successfully'
            );
        }, 'Failed to delete category');
    }
}