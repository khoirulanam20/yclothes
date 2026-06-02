<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_cart_index_returns_success(): void
    {
        $response = $this->get('/cart');
        $response->assertStatus(200);
    }

    public function test_cart_add_product(): void
    {
        $product = Product::first();

        $response = $this->postJson('/cart/add', [
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_cart_add_then_update_qty(): void
    {
        $product = Product::first();

        $this->postJson('/cart/add', [
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $itemKey = $product->id.'--';

        $response = $this->postJson('/cart/update', [
            'key' => $itemKey,
            'qty' => 3,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_cart_add_then_remove(): void
    {
        $product = Product::first();

        $this->postJson('/cart/add', [
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $itemKey = $product->id.'--';

        $response = $this->postJson('/cart/remove', [
            'key' => $itemKey,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_checkout_page_accessible(): void
    {
        $product = Product::first();
        $this->postJson('/cart/add', [
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $response = $this->get('/checkout');
        $response->assertStatus(200);
    }

    public function test_checkout_empty_cart_redirects(): void
    {
        $response = $this->get('/checkout');
        $response->assertRedirect('/cart');
    }
}
