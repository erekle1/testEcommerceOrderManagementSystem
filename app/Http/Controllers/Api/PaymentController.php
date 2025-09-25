<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

class PaymentController extends BaseApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $payments = Payment::with('order')
            ->whereHas('order', function ($query) {
                $query->where('user_id', request()->user()->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return $this->paginatedResponse(
            PaymentResource::collection($payments),
            'Payments retrieved successfully'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(string $orderId): JsonResponse
    {
        $order = Order::where('user_id', request()->user()->id)
            ->findOrFail($orderId);

        // Mock payment processing
        $paymentStatus = $this->processMockPayment($order);

        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => $order->total_amount,
            'status' => $paymentStatus,
        ]);

        // Update order status if payment is successful
        if ($paymentStatus === 'success') {
            $order->update(['status' => 'confirmed']);
        }

        return $this->createdResponse(
            PaymentResource::make($payment->load('order')),
            'Payment processed successfully'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $payment = Payment::with('order')
            ->whereHas('order', function ($query) {
                $query->where('user_id', request()->user()->id);
            })
            ->findOrFail($id);

        return $this->resourceResponse(
            PaymentResource::make($payment),
            'Payment retrieved successfully'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(string $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);
        
        // For demo purposes, we'll just return the current payment
        // In a real application, you might want to add validation for status updates
        
        return $this->resourceResponse(
            PaymentResource::make($payment),
            'Payment status retrieved successfully'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        return $this->methodNotAllowedResponse('Payments cannot be deleted');
    }

    /**
     * Mock payment processing.
     */
    private function processMockPayment(Order $order): string
    {
        // Simulate payment processing with 90% success rate
        return rand(1, 10) <= 9 ? 'success' : 'failed';
    }
}