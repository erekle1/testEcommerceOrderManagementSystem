<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $orders = Order::with(['orderItems.product', 'payments'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'orders' => $orders,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cart_items' => 'required|array|min:1',
            'cart_items.*.product_id' => 'required|exists:products,id',
            'cart_items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $cartItems = $request->cart_items;
            $order = $this->orderService->createOrderFromCart($request->user()->id, $cartItems);

            // Clear cart after successful order
            Cart::where('user_id', $request->user()->id)->delete();

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order->load(['orderItems.product', 'payments']),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $order = Order::with(['orderItems.product', 'payments'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'order' => $order,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,shipped,delivered,cancelled',
        ]);

        $order = Order::findOrFail($id);
        
        if ($request->status === 'cancelled') {
            $success = $this->orderService->cancelOrder($order);
        } else {
            $success = $this->orderService->updateOrderStatus($order, $request->status);
        }

        if (!$success) {
            return response()->json([
                'message' => 'Invalid status transition',
            ], 422);
        }

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order->load(['orderItems.product', 'payments']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Orders are typically not deleted, only cancelled
        return response()->json([
            'message' => 'Orders cannot be deleted. Use cancel instead.',
        ], 405);
    }
}
