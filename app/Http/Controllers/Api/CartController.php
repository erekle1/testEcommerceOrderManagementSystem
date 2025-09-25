<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Cart\StoreCartRequest;
use App\Http\Requests\Cart\UpdateCartRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class CartController extends BaseApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $cartItems = Cart::with('product.category')
            ->where('user_id', request()->user()->id)
            ->paginate(15);

        $total = $cartItems->sum('total_price');

        return $this->paginatedResponse(
            CartResource::collection($cartItems),
            'Cart retrieved successfully',
            additional: [
                'total' => (float) $total,
                'items_count' => $cartItems->count(),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCartRequest $request): JsonResponse
    {
        $product = Product::findOrFail($request->validated('product_id'));

        if (!$product->isInStock($request->validated('quantity'))) {
            return $this->errorResponse(
                'Insufficient stock',
                ['stock' => 'The requested quantity exceeds available stock'],
                additional: ['available_stock' => $product->stock]
            );
        }

        // Check if item already exists in cart
        $existingCartItem = Cart::where('user_id', request()->user()->id)
            ->where('product_id', $request->validated('product_id'))
            ->first();

        if ($existingCartItem) {
            $existingCartItem->increment('quantity', $request->validated('quantity'));
            $cartItem = $existingCartItem;
        } else {
            $cartItem = Cart::create([
                'user_id' => request()->user()->id,
                'product_id' => $request->validated('product_id'),
                'quantity' => $request->validated('quantity'),
            ]);
        }

        return $this->createdResponse(
            CartResource::make($cartItem->load('product')),
            'Product added to cart successfully'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $cartItem = Cart::with('product.category')
            ->where('user_id', request()->user()->id)
            ->findOrFail($id);

        return $this->resourceResponse(
            CartResource::make($cartItem),
            'Cart item retrieved successfully'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCartRequest $request, string $id): JsonResponse
    {
        $cartItem = Cart::where('user_id', request()->user()->id)
            ->findOrFail($id);

        $product = $cartItem->product;

        if (!$product->isInStock($request->validated('quantity'))) {
            return $this->errorResponse(
                'Insufficient stock',
                ['stock' => 'The requested quantity exceeds available stock'],
                additional: ['available_stock' => $product->stock]
            );
        }

        $cartItem->update(['quantity' => $request->validated('quantity')]);

        return $this->resourceResponse(
            CartResource::make($cartItem->load('product')),
            'Cart item updated successfully'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $cartItem = Cart::where('user_id', request()->user()->id)
            ->findOrFail($id);

        $cartItem->delete();

        return $this->successResponse(
            null,
            'Cart item removed successfully'
        );
    }
}