<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentConfirmation;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingCost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrisPaymentConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        Setting::updateOrCreate(['key' => 'payment_qris_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'qris_image'], ['value' => 'payments/qris-test.png']);
        clear_settings_cache();
    }

    public function test_qris_payment_confirmation_without_bank_id(): void
    {
        $product = Product::first();
        $shipping = ShippingCost::first();

        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $this->post('/checkout/process', array_merge([
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'qris-confirm@example.com',
            'shipping_address' => 'Jl. Test No. 1',
            'shipping_city' => $shipping->id,
            'payment_method' => 'qris',
        ], $this->checkoutWilayahFields()));

        $order = Order::first();
        grant_order_access($order);

        $response = $this->post("/order/{$order->order_number}/confirm-payment", [
            'amount_claimed' => $order->unique_payment_amount ?? $order->grand_total,
            'transfer_date' => now()->toDateString(),
            'sender_name' => 'Test User',
        ]);

        $response->assertRedirect(order_public_url('order.show', $order));

        $this->assertDatabaseHas('payment_confirmations', [
            'order_id' => $order->id,
            'payment_bank_id' => null,
            'sender_name' => 'Test User',
            'status' => 'pending',
        ]);

        $this->assertEquals('pending', $order->fresh()->payment_confirmation_status);
        $this->assertEquals(1, PaymentConfirmation::where('order_id', $order->id)->count());
    }
}
