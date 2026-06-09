<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KlikQrisNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        Setting::updateOrCreate(['key' => 'klikqris_api_key'], ['value' => 'sk_sandbox_test_key']);
        Setting::updateOrCreate(['key' => 'klikqris_merchant_id'], ['value' => '178093268321']);
        clear_settings_cache();
    }

    public function test_klikqris_notification_marks_paid(): void
    {
        $order = Order::createTrusted([
            'order_number' => 'INV-KQ001',
            'access_token' => 'token-kq',
            'customer_name' => 'Test',
            'customer_phone' => '08123456789',
            'customer_email' => 'kq@example.com',
            'shipping_address' => 'Jl. Test',
            'shipping_city' => 'Temanggung',
            'total_price' => 1000,
            'grand_total' => 1000,
            'unique_payment_amount' => 1016,
            'payment_method' => 'klikqris',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'payment_gateway_data' => [
                'klikqris' => ['signature' => 'SANDBOX_SIG_test'],
            ],
        ]);

        $payload = [
            'order_id' => $order->order_number,
            'status' => 'PAID',
            'amount' => 1000,
            'total_amount' => 1016,
            'signature' => 'SANDBOX_SIG_webhook',
        ];

        $response = $this->postJson('/klikqris/notification', $payload);

        $response->assertOk();
        $this->assertEquals('paid', $order->fresh()->payment_status);
    }

    public function test_klikqris_notification_rejects_amount_mismatch(): void
    {
        $order = Order::createTrusted([
            'order_number' => 'INV-KQ002',
            'access_token' => 'token-kq2',
            'customer_name' => 'Test',
            'customer_phone' => '08123456789',
            'customer_email' => 'kq2@example.com',
            'shipping_address' => 'Jl. Test',
            'shipping_city' => 'Temanggung',
            'total_price' => 1000,
            'grand_total' => 1000,
            'unique_payment_amount' => 1016,
            'payment_method' => 'klikqris',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        $payload = [
            'order_id' => $order->order_number,
            'status' => 'PAID',
            'total_amount' => 9999,
            'signature' => 'SANDBOX_SIG_mismatch',
        ];

        $response = $this->postJson('/klikqris/notification', $payload);

        $response->assertStatus(400);
        $this->assertEquals('pending', $order->fresh()->payment_status);
    }

    public function test_klikqris_duplicate_notification_is_idempotent(): void
    {
        $order = Order::createTrusted([
            'order_number' => 'INV-KQ003',
            'access_token' => 'token-kq3',
            'customer_name' => 'Test',
            'customer_phone' => '08123456789',
            'customer_email' => 'kq3@example.com',
            'shipping_address' => 'Jl. Test',
            'shipping_city' => 'Temanggung',
            'total_price' => 1000,
            'grand_total' => 1000,
            'unique_payment_amount' => 1016,
            'payment_method' => 'klikqris',
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
            'paid_at' => now(),
        ]);

        $payload = [
            'order_id' => $order->order_number,
            'status' => 'PAID',
            'total_amount' => 1016,
            'signature' => 'SANDBOX_SIG_dup',
        ];

        $response = $this->postJson('/klikqris/notification', $payload);

        $response->assertOk();
        $this->assertEquals('paid', $order->fresh()->payment_status);
    }
}
