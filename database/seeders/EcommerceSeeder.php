<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EcommerceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 2 admin users
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create 10 customer users
        User::factory(10)->create([
            'role' => 'customer',
        ]);

        // Create 5 categories
        $categories = [
            ['name' => 'Electronics', 'description' => 'Electronic devices and gadgets'],
            ['name' => 'Clothing', 'description' => 'Fashion and apparel'],
            ['name' => 'Books', 'description' => 'Books and educational materials'],
            ['name' => 'Home & Garden', 'description' => 'Home improvement and gardening'],
            ['name' => 'Sports', 'description' => 'Sports equipment and accessories'],
        ];

        foreach ($categories as $categoryData) {
            Category::create($categoryData);
        }

        // Create 20 products
        Product::factory(20)->create();

        // Create 10 cart items for different users
        $customers = User::where('role', 'customer')->take(5)->get();
        $products = Product::take(10)->get();

        foreach ($customers as $customer) {
            $randomProducts = $products->random(2);
            foreach ($randomProducts as $product) {
                Cart::create([
                    'user_id' => $customer->id,
                    'product_id' => $product->id,
                    'quantity' => rand(1, 3),
                ]);
            }
        }

        // Create 15 orders
        $customers = User::where('role', 'customer')->get();
        $products = Product::all();

        for ($i = 0; $i < 15; $i++) {
            $customer = $customers->random();
            $orderProducts = $products->random(rand(1, 4));
            
            $totalAmount = 0;
            $order = Order::create([
                'user_id' => $customer->id,
                'total_amount' => 0, // Will be calculated
                'status' => ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'][rand(0, 4)],
            ]);

            foreach ($orderProducts as $product) {
                $quantity = rand(1, 3);
                $unitPrice = $product->price;
                $totalAmount += $quantity * $unitPrice;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ]);
            }

            $order->update(['total_amount' => $totalAmount]);

            // Create payment for each order
            Payment::create([
                'order_id' => $order->id,
                'amount' => $totalAmount,
                'status' => ['success', 'failed'][rand(0, 1)],
            ]);
        }
    }
}
