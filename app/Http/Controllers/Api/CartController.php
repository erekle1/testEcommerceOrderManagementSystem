<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\StoreCartRequest;
use App\Http\Requests\Cart\UpdateCartRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $cartItems = Cart::with('product.category')
            ->where('user_id', request()->user()->id)
            ->get();

        $total = $cartItems->sum('total_price');

        return response()->json([
            'success' => true,
            'message' => 'Cart retrieved successfully',
            'data' => [
                'cart_items' => CartResource::collection($cartItems),
                'total' => (float) $total,
                'items_count' => $cartItems->count(),
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
    public function store(StoreCartRequest $request): JsonResponse
    {
        $product = Product::findOrFail($request->validated('product_id'));

        if (!$product->isInStock($request->validated('quantity'))) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock',
                'errors' => [
                    'stock' => 'The requested quantity exceeds available stock',
                    'available_stock' => $product->stock,
                ],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'version' => '1.0',
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
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

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart successfully',
            'data' => [
                'cart_item' => new CartResource($cartItem->load('product')),
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
        $cartItem = Cart::with('product.category')
            ->where('user_id', request()->user()->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Cart item retrieved successfully',
            'data' => [
                'cart_item' => new CartResource($cartItem),
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
    public function update(UpdateCartRequest $request, string $id): JsonResponse
    {
        $cartItem = Cart::where('user_id', request()->user()->id)
            ->findOrFail($id);

        $product = $cartItem->product;

        if (!$product->isInStock($request->validated('quantity'))) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock',
                'errors' => [
                    'stock' => 'The requested quantity exceeds available stock',
                    'available_stock' => $product->stock,
                ],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'version' => '1.0',
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cartItem->update(['quantity' => $request->validated('quantity')]);

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated successfully',
            'data' => [
                'cart_item' => new CartResource($cartItem->load('product')),
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
        $cartItem = Cart::where('user_id', request()->user()->id)
            ->findOrFail($id);

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart item removed successfully',
            'data' => null,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => '1.0',
            ],
        ]);
    }
}