<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DokuNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        Setting::updateOrCreate(['key' => 'doku_client_id'], ['value' => 'test-client-id']);
        Setting::updateOrCreate(['key' => 'doku_secret_key'], ['value' => 'test-secret-key']);
        clear_settings_cache();
    }

    public function test_doku_notification_with_valid_signature_marks_paid(): void
    {
        $order = Order::createTrusted([
            'order_number' => 'INV-DOKU001',
            'access_token' => 'token-doku',
            'customer_name' => 'Test',
            'customer_phone' => '08123456789',
            'customer_email' => 'doku@example.com',
            'shipping_address' => 'Jl. Test',
            'shipping_city' => 'Temanggung',
            'total_price' => 150000,
            'grand_total' => 150000,
            'payment_method' => 'doku',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        $payload = [
            'order' => [
                'invoice_number' => $order->order_number,
                'amount' => 150000,
            ],
            'transaction' => [
                'status' => 'SUCCESS',
            ],
        ];

        $response = $this->signedDokuNotification($payload);

        $response->assertOk();
        $this->assertEquals('paid', $order->fresh()->payment_status);
    }

    public function test_doku_notification_rejects_invalid_signature(): void
    {
        $order = Order::createTrusted([
            'order_number' => 'INV-DOKU002',
            'access_token' => 'token-doku2',
            'customer_name' => 'Test',
            'customer_phone' => '08123456789',
            'customer_email' => 'doku2@example.com',
            'shipping_address' => 'Jl. Test',
            'shipping_city' => 'Temanggung',
            'total_price' => 150000,
            'grand_total' => 150000,
            'payment_method' => 'doku',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        $payload = [
            'order' => ['invoice_number' => $order->order_number, 'amount' => 150000],
            'transaction' => ['status' => 'SUCCESS'],
        ];

        $body = json_encode($payload);

        $response = $this->call('POST', '/doku/notification', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Client-Id' => 'test-client-id',
            'HTTP_Request-Id' => (string) Str::uuid(),
            'HTTP_Request-Timestamp' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'HTTP_Signature' => 'HMACSHA256=invalid',
        ], $body);

        $response->assertStatus(401);
        $this->assertEquals('pending', $order->fresh()->payment_status);
    }

    /** @param  array<string, mixed>  $payload */
    private function signedDokuNotification(array $payload)
    {
        $body = json_encode($payload);
        $clientId = 'test-client-id';
        $secret = 'test-secret-key';
        $requestId = (string) Str::uuid();
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');
        $target = '/doku/notification';
        $digest = base64_encode(hash('sha256', $body, true));
        $component = "Client-Id:{$clientId}\n"
            ."Request-Id:{$requestId}\n"
            ."Request-Timestamp:{$timestamp}\n"
            ."Request-Target:{$target}\n"
            ."Digest:{$digest}";
        $signature = base64_encode(hash_hmac('sha256', $component, $secret, true));

        return $this->call('POST', '/doku/notification', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Client-Id' => $clientId,
            'HTTP_Request-Id' => $requestId,
            'HTTP_Request-Timestamp' => $timestamp,
            'HTTP_Signature' => 'HMACSHA256='.$signature,
        ], $body);
    }
}
