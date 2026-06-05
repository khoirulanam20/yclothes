<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_create_warehouse(): void
    {
        $admin = User::where('is_admin', true)->first();

        $response = $this->actingAs($admin)->post(route('admin.warehouses.store'), [
            'name' => 'Gudang Pusat',
            'city' => 'Jakarta',
            'address' => 'Jl. Gudang No. 1',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.warehouses.index'));
        $this->assertDatabaseHas('warehouses', ['name' => 'Gudang Pusat']);
    }

    public function test_admin_can_manage_inventory(): void
    {
        $admin = User::where('is_admin', true)->first();
        $product = Product::first();
        $warehouse = Warehouse::create(['name' => 'WH Test', 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('admin.inventories.store'), [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'stock' => 50,
            'low_stock_threshold' => 5,
        ]);

        $response->assertRedirect(route('admin.inventories.index'));
        $this->assertDatabaseHas('inventories', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'stock' => 50,
        ]);
    }

    public function test_can_order_returns_false_when_out_of_stock(): void
    {
        $product = Product::first();
        $product->update(['track_stock' => true, 'allow_backorder' => false]);

        $warehouse = Warehouse::create(['name' => 'WH', 'is_active' => true]);
        Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'stock' => 0,
        ]);

        $service = app(InventoryService::class);
        $this->assertFalse($service->canOrder($product, null, 1));
    }

    public function test_can_order_allows_backorder(): void
    {
        $product = Product::first();
        $product->update(['track_stock' => true, 'allow_backorder' => true]);

        $warehouse = Warehouse::create(['name' => 'WH', 'is_active' => true]);
        Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'stock' => 0,
        ]);

        $service = app(InventoryService::class);
        $this->assertTrue($service->canOrder($product, null, 1));
    }

    public function test_checkout_blocked_when_out_of_stock(): void
    {
        $product = Product::first();
        $product->update(['track_stock' => true, 'allow_backorder' => false]);

        $warehouse = Warehouse::create(['name' => 'WH', 'is_active' => true]);
        Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'stock' => 0,
        ]);

        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $shipping = \App\Models\ShippingCost::first();
        $bank = \App\Models\PaymentBank::first();

        $response = $this->post('/checkout/process', array_merge([
            'customer_name' => 'Test',
            'customer_phone' => '08123456789',
            'customer_email' => 'test@example.com',
            'shipping_address' => 'Jl. Test',
            'shipping_city' => $shipping->id,
            'payment_method' => 'bank_'.$bank->id,
        ], $this->checkoutWilayahFields()));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}
