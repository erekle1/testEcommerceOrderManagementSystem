<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CartTest extends TestCase
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

    public function test_customer_can_view_cart(): void
    {
        Cart::factory()->create([
            'user_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        $token = $this->customer->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/cart');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'cart_items' => [
                        '*' => ['id', 'quantity', 'product']
                    ],
                    'total',
                    'items_count',
                ],
                'meta' => ['timestamp', 'version'],
            ]);
    }

    public function test_customer_can_add_product_to_cart(): void
    {
        $token = $this->customer->createToken('test-token')->plainTextToken;

        $cartData = [
            'product_id' => $this->product->id,
            'quantity' => 3,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/cart', $cartData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'cart_item' => ['id', 'quantity', 'product']
                ],
                'meta' => ['timestamp', 'version'],
            ]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'quantity' => 3,
        ]);
    }

    public function test_customer_can_update_cart_item(): void
    {
        $cartItem = Cart::factory()->create([
            'user_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        $token = $this->customer->createToken('test-token')->plainTextToken;

        $updateData = ['quantity' => 5];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/cart/{$cartItem->id}", $updateData);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('carts', [
            'id' => $cartItem->id,
            'quantity' => 5,
        ]);
    }

    public function test_customer_can_remove_cart_item(): void
    {
        $cartItem = Cart::factory()->create([
            'user_id' => $this->customer->id,
            'product_id' => $this->product->id,
        ]);

        $token = $this->customer->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/cart/{$cartItem->id}");

        $response->assertStatus(200);
        
        $this->assertDatabaseMissing('carts', ['id' => $cartItem->id]);
    }

    public function test_cannot_add_out_of_stock_product(): void
    {
        $outOfStockProduct = Product::factory()->create([
            'category_id' => $this->category->id,
            'stock' => 0,
        ]);

        $token = $this->customer->createToken('test-token')->plainTextToken;

        $cartData = [
            'product_id' => $outOfStockProduct->id,
            'quantity' => 1,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/cart', $cartData);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Insufficient stock']);
    }

    public function test_admin_cannot_access_cart(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/cart');

        $response->assertStatus(403);
    }

    public function test_cart_validation(): void
    {
        $token = $this->customer->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/cart', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id', 'quantity']);
    }
}
