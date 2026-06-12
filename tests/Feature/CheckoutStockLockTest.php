<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\PaymentBank;
use App\Models\Product;
use App\Models\ShippingCost;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutStockLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_second_checkout_fails_when_stock_is_exhausted_by_first_order(): void
    {
        $product = Product::first();
        $product->update(['track_stock' => true, 'allow_backorder' => false]);
        Inventory::where('product_id', $product->id)->delete();

        $warehouse = Warehouse::create(['name' => 'WH-Lock', 'is_active' => true]);
        $inventory = Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'stock' => 1,
        ]);

        $payload = array_merge([
            'customer_name' => 'Buyer One',
            'customer_phone' => '08123456789',
            'customer_email' => 'buyer1@example.com',
            'shipping_address' => 'Jl. Test',
            'courier_code' => (ShippingCost::first()?->courier_code ?? 'jne'),
            'payment_method' => 'bank_'.PaymentBank::first()->id,
        ], $this->checkoutWilayahFields());

        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);
        $this->post('/checkout/process', $payload)->assertRedirect();

        $this->assertSame(1, Order::count());
        $inventory->refresh();
        $this->assertSame(0, $inventory->stock);

        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);
        $this->post('/checkout/process', array_merge($payload, [
            'customer_email' => 'buyer2@example.com',
        ]));

        $this->assertSame(1, Order::count());
        $inventory->refresh();
        $this->assertSame(0, $inventory->stock);
    }
}
