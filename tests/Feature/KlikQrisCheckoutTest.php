<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingCost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KlikQrisCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        Setting::updateOrCreate(['key' => 'payment_klikqris_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'klikqris_api_key'], ['value' => 'sk_sandbox_test_key']);
        Setting::updateOrCreate(['key' => 'klikqris_merchant_id'], ['value' => '178093268321']);
        clear_settings_cache();
    }

    public function test_klikqris_checkout_shows_snap_payment_page(): void
    {
        Http::fake([
            'klikqris.com/*' => Http::response([
                'status' => true,
                'message' => 'Transaction Created Successfully',
                'data' => [
                    'order_id' => 'INV-TEST',
                    'total_amount' => '1016.00',
                    'amount' => '1000.00',
                    'status' => 'PENDING',
                    'signature' => 'SANDBOX_SIG_test123',
                    'qris_url' => 'https://klikqris.com/storage/sandbox/qris_test.png',
                    'qris_image' => 'data:image/png;base64,iVBORw0KGgo=',
                    'expired_at' => now()->addHour()->format('Y-m-d H:i:s'),
                ],
            ], 200),
        ]);

        $product = Product::first();
        $shipping = ShippingCost::first();

        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $response = $this->post('/checkout/process', array_merge([
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'klikqris@example.com',
            'shipping_address' => 'Jl. Test No. 1',
            'courier_code' => $shipping->courier_code ?? 'jne',
            'payment_method' => 'klikqris',
        ], $this->checkoutWilayahFields()));

        $order = Order::where('customer_email', 'klikqris@example.com')->first();
        $this->assertNotNull($order);
        $this->assertEquals('klikqris', $order->payment_method);
        $this->assertEquals(1016, $order->unique_payment_amount);
        $this->assertSame('SANDBOX_SIG_test123', $order->payment_gateway_data['klikqris']['signature'] ?? null);

        $response->assertRedirect(order_klikqris_payment_url($order));

        Http::assertSent(function ($request) use ($order) {
            if (! str_contains($request->url(), 'klikqris.com')) {
                return false;
            }

            $body = $request->data();

            return ($body['callback_url'] ?? null) === route('klikqris.notification')
                && ($body['redirect_url'] ?? null) === order_public_url('order.success', $order)
                && ($body['order_id'] ?? null) === $order->order_number;
        });

        $paymentPage = $this->get(order_klikqris_payment_url($order));
        $paymentPage->assertOk();
        $paymentPage->assertSee('Pembayaran KlikQRIS');
        $paymentPage->assertSee('payment-snap.js');
        $paymentPage->assertSee('env=sandbox');
        $paymentPage->assertSee('data:image/png;base64');
        $paymentPage->assertSee('Cek Status');
    }

    public function test_klikqris_checkout_redirects_inertia_requests_with_location_header(): void
    {
        Http::fake([
            'klikqris.com/*' => Http::response([
                'status' => true,
                'data' => [
                    'total_amount' => '1016.00',
                    'signature' => 'SANDBOX_SIG_test123',
                ],
            ], 200),
        ]);

        $product = Product::first();
        $shipping = ShippingCost::first();

        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $response = $this->post('/checkout/process', array_merge([
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'inertia-kq@example.com',
            'shipping_address' => 'Jl. Test No. 1',
            'courier_code' => $shipping->courier_code ?? 'jne',
            'payment_method' => 'klikqris',
        ], $this->checkoutWilayahFields()), [
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $order = Order::where('customer_email', 'inertia-kq@example.com')->firstOrFail();

        $response->assertStatus(409);
        $response->assertHeader('X-Inertia-Location', order_klikqris_payment_url($order));
    }

    public function test_klikqris_payment_page_recovers_existing_transaction(): void
    {
        Http::fake([
            'klikqris.com/api/sandbox/qris/create' => Http::response([
                'status' => false,
                'message' => 'Order ID already exists',
            ], 422),
            'klikqris.com/api/sandbox/qris/status/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'PENDING',
                    'total_amount' => '161116.00',
                    'signature' => 'SANDBOX_SIG_recovered',
                ],
            ], 200),
        ]);

        $order = Order::createTrusted([
            'order_number' => 'INV-RECOVER',
            'access_token' => 'token-recover',
            'customer_name' => 'Test',
            'customer_phone' => '08123456789',
            'customer_email' => 'recover@example.com',
            'shipping_address' => 'Jl. Test',
            'shipping_city' => 'Temanggung',
            'total_price' => 161100,
            'grand_total' => 161100,
            'payment_method' => 'klikqris',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        $response = $this->get(order_klikqris_payment_url($order));

        $response->assertOk();
        $response->assertSee('Pembayaran KlikQRIS');
        $this->assertSame('SANDBOX_SIG_recovered', $order->fresh()->payment_gateway_data['klikqris']['signature'] ?? null);
        $this->assertSame(161116, $order->fresh()->unique_payment_amount);
    }
}
