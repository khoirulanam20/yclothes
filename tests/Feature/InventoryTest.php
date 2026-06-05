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

    public function test_admin_can_add_inventory_for_product_variant(): void
    {
        $admin = User::where('is_admin', true)->first();
        $warehouse = Warehouse::create(['name' => 'WH Variant Form', 'is_active' => true]);
        $product = Product::first();
        $product->update(['type' => \App\Enums\ProductType::Configurable]);

        $variant = \App\Models\ProductVariant::create([
            'parent_product_id' => $product->id,
            'sku' => 'VAR-FORM-001',
            'name' => 'M / Hitam',
            'attributes' => ['size' => 'M', 'color' => 'Hitam'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.inventories.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'warehouse_id' => $warehouse->id,
            'stock' => 15,
            'low_stock_threshold' => 3,
        ]);

        $response->assertRedirect(route('admin.inventories.index'));
        $this->assertDatabaseHas('inventories', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'warehouse_id' => $warehouse->id,
            'stock' => 15,
        ]);
    }

    public function test_create_inventory_form_includes_variants_for_configurable_products(): void
    {
        $admin = User::where('is_admin', true)->first();
        $product = Product::first();
        $product->update(['type' => \App\Enums\ProductType::Configurable, 'name' => 'Produk Varian Form Test']);

        \App\Models\ProductVariant::create([
            'parent_product_id' => $product->id,
            'sku' => 'VAR-FORM-002',
            'name' => 'L / Putih',
            'attributes' => ['size' => 'L', 'color' => 'Putih'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.inventories.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Inventories/Form')
            ->has('products')
            ->where('products', function ($products) use ($product) {
                $match = collect($products)->firstWhere('id', $product->id);

                return $match
                    && $match['type'] === 'configurable'
                    && count($match['variants']) === 1
                    && $match['variants'][0]['sku'] === 'VAR-FORM-002';
            }));
    }

    public function test_admin_can_transfer_stock_for_product_variant(): void
    {
        $admin = User::where('is_admin', true)->first();
        $fromWarehouse = Warehouse::create(['name' => 'WH From', 'is_active' => true]);
        $toWarehouse = Warehouse::create(['name' => 'WH To', 'is_active' => true]);
        $product = Product::first();
        $product->update(['type' => \App\Enums\ProductType::Configurable]);

        $variant = \App\Models\ProductVariant::create([
            'parent_product_id' => $product->id,
            'sku' => 'VAR-TRANSFER-001',
            'name' => 'S / Merah',
            'attributes' => ['size' => 'S', 'color' => 'Merah'],
            'is_active' => true,
        ]);

        Inventory::create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'warehouse_id' => $fromWarehouse->id,
            'stock' => 20,
        ]);

        $response = $this->actingAs($admin)->post('/admin/stock-movements/transfer', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'from_warehouse_id' => $fromWarehouse->id,
            'to_warehouse_id' => $toWarehouse->id,
            'quantity' => 5,
            'reason' => 'Restock cabang',
        ]);

        $response->assertRedirect(route('admin.stock-movements.index'));

        $this->assertDatabaseHas('inventories', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'warehouse_id' => $fromWarehouse->id,
            'stock' => 15,
        ]);
        $this->assertDatabaseHas('inventories', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'warehouse_id' => $toWarehouse->id,
            'stock' => 5,
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

    public function test_inventory_index_shows_variant_stock_not_parent_for_configurable_products(): void
    {
        $admin = User::where('is_admin', true)->first();
        $warehouse = Warehouse::create(['name' => 'WH Variant', 'is_active' => true]);
        $product = Product::first();
        $product->update(['type' => \App\Enums\ProductType::Configurable]);

        $variant = \App\Models\ProductVariant::create([
            'parent_product_id' => $product->id,
            'sku' => 'VAR-STOCK-001',
            'name' => 'Hitam / M',
            'attributes' => ['size' => 'M', 'color' => 'Hitam'],
            'is_active' => true,
        ]);

        $parentInventory = Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'stock' => 99,
        ]);

        $variantInventory = Inventory::create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'warehouse_id' => $warehouse->id,
            'stock' => 12,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.inventories.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Inventories/Index')
            ->has('inventories.data', 1)
            ->where('inventories.data.0.id', $variantInventory->id)
            ->where('inventories.data.0.displaySku', 'VAR-STOCK-001')
            ->where('inventories.data.0.stock', 12));

        $this->assertTrue(Inventory::whereKey($parentInventory->id)->exists());
    }

    public function test_inventory_index_can_filter_by_warehouse_and_search(): void
    {
        $admin = User::where('is_admin', true)->first();
        $warehouseA = Warehouse::create(['name' => 'Gudang A', 'is_active' => true]);
        $warehouseB = Warehouse::create(['name' => 'Gudang B', 'is_active' => true]);
        $product = Product::first();
        $product->update(['name' => 'Kaos Premium', 'sku' => 'PRM-001']);

        Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseA->id,
            'stock' => 10,
        ]);
        Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseB->id,
            'stock' => 20,
        ]);

        $this->actingAs($admin)->get(route('admin.inventories.index', [
            'warehouse_id' => $warehouseA->id,
        ]))->assertInertia(fn ($page) => $page
            ->has('inventories.data', 1)
            ->where('inventories.data.0.stock', 10));

        $this->actingAs($admin)->get(route('admin.inventories.index', [
            'search' => 'Premium',
        ]))->assertInertia(fn ($page) => $page
            ->has('inventories.data', 2));

        $this->actingAs($admin)->get(route('admin.inventories.index', [
            'search' => 'PRM-001',
        ]))->assertInertia(fn ($page) => $page
            ->has('inventories.data', 2));
    }
}
