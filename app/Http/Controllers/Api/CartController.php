<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $cartItems = Cart::with('product.category')
            ->where('user_id', $request->user()->id)
            ->get();

        $total = $cartItems->sum('total_price');

        return response()->json([
            'cart_items' => $cartItems,
            'total' => $total,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        if (!$product->isInStock($request->quantity)) {
            return response()->json([
                'message' => 'Insufficient stock',
                'available_stock' => $product->stock,
            ], 422);
        }

        // Check if item already exists in cart
        $existingCartItem = Cart::where('user_id', $request->user()->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existingCartItem) {
            $existingCartItem->increment('quantity', $request->quantity);
            $cartItem = $existingCartItem;
        } else {
            $cartItem = Cart::create([
                'user_id' => $request->user()->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
            ]);
        }

        return response()->json([
            'message' => 'Product added to cart successfully',
            'cart_item' => $cartItem->load('product'),
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $cartItem = Cart::with('product.category')
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'cart_item' => $cartItem,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = Cart::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $product = $cartItem->product;

        if (!$product->isInStock($request->quantity)) {
            return response()->json([
                'message' => 'Insufficient stock',
                'available_stock' => $product->stock,
            ], 422);
        }

        $cartItem->update(['quantity' => $request->quantity]);

        return response()->json([
            'message' => 'Cart item updated successfully',
            'cart_item' => $cartItem->load('product'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $cartItem = Cart::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $cartItem->delete();

        return response()->json([
            'message' => 'Cart item removed successfully',
        ]);
    }
}
