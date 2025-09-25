<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $orderService;
    protected Category $category;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = new OrderService();
        $this->category = Category::factory()->create();
        $this->product = Product::factory()->create([
            'category_id' => $this->category->id,
            'price' => 100.00,
            'stock' => 10,
        ]);
    }

    public function test_calculate_order_total(): void
    {
        $cartItems = [
            ['product_id' => $this->product->id, 'quantity' => 2],
        ];

        $total = $this->orderService->calculateOrderTotal($cartItems);

        $this->assertEquals(200.00, $total);
    }

    public function test_apply_discount(): void
    {
        $total = 100.00;
        $discountPercentage = 10.0;

        $discountedTotal = $this->orderService->applyDiscount($total, $discountPercentage);

        $this->assertEquals(90.00, $discountedTotal);
    }

    public function test_apply_zero_discount(): void
    {
        $total = 100.00;

        $discountedTotal = $this->orderService->applyDiscount($total, 0);

        $this->assertEquals(100.00, $discountedTotal);
    }

    public function test_apply_invalid_discount(): void
    {
        $total = 100.00;
        $discountPercentage = 150.0; // Invalid discount > 100%

        $discountedTotal = $this->orderService->applyDiscount($total, $discountPercentage);

        $this->assertEquals(100.00, $discountedTotal);
    }

    public function test_update_order_status(): void
    {
        $order = \App\Models\Order::factory()->create(['status' => 'pending']);

        $result = $this->orderService->updateOrderStatus($order, 'confirmed');

        $this->assertTrue($result);
        $this->assertEquals('confirmed', $order->fresh()->status);
    }

    public function test_update_order_status_invalid(): void
    {
        $order = \App\Models\Order::factory()->create(['status' => 'pending']);

        $result = $this->orderService->updateOrderStatus($order, 'invalid_status');

        $this->assertFalse($result);
        $this->assertEquals('pending', $order->fresh()->status);
    }

    public function test_cancel_order(): void
    {
        $order = \App\Models\Order::factory()->create(['status' => 'pending']);
        \App\Models\OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        $originalStock = $this->product->stock;
        $result = $this->orderService->cancelOrder($order);

        $this->assertTrue($result);
        $this->assertEquals('cancelled', $order->fresh()->status);
        $this->assertEquals($originalStock + 2, $this->product->fresh()->stock);
    }

    public function test_cannot_cancel_shipped_order(): void
    {
        $order = \App\Models\Order::factory()->create(['status' => 'shipped']);

        $result = $this->orderService->cancelOrder($order);

        $this->assertFalse($result);
        $this->assertEquals('shipped', $order->fresh()->status);
    }

    public function test_get_order_summary(): void
    {
        $order = \App\Models\Order::factory()->create([
            'total_amount' => 250.00,
            'status' => 'confirmed',
        ]);

        $summary = $this->orderService->getOrderSummary($order);

        $this->assertArrayHasKey('order_id', $summary);
        $this->assertArrayHasKey('total_amount', $summary);
        $this->assertArrayHasKey('status', $summary);
        $this->assertEquals(250.00, $summary['total_amount']);
        $this->assertEquals('confirmed', $summary['status']);
    }
}
