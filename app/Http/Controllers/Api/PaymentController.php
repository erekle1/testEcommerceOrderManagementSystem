<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $payments = Payment::with('order')
            ->whereHas('order', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'payments' => $payments,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $orderId)
    {
        $request->validate([
            'payment_method' => 'required|string|in:credit_card,paypal,bank_transfer',
        ]);

        $order = Order::where('user_id', $request->user()->id)
            ->findOrFail($orderId);

        // Mock payment processing
        $paymentStatus = $this->processMockPayment($order);

        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => $order->total_amount,
            'status' => $paymentStatus,
            'payment_method' => $request->payment_method,
            'transaction_id' => 'TXN_' . strtoupper(uniqid()),
        ]);

        // Update order status if payment is successful
        if ($paymentStatus === 'success') {
            $order->update(['status' => 'confirmed']);
        }

        return response()->json([
            'message' => 'Payment processed successfully',
            'payment' => $payment,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $payment = Payment::with('order')
            ->whereHas('order', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->findOrFail($id);

        return response()->json([
            'payment' => $payment,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:success,failed,refunded',
        ]);

        $payment = Payment::findOrFail($id);
        $payment->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Payment status updated successfully',
            'payment' => $payment,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return response()->json([
            'message' => 'Payments cannot be deleted',
        ], 405);
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
