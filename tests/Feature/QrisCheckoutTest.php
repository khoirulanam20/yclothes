<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingCost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrisCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        Setting::updateOrCreate(['key' => 'payment_qris_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'qris_image'], ['value' => 'payments/qris-test.png']);
        Setting::updateOrCreate(['key' => 'qris_merchant_name'], ['value' => 'Toko Test QRIS']);
        clear_settings_cache();
    }

    public function test_qris_checkout_creates_order_with_unique_amount(): void
    {
        $product = Product::first();
        $shipping = ShippingCost::first();

        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $response = $this->post('/checkout/process', array_merge([
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'qris@example.com',
            'shipping_address' => 'Jl. Test No. 1',
            'courier_code' => $shipping->courier_code ?? 'jne',
            'payment_method' => 'qris',
        ], $this->checkoutWilayahFields()));

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals('qris', $order->payment_method);
        $this->assertNotNull($order->unique_payment_amount);

        $response->assertRedirect(order_public_url('order.success', $order));

        $this->get(order_public_url('order.success', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Order/Success')
                ->where('order.paymentMethod', 'qris')
                ->has('qris.imageUrl')
            );
    }
}
