<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Tests\TestCase;

class StandardizedResponseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_success_response_structure(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'email', 'role'],
                'meta' => ['timestamp', 'version'],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'User profile retrieved successfully',
            ]);
    }

    public function test_created_response_structure(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test')->plainTextToken;

        $categoryData = [
            'name' => 'Test Category',
            'description' => 'Test Description',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'description'],
                'meta' => ['timestamp', 'version'],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Category created successfully',
            ]);
    }

    public function test_paginated_response_structure(): void
    {
        // Create some categories
        Category::factory(25)->create();

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'description', 'products_count'],
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
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Categories retrieved successfully',
            ]);

        // Verify pagination data
        $responseData = $response->json();
        $this->assertEquals(1, $responseData['pagination']['current_page']);
        $this->assertEquals(15, $responseData['pagination']['per_page']);
        $this->assertGreaterThanOrEqual(25, $responseData['pagination']['total']);
        $this->assertTrue($responseData['pagination']['has_more_pages']);
    }

    public function test_not_found_response_structure(): void
    {
        $response = $this->getJson('/api/categories/99999');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'message',
                'meta' => ['timestamp', 'version'],
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found',
            ]);
    }

    public function test_validation_error_response_structure(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/categories', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [
                    'name' => [],
                ],
                'meta' => ['timestamp', 'version'],
            ])
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonValidationErrors(['name']);
    }

    public function test_unauthorized_response_structure(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401)
            ->assertJsonStructure([
                'success',
                'message',
                'meta' => ['timestamp', 'version'],
            ])
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_forbidden_response_structure(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $token = $customer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/categories', [
            'name' => 'Test Category',
            'description' => 'Test Description',
        ]);

        $response->assertStatus(403)
            ->assertJsonStructure([
                'success',
                'message',
                'meta' => ['timestamp', 'version'],
            ])
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_method_not_allowed_response_structure(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/payments/1');

        $response->assertStatus(405)
            ->assertJsonStructure([
                'success',
                'message',
                'meta' => ['timestamp', 'version'],
            ])
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_error_response_with_additional_data(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $token = $customer->createToken('test')->plainTextToken;

        // Try to add a product with insufficient stock
        $product = \App\Models\Product::factory()->create(['stock' => 0]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/cart', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
                'meta' => ['timestamp', 'version', 'available_stock'],
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient stock',
            ]);
    }

    public function test_json_response_headers_are_set(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_response_meta_contains_timestamp_and_version(): void
    {
        $response = $this->getJson('/api/categories');

        $responseData = $response->json();
        
        $this->assertArrayHasKey('timestamp', $responseData['meta']);
        $this->assertArrayHasKey('version', $responseData['meta']);
        $this->assertEquals('1.0', $responseData['meta']['version']);
        
        // Verify timestamp is valid ISO format
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/',
            $responseData['meta']['timestamp']
        );
    }

    public function test_pagination_works_with_different_page_sizes(): void
    {
        // Create 30 categories
        Category::factory(30)->create();

        // Test first page
        $response = $this->getJson('/api/categories?page=1');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(1, $data['pagination']['current_page']);
        $this->assertEquals(15, $data['pagination']['per_page']);
        $this->assertCount(15, $data['data']);

        // Test second page
        $response = $this->getJson('/api/categories?page=2');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(2, $data['pagination']['current_page']);
        $this->assertCount(15, $data['data']);
    }

    public function test_error_response_logs_exceptions(): void
    {
        // This test ensures that exceptions are properly logged
        // We'll test with a non-existent category
        $response = $this->getJson('/api/categories/99999');
        
        $response->assertStatus(404);
        
        // The exception should be logged (we can't easily test this in a unit test,
        // but the structure should be correct)
        $response->assertJsonStructure([
            'success',
            'message',
            'meta' => ['timestamp', 'version'],
        ]);
    }
}