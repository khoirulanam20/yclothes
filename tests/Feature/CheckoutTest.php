<?php

namespace Tests\Feature;

use App\Models\PaymentBank;
use App\Models\Product;
use App\Models\ShippingCost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_checkout_index_shows_form(): void
    {
        $product = Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $response = $this->get('/checkout');
        $response->assertStatus(200);
        $response->assertSee('Data Diri');
        $response->assertSee('Pengiriman');
        $response->assertSee('Metode Pembayaran');
    }

    public function test_checkout_redirects_when_cart_empty(): void
    {
        $response = $this->get('/checkout');
        $response->assertRedirect('/cart');
    }

    public function test_checkout_process_creates_order(): void
    {
        $product = Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 2]);

        $shipping = ShippingCost::first();
        $bank = PaymentBank::first();

        $response = $this->post('/checkout/process', [
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'test@example.com',
            'shipping_address' => 'Jl. Test No. 1',
            'shipping_city' => $shipping->id,
            'payment_method' => 'bank_' . $bank->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'test@example.com',
            'shipping_city' => $shipping->city_name,
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_name' => $product->name,
            'qty' => 2,
        ]);
    }

    public function test_checkout_process_validates_required_fields(): void
    {
        $product = Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $response = $this->post('/checkout/process', []);
        $response->assertSessionHasErrors(['customer_name', 'customer_phone', 'customer_email', 'shipping_address', 'shipping_city', 'payment_method']);
    }

    public function test_shipping_cost_endpoint(): void
    {
        $shipping = ShippingCost::first();

        $response = $this->postJson('/checkout/shipping-cost', [
            'city_id' => $shipping->id,
        ]);

        $response->assertStatus(200)
            ->assertJson(['cost' => $shipping->cost]);
    }

    public function test_shipping_cost_invalid_city(): void
    {
        $response = $this->postJson('/checkout/shipping-cost', [
            'city_id' => 99999,
        ]);

        $response->assertStatus(422);
    }
}
