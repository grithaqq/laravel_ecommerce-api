<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_checkout_successfully()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $token = auth('api')->login($customer);

        $product = Product::create([
            'name' => 'Laptop',
            'description' => 'Gaming Laptop',
            'price' => 10000000,
            'stock' => 5
        ]);

        $response = $this->withToken($token)->postJson('/api/orders', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2
                ]
            ]
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['total_amount' => 20000000]);

        // Check if stock is deducted
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 3
        ]);

        // Check if order and order_items are created
        $this->assertDatabaseHas('orders', [
            'user_id' => $customer->id,
            'total_amount' => 20000000
        ]);
        
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2
        ]);
    }

    public function test_checkout_fails_if_stock_insufficient()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $token = auth('api')->login($customer);

        $product = Product::create([
            'name' => 'Mouse',
            'description' => 'Wireless Mouse',
            'price' => 100000,
            'stock' => 1
        ]);

        $response = $this->withToken($token)->postJson('/api/orders', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5 // Trying to buy 5, but stock is 1
                ]
            ]
        ]);

        $response->assertStatus(400);

        // Check if stock remains intact (Transaction Rollback)
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 1
        ]);
        
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_customer_can_view_own_orders()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $token = auth('api')->login($customer);

        Order::create([
            'user_id' => $customer->id,
            'order_date' => now(),
            'total_amount' => 5000,
            'status' => 'pending'
        ]);

        $response = $this->withToken($token)->getJson('/api/orders');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    public function test_customer_cannot_view_others_order()
    {
        $customerA = User::factory()->create(['role' => 'customer']);
        $customerB = User::factory()->create(['role' => 'customer']);
        
        $orderB = Order::create([
            'user_id' => $customerB->id,
            'order_date' => now(),
            'total_amount' => 5000,
            'status' => 'pending'
        ]);

        $tokenA = auth('api')->login($customerA);

        $response = $this->withToken($tokenA)->getJson('/api/orders/' . $orderB->id);

        $response->assertStatus(403);
    }

    public function test_customer_can_pay_order_and_admin_can_ship()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        
        $order = Order::create([
            'user_id' => $customer->id,
            'order_date' => now(),
            'total_amount' => 5000,
            'status' => 'pending'
        ]);

        // Customer pays
        $tokenCustomer = auth('api')->login($customer);
        $responsePay = $this->withToken($tokenCustomer)->putJson('/api/orders/' . $order->id . '/status', [
            'status' => 'paid'
        ]);
        $responsePay->assertStatus(200);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);

        // Admin ships
        $tokenAdmin = auth('api')->login($admin);
        $responseShip = $this->withToken($tokenAdmin)->putJson('/api/orders/' . $order->id . '/status', [
            'status' => 'shipped'
        ]);
        $responseShip->assertStatus(200);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'shipped']);
    }
}
