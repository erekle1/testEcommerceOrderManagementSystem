<?php

namespace App\Services;

use App\Jobs\SendOrderConfirmationJob;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Calculate order total from cart items.
     */
    public function calculateOrderTotal(array $cartItems): float
    {
        $total = 0;

        foreach ($cartItems as $cartItem) {
            $product = Product::find($cartItem['product_id']);
            if ($product) {
                $total += $product->price * $cartItem['quantity'];
            }
        }

        return $total;
    }

    /**
     * Apply discount to order total.
     */
    public function applyDiscount(float $total, float $discountPercentage = 0): float
    {
        if ($discountPercentage > 0 && $discountPercentage <= 100) {
            return $total * (1 - $discountPercentage / 100);
        }

        return $total;
    }

    /**
     * Create order from cart items.
     */
    public function createOrderFromCart(int $userId, array $cartItems): Order
    {
        return DB::transaction(function () use ($userId, $cartItems) {
            // Validate stock before creating order
            foreach ($cartItems as $cartItem) {
                $product = Product::find($cartItem['product_id']);
                if (!$product || !$product->isInStock($cartItem['quantity'])) {
                    throw new \Exception('Insufficient stock for product: ' . ($product ? $product->name : 'Unknown'));
                }
            }

            $totalAmount = $this->calculateOrderTotal($cartItems);

            $order = Order::create([
                'user_id' => $userId,
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            foreach ($cartItems as $cartItem) {
                $product = Product::find($cartItem['product_id']);
                
                if ($product && $product->isInStock($cartItem['quantity'])) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $cartItem['quantity'],
                        'unit_price' => $product->price,
                    ]);

                    // Decrease stock
                    $product->decreaseStock($cartItem['quantity']);
                }
            }

            // Dispatch order confirmation job
            dispatch(new SendOrderConfirmationJob($order));

            return $order;
        });
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(Order $order, string $status): bool
    {
        $validStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $order->update(['status' => $status]);
        return true;
    }

    /**
     * Cancel order and restore stock.
     */
    public function cancelOrder(Order $order): bool
    {
        if (!$order->canBeCancelled()) {
            return false;
        }

        DB::transaction(function () use ($order) {
            // Restore stock for each order item
            foreach ($order->orderItems as $orderItem) {
                $orderItem->product->increaseStock($orderItem->quantity);
            }

            $order->update(['status' => 'cancelled']);
        });

        return true;
    }

    /**
     * Get order summary.
     */
    public function getOrderSummary(Order $order): array
    {
        return [
            'order_id' => $order->id,
            'user_name' => $order->user->name,
            'total_amount' => $order->total_amount,
            'status' => $order->status,
            'items_count' => $order->orderItems->count(),
            'created_at' => $order->created_at,
        ];
    }
}
