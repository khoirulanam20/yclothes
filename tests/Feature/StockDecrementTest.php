<?php

namespace Tests\Feature;

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

    public function test_stock_decrements_when_admin_confirms_payment(): void
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
        $order->refresh();
        $this->assertTrue($order->inventory_decremented);
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
        $order->update(['payment_status' => 'paid', 'order_status' => 'confirmed']);

        $service = app(InventoryService::class);
        $service->decrementOnPaid($order);
        $service->decrementOnPaid($order->fresh());

        $inventory->refresh();
        $this->assertEquals(9, $inventory->stock);
    }

    public function test_midtrans_settlement_decrements_stock(): void
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
        $order->update(['payment_method' => 'midtrans']);

        app(OrderPaymentService::class)->applyMidtransStatus($order, 'settlement');

        $inventory->refresh();
        $this->assertEquals(4, $inventory->stock);
    }

    private function createOrderWithProduct(Product $product): Order
    {
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $shipping = ShippingCost::first();
        $bank = PaymentBank::first();

        $this->post('/checkout/process', [
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'test@example.com',
            'shipping_address' => 'Jl. Test',
            'shipping_city' => $shipping->id,
            'payment_method' => 'bank_'.$bank->id,
        ]);

        return Order::first();
    }
}
