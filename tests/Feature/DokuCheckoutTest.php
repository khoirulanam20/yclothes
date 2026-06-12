<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingCost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DokuCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        Setting::updateOrCreate(['key' => 'payment_doku_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'doku_client_id'], ['value' => 'test-client-id']);
        Setting::updateOrCreate(['key' => 'doku_secret_key'], ['value' => 'test-secret-key']);
        clear_settings_cache();
    }

    public function test_doku_checkout_redirects_to_payment_url(): void
    {
        Http::fake([
            'api-sandbox.doku.com/*' => Http::response([
                'response' => [
                    'payment' => [
                        'url' => 'https://sandbox.doku.com/pay/test-order',
                    ],
                ],
            ], 200),
        ]);

        $product = Product::first();
        $shipping = ShippingCost::first();

        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $response = $this->post('/checkout/process', array_merge([
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'doku@example.com',
            'shipping_address' => 'Jl. Test No. 1',
            'courier_code' => $shipping->courier_code ?? 'jne',
            'payment_method' => 'doku',
        ], $this->checkoutWilayahFields()));

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals('doku', $order->payment_method);

        $response->assertRedirect('https://sandbox.doku.com/pay/test-order');
    }
}
