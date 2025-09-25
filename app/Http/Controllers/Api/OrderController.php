<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends BaseApiController
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
            ->paginate(15);

        return $this->paginatedResponse(
            OrderResource::collection($orders),
            'Orders retrieved successfully'
        );
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

            return $this->createdResponse(
                OrderResource::make($order->load(['orderItems.product', 'payments'])),
                'Order created successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                ['order' => $e->getMessage()]
            );
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

        return $this->resourceResponse(
            OrderResource::make($order),
            'Order retrieved successfully'
        );
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
            return $this->errorResponse(
                'Invalid status transition',
                ['status' => 'The order status cannot be changed to the requested status']
            );
        }

        return $this->resourceResponse(
            OrderResource::make($order->load(['orderItems.product', 'payments'])),
            'Order status updated successfully'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        // Orders are typically not deleted, only cancelled
        return $this->methodNotAllowedResponse('Orders cannot be deleted. Use cancel instead.');
    }
}