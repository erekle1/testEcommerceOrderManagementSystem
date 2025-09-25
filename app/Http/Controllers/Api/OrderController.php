<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $orders = Order::with(['orderItems.product', 'payments'])
            ->where('user_id', request()->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully',
            'data' => [
                'orders' => OrderResource::collection($orders),
                'total_count' => $orders->count(),
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
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $cartItems = $request->validated('cart_items');
            $order = $this->orderService->createOrderFromCart(request()->user()->id, $cartItems);

            // Clear cart after successful order
            Cart::where('user_id', request()->user()->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'order' => new OrderResource($order->load(['orderItems.product', 'payments'])),
                ],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'version' => '1.0',
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [
                    'order' => $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'version' => '1.0',
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $order = Order::with(['orderItems.product', 'payments'])
            ->where('user_id', request()->user()->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully',
            'data' => [
                'order' => new OrderResource($order),
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
    public function update(UpdateOrderRequest $request, string $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        
        $status = $request->validated('status');
        
        $success = match ($status) {
            'cancelled' => $this->orderService->cancelOrder($order),
            default => $this->orderService->updateOrderStatus($order, $status),
        };

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status transition',
                'errors' => [
                    'status' => 'The order status cannot be changed to the requested status',
                ],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'version' => '1.0',
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => [
                'order' => new OrderResource($order->load(['orderItems.product', 'payments'])),
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
        // Orders are typically not deleted, only cancelled
        return response()->json([
            'success' => false,
            'message' => 'Orders cannot be deleted. Use cancel instead.',
            'errors' => [
                'method' => 'DELETE method is not allowed for orders',
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => '1.0',
            ],
        ], Response::HTTP_METHOD_NOT_ALLOWED);
    }
}