<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\PaymentBank;
use App\Models\Product;
use App\Models\ShippingCost;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\OrderPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockDecrementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_checkout_reserves_stock_without_payment(): void
    {
        $product = Product::first();
        $product->update(['track_stock' => true]);

        $warehouse = Warehouse::create(['name' => 'WH', 'is_active' => true]);
        $inventory = Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'stock' => 10,
        ]);

        $order = $this->createOrderWithProduct($product);

        $admin = User::where('is_admin', true)->first();
        $this->actingAs($admin)->post(route('admin.orders.payment', $order));

        $inventory->refresh();
        $this->assertEquals(9, $inventory->stock);
        $this->assertTrue($order->fresh()->inventory_decremented);
    }

    public function test_stock_reserved_at_checkout_and_confirm_received_is_idempotent(): void
    {
        $product = Product::first();
        $product->update(['track_stock' => true]);

        $warehouse = Warehouse::create(['name' => 'WH', 'is_active' => true]);
        $inventory = Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'stock' => 10,
        ]);

        $order = $this->createOrderWithProduct($product);
        $this->assertEquals(9, $inventory->fresh()->stock);

        $order->updateTrusted(['order_status' => 'shipped', 'payment_status' => 'paid']);
        grant_order_access($order);

        $this->post(route('order.confirm-received', $order));

        $inventory->refresh();
        $this->assertEquals(9, $inventory->stock);
        $this->assertTrue($order->fresh()->inventory_decremented);
    }

    public function test_admin_cannot_mark_order_delivered_or_completed_via_status(): void
    {
        $order = $this->createOrderWithProduct(Product::first());
        $order->updateTrusted(['order_status' => 'shipped', 'payment_status' => 'paid']);

        $admin = User::where('is_admin', true)->first();

        $this->actingAs($admin)
            ->post(route('admin.orders.status', $order), ['order_status' => 'delivered'])
            ->assertSessionHasErrors('order_status');

        $this->assertEquals('shipped', $order->fresh()->order_status);

        $order->updateTrusted(['order_status' => 'delivered']);

        $this->actingAs($admin)
            ->post(route('admin.orders.status', $order), ['order_status' => 'completed'])
            ->assertSessionHasErrors('order_status');

        $this->assertEquals('delivered', $order->fresh()->order_status);
    }

    public function test_guest_with_order_access_can_confirm_received_for_customer_order(): void
    {
        $customer = Customer::factory()->create();
        $order = $this->createOrderWithProduct(Product::first());
        $order->updateTrusted([
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
            'order_status' => 'shipped',
            'payment_status' => 'paid',
        ]);
        grant_order_access($order);

        $this->get(order_public_url('order.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('canConfirmReceived', true));

        $this->post(route('order.confirm-received', $order))
            ->assertRedirect();

        $this->assertEquals('completed', $order->fresh()->order_status);
    }

    public function test_guest_without_order_access_cannot_confirm_received_for_customer_order(): void
    {
        $customer = Customer::factory()->create();
        $order = $this->createOrderWithProduct(Product::first());
        $order->updateTrusted([
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
            'order_status' => 'shipped',
            'payment_status' => 'paid',
        ]);
        session()->forget('order_access.'.$order->order_number);

        $this->post(route('order.confirm-received', $order))
            ->assertForbidden();
    }

    public function test_logged_in_customer_can_confirm_received_via_account_route(): void
    {
        $customer = Customer::factory()->create();
        $order = $this->createOrderWithProduct(Product::first());
        $order->updateTrusted([
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
            'order_status' => 'shipped',
            'payment_status' => 'paid',
        ]);

        $this->actingAs($customer, 'customer')
            ->post(route('customer.orders.confirm-received', $order))
            ->assertRedirect();

        $this->assertEquals('completed', $order->fresh()->order_status);
    }

    public function test_stock_decrement_is_idempotent(): void
    {
        $product = Product::first();
        $product->update(['track_stock' => true]);

        $warehouse = Warehouse::create(['name' => 'WH', 'is_active' => true]);
        $inventory = Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'stock' => 10,
        ]);

        $order = $this->createOrderWithProduct($product);
        $order->updateTrusted(['order_status' => 'completed', 'payment_status' => 'paid']);

        $service = app(InventoryService::class);
        $service->decrementForOrder($order);
        $service->decrementForOrder($order->fresh());

        $inventory->refresh();
        $this->assertEquals(9, $inventory->stock);
    }

    public function test_midtrans_settlement_does_not_decrement_stock_again(): void
    {
        $product = Product::first();
        $product->update(['track_stock' => true]);

        $warehouse = Warehouse::create(['name' => 'WH', 'is_active' => true]);
        $inventory = Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'stock' => 5,
        ]);

        $order = $this->createOrderWithProduct($product);
        $order->updateTrusted(['payment_method' => 'midtrans']);

        app(OrderPaymentService::class)->applyMidtransStatus($order, 'settlement');

        $inventory->refresh();
        $this->assertEquals(4, $inventory->stock);
        $this->assertTrue($order->fresh()->inventory_decremented);
    }

    private function createOrderWithProduct(Product $product): Order
    {
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $shipping = ShippingCost::first();
        $bank = PaymentBank::first();

        $this->post('/checkout/process', array_merge([
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'test@example.com',
            'shipping_address' => 'Jl. Test',
            'shipping_city' => $shipping->id,
            'payment_method' => 'bank_'.$bank->id,
        ], $this->checkoutWilayahFields()));

        return Order::first();
    }
}
