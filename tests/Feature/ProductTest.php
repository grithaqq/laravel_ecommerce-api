<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_view_product_list()
    {
        Product::create([
            'name' => 'Product 1',
            'description' => 'Desc 1',
            'price' => 10000,
            'stock' => 10
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Product 1']);
    }

    public function test_admin_can_create_product()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = auth('api')->login($admin);

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => 'New Product',
            'description' => 'New Desc',
            'price' => 50000,
            'stock' => 20
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['message' => 'Produk berhasil ditambahkan']);

        $this->assertDatabaseHas('products', ['name' => 'New Product']);
    }

    public function test_customer_cannot_create_product()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $token = auth('api')->login($customer);

        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => 'Customer Product',
            'description' => 'Desc',
            'price' => 100,
            'stock' => 1
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('products', ['name' => 'Customer Product']);
    }

    public function test_product_creation_requires_valid_data()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = auth('api')->login($admin);

        // Missing price and stock
        $response = $this->withToken($token)->postJson('/api/products', [
            'name' => 'Invalid Product',
            'description' => 'Desc',
        ]);

        // Laravel validation returns 422 Unprocessable Entity
        $response->assertStatus(422); 
    }

    public function test_admin_can_update_and_delete_product()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = auth('api')->login($admin);

        $product = Product::create([
            'name' => 'Old Name',
            'description' => 'Old Desc',
            'price' => 1000,
            'stock' => 5
        ]);

        // Update
        $responseUpdate = $this->withToken($token)->putJson('/api/products/' . $product->id, [
            'name' => 'Updated Name',
            'description' => 'Old Desc',
            'price' => 2000,
            'stock' => 10
        ]);

        $responseUpdate->assertStatus(200);
        $this->assertDatabaseHas('products', ['name' => 'Updated Name']);

        // Delete
        $responseDelete = $this->withToken($token)->deleteJson('/api/products/' . $product->id);
        $responseDelete->assertStatus(200);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
