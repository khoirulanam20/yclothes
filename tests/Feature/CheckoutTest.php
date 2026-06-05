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
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Guest/Checkout/Index')
            ->has('items', 1)
            ->has('cities')
            ->has('banks')
        );
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

        $response = $this->post('/checkout/process', array_merge([
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'test@example.com',
            'shipping_address' => 'Jl. Test No. 1',
            'shipping_city' => $shipping->id,
            'payment_method' => 'bank_' . $bank->id,
        ], $this->checkoutWilayahFields()));

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'test@example.com',
            'shipping_city' => 'Temanggung',
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
        $response->assertSessionHasErrors([
            'customer_name', 'customer_phone', 'customer_email', 'shipping_address',
            'province_code', 'regency_code', 'district_code', 'shipping_city', 'payment_method',
        ]);
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

    public function test_checkout_process_redirects_to_success_with_token(): void
    {
        $customer = \App\Models\Customer::factory()->create();
        $product = Product::first();

        $this->actingAs($customer, 'customer')
            ->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $shipping = ShippingCost::first();
        $bank = PaymentBank::first();

        $response = $this->actingAs($customer, 'customer')
            ->post('/checkout/process', array_merge([
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'customer_email' => $customer->email,
                'shipping_address' => 'Jl. Test No. 1',
                'shipping_city' => $shipping->id,
                'payment_method' => 'bank_'.$bank->id,
            ], $this->checkoutWilayahFields()));

        $order = \App\Models\Order::first();
        $this->assertNotNull($order);

        $response->assertRedirect(order_public_url('order.success', $order));

        $this->defaultHeaders = [];

        $this->get('/order/success/'.$order->order_number.'?token='.$order->access_token)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Order/Success')
                ->has('orderShowUrl')
            );
    }
}
