<?php

namespace Tests\Feature;

use App\Models\PaymentBank;
use App\Models\Product;
use App\Models\Setting;
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
            ->has('shippingMode')
            ->has('banks')
            ->has('paymentMethods')
        );
    }

    public function test_checkout_shows_cod_coming_soon_when_disabled(): void
    {
        Setting::updateOrCreate(['key' => 'payment_cod_enabled'], ['value' => '0']);
        clear_settings_cache();

        $product = Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $this->get('/checkout')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Checkout/Index')
                ->where('paymentMethodsComingSoon', fn ($methods) => collect($methods)->contains(
                    fn ($m) => ($m['id'] ?? null) === 'cod' && ($m['comingSoon'] ?? false) === true
                ))
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
            'courier_code' => $shipping->courier_code ?? 'jne',
            'payment_method' => 'bank_' . $bank->id,
        ], $this->checkoutWilayahFields()));

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'test@example.com',
            'shipping_city' => 'Kabupaten Temanggung',
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
            'province_code', 'regency_code', 'district_code', 'courier_code', 'payment_method',
        ]);
    }

    public function test_shipping_options_endpoint(): void
    {
        $product = Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $response = $this->postJson('/checkout/shipping-options', [
            'regency_code' => '33.73',
            'postal_code' => '56211',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['options']);
    }

    public function test_shipping_options_requires_regency(): void
    {
        $response = $this->postJson('/checkout/shipping-options', []);

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
                'courier_code' => $shipping->courier_code ?? 'jne',
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
