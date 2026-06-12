<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Setting::updateOrCreate(['key' => 'payment_cod_enabled'], ['value' => '1']);
        clear_settings_cache();
    }

    public function test_checkout_with_cod_creates_confirmed_unpaid_order(): void
    {
        $order = $this->createCodOrder();

        $this->assertEquals('cod', $order->payment_method);
        $this->assertEquals('pending', $order->payment_status);
        $this->assertEquals('confirmed', $order->order_status);
        $this->assertNull($order->payment_due_at);
    }

    public function test_admin_can_process_cod_without_prior_payment(): void
    {
        $order = $this->createCodOrder();
        $admin = User::where('is_admin', true)->first();

        $this->actingAs($admin)
            ->post(route('admin.orders.status', $order), ['order_status' => 'processed'])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertEquals('processed', $order->fresh()->order_status);
        $this->assertEquals('pending', $order->fresh()->payment_status);
    }

    public function test_admin_can_ship_cod_without_payment(): void
    {
        $order = $this->createCodOrder();
        $order->updateTrusted(['order_status' => 'processed']);
        $admin = User::where('is_admin', true)->first();

        $this->actingAs($admin)
            ->post(route('admin.orders.ship', $order), [
                'courier' => 'JNE',
                'courier_service' => 'REG',
                'tracking_number' => 'COD123',
            ])
            ->assertRedirect();

        $this->assertEquals('shipped', $order->fresh()->order_status);
    }

    public function test_buyer_confirm_received_marks_cod_paid_and_completed(): void
    {
        $order = $this->createCodOrder();
        $order->updateTrusted(['order_status' => 'shipped']);
        grant_order_access($order);

        $this->post(route('order.confirm-received', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('completed', $order->order_status);
    }

    public function test_cod_orders_are_not_auto_expired(): void
    {
        $order = $this->createCodOrder();
        $order->updateTrusted([
            'order_status' => 'pending',
            'payment_due_at' => now()->subHour(),
        ]);

        $this->artisan('orders:expire-pending')->assertSuccessful();

        $this->assertNotEquals('cancelled', $order->fresh()->order_status);
    }

    public function test_cod_not_available_when_disabled(): void
    {
        Setting::updateOrCreate(['key' => 'payment_cod_enabled'], ['value' => '0']);
        clear_settings_cache();

        $product = Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $shipping = ShippingCost::first();

        $response = $this->post('/checkout/process', array_merge([
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'cod-off@example.com',
            'shipping_address' => 'Jl. Test',
            'courier_code' => $shipping->courier_code ?? 'jne',
            'payment_method' => 'cod',
        ], $this->checkoutWilayahFields()));

        $response->assertSessionHasErrors('payment_method');
    }

    private function createCodOrder(): Order
    {
        $product = Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $shipping = ShippingCost::first();

        $this->post('/checkout/process', array_merge([
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'cod@example.com',
            'shipping_address' => 'Jl. Test',
            'courier_code' => $shipping->courier_code ?? 'jne',
            'payment_method' => 'cod',
        ], $this->checkoutWilayahFields()));

        return Order::where('customer_email', 'cod@example.com')->first();
    }
}
