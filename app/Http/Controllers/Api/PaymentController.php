<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PaymentController extends Controller
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
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Payments retrieved successfully',
            'data' => [
                'payments' => PaymentResource::collection($payments),
                'total_count' => $payments->count(),
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

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'data' => [
                'payment' => new PaymentResource($payment->load('order')),
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
        $payment = Payment::with('order')
            ->whereHas('order', function ($query) {
                $query->where('user_id', request()->user()->id);
            })
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Payment retrieved successfully',
            'data' => [
                'payment' => new PaymentResource($payment),
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
    public function update(string $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);
        
        // For demo purposes, we'll just return the current payment
        // In a real application, you might want to add validation for status updates
        
        return response()->json([
            'success' => true,
            'message' => 'Payment status retrieved successfully',
            'data' => [
                'payment' => new PaymentResource($payment),
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
        return response()->json([
            'success' => false,
            'message' => 'Payments cannot be deleted',
            'errors' => [
                'method' => 'DELETE method is not allowed for payments',
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => '1.0',
            ],
        ], Response::HTTP_METHOD_NOT_ALLOWED);
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