<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->category = Category::factory()->create();
        $this->product = Product::factory()->create(['category_id' => $this->category->id, 'stock' => 10]);
    }

    public function test_customer_can_create_order(): void
    {
        $token = $this->customer->createToken('test-token')->plainTextToken;

        $orderData = [
            'cart_items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                ]
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/orders', $orderData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'total_amount', 'status', 'order_items'],
                'meta' => ['timestamp', 'version'],
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->customer->id,
            'status' => 'pending',
        ]);
    }

    public function test_customer_can_view_their_orders(): void
    {
        Order::factory()->create(['user_id' => $this->customer->id]);
        Order::factory()->create(['user_id' => $this->admin->id]); // Different user

        $token = $this->customer->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'total_amount', 'status', 'order_items']
                ],
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'from',
                    'to',
                    'has_more_pages',
                ],
                'meta' => ['timestamp', 'version'],
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_customer_can_view_specific_order(): void
    {
        $order = Order::factory()->create(['user_id' => $this->customer->id]);
        $token = $this->customer->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'total_amount', 'status', 'order_items'],
                'meta' => ['timestamp', 'version'],
            ]);
    }

    public function test_admin_can_update_order_status(): void
    {
        $order = Order::factory()->create(['user_id' => $this->customer->id, 'status' => 'pending']);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $updateData = ['status' => 'confirmed'];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/orders/{$order->id}/status", $updateData);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_customer_cannot_update_order_status(): void
    {
        $order = Order::factory()->create(['user_id' => $this->customer->id]);
        $token = $this->customer->createToken('test-token')->plainTextToken;

        $updateData = ['status' => 'confirmed'];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/orders/{$order->id}/status", $updateData);

        $response->assertStatus(403);
    }

    public function test_order_creation_validation(): void
    {
        $token = $this->customer->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cart_items']);
    }

    public function test_cannot_create_order_with_out_of_stock_product(): void
    {
        $outOfStockProduct = Product::factory()->create([
            'category_id' => $this->category->id,
            'stock' => 0,
        ]);

        $token = $this->customer->createToken('test-token')->plainTextToken;

        $orderData = [
            'cart_items' => [
                [
                    'product_id' => $outOfStockProduct->id,
                    'quantity' => 1,
                ]
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/orders', $orderData);

        $response->assertStatus(422);
    }
}
