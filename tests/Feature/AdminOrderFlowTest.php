<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentBank;
use App\Models\Product;
use App\Models\ShippingCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_verify_payment_without_buyer_form_marks_paid_and_confirmed(): void
    {
        $order = $this->createOrder();
        $admin = User::where('is_admin', true)->first();

        $this->actingAs($admin)
            ->post(route('admin.orders.payment', $order))
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('confirmed', $order->order_status);
    }

    public function test_admin_cannot_process_unpaid_order(): void
    {
        $order = $this->createOrder();
        $admin = User::where('is_admin', true)->first();

        $this->actingAs($admin)
            ->post(route('admin.orders.status', $order), ['order_status' => 'processed'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertEquals('pending', $order->fresh()->order_status);
    }

    public function test_admin_can_process_after_payment_verified(): void
    {
        $order = $this->createOrder();
        $admin = User::where('is_admin', true)->first();

        $this->actingAs($admin)->post(route('admin.orders.payment', $order));

        $this->actingAs($admin)
            ->post(route('admin.orders.status', $order), ['order_status' => 'processed'])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertEquals('processed', $order->fresh()->order_status);
    }

    public function test_admin_order_show_includes_contextual_actions(): void
    {
        $order = $this->createOrder();
        $admin = User::where('is_admin', true)->first();

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Orders/Show')
                ->has('orderActions')
                ->where('orderActions', fn ($actions) => collect($actions)->contains('key', 'verify_payment'))
            );

        $this->actingAs($admin)->post(route('admin.orders.payment', $order));

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertInertia(fn ($page) => $page
                ->where('orderActions', fn ($actions) => collect($actions)->contains('key', 'process'))
            );
    }

    public function test_admin_cannot_set_confirmed_via_status_endpoint(): void
    {
        $order = $this->createOrder();
        $admin = User::where('is_admin', true)->first();

        $this->actingAs($admin)
            ->post(route('admin.orders.status', $order), ['order_status' => 'confirmed'])
            ->assertSessionHasErrors('order_status');

        $this->assertEquals('pending', $order->fresh()->order_status);
    }

    private function createOrder(): Order
    {
        $product = Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $shipping = ShippingCost::first();
        $bank = PaymentBank::first();

        $this->post('/checkout/process', array_merge([
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'test@example.com',
            'shipping_address' => 'Jl. Test',
            'courier_code' => $shipping->courier_code ?? 'jne',
            'payment_method' => 'bank_'.$bank->id,
        ], $this->checkoutWilayahFields()));

        return Order::first();
    }
}
