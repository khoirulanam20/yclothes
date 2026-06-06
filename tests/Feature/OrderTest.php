<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentBank;
use App\Models\Product;
use App\Models\ShippingCost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function createOrder(): Order
    {
        $product = Product::first();
        $shipping = ShippingCost::first();
        $bank = PaymentBank::first();

        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $this->post('/checkout/process', array_merge([
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'test@example.com',
            'shipping_address' => 'Jl. Test No. 1',
            'shipping_city' => $shipping->id,
            'payment_method' => 'bank_'.$bank->id,
        ], $this->checkoutWilayahFields()));

        return Order::first();
    }

    public function test_order_success_page_requires_token(): void
    {
        $order = $this->createOrder();

        $this->flushSession();

        $this->get("/order/success/{$order->order_number}")
            ->assertForbidden();

        $this->get(order_public_url('order.success', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Order/Success')
                ->where('order.orderNumber', $order->order_number)
                ->has('orderShowUrl')
            );
    }

    public function test_order_track_page(): void
    {
        $response = $this->get('/order/track');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Guest/Order/Track')
            ->where('requiresEmail', true)
        );
    }

    public function test_order_track_page_hides_email_when_logged_in(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer')
            ->get('/order/track')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Order/Track')
                ->where('requiresEmail', false)
            );
    }

    public function test_logged_in_customer_tracks_order_by_number_only(): void
    {
        $customer = Customer::factory()->create();
        $order = $this->createOrder();
        $order->updateTrusted(['customer_id' => $customer->id, 'customer_email' => $customer->email]);

        $response = $this->actingAs($customer, 'customer')
            ->post('/order/track', ['order_number' => $order->order_number]);

        $response->assertRedirect(order_public_url('order.show', $order));
    }

    public function test_logged_in_customer_cannot_track_other_order(): void
    {
        $customer = Customer::factory()->create();
        $order = $this->createOrder();

        $this->actingAs($customer, 'customer')
            ->post('/order/track', ['order_number' => $order->order_number])
            ->assertRedirect('/order/track')
            ->assertSessionHas('error');
    }

    public function test_order_search_by_number_and_email(): void
    {
        $order = $this->createOrder();

        $response = $this->post('/order/track', [
            'order_number' => $order->order_number,
            'email' => $order->customer_email,
        ]);

        $response->assertRedirect(order_public_url('order.show', $order));
    }

    public function test_order_search_by_phone_only_fails(): void
    {
        $order = $this->createOrder();

        $response = $this->post('/order/track', [
            'order_number' => $order->customer_phone,
            'email' => 'wrong@example.com',
        ]);

        $response->assertRedirect('/order/track');
    }

    public function test_order_search_not_found(): void
    {
        $response = $this->post('/order/track', [
            'order_number' => 'INV-ZZZZZZZZ',
            'email' => 'nobody@example.com',
        ]);

        $response->assertRedirect('/order/track');
    }

    public function test_order_detail_page_requires_token(): void
    {
        $order = $this->createOrder();

        $this->flushSession();

        $this->get("/order/{$order->order_number}")
            ->assertForbidden();

        $response = $this->get(order_public_url('order.show', $order));
        $response->assertStatus(200);
        $response->assertSee($order->order_number);
        $response->assertSee($order->customer_name);
    }

    public function test_order_detail_by_numeric_id_not_accessible(): void
    {
        $order = $this->createOrder();

        $this->get("/order/{$order->id}")
            ->assertNotFound();
    }

    public function test_payment_finish_ignores_fake_client_status(): void
    {
        $order = $this->createOrder();
        $order->updateTrusted(['payment_method' => 'midtrans']);

        $this->postJson(
            route('order.payment-finish', ['order' => $order->order_number, 'token' => $order->access_token]),
            ['transaction_status' => 'settlement'],
        )->assertOk();

        $order->refresh();
        $this->assertNotEquals('paid', $order->payment_status);
    }
}
