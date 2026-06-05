<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Models\AttributeFamily;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductInventorySyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_simple_product_save_creates_inventory_rows(): void
    {
        $admin = User::where('is_admin', true)->first();
        $category = Category::first();
        $warehouse = Warehouse::where('is_active', true)->first();
        $family = AttributeFamily::first();

        $product = Product::create([
            'category_id' => $category->id,
            'attribute_family_id' => $family->id,
            'type' => ProductType::Simple,
            'sku' => 'INV-SIMPLE-001',
            'name' => 'Produk Stok Simple',
            'slug' => 'produk-stok-simple',
            'price' => 120000,
            'image' => '',
            'track_stock' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'attribute_family_id' => $product->attribute_family_id,
            'type' => ProductType::Simple->value,
            'sku' => $product->sku,
            'name' => $product->name,
            'price' => 120000,
            'track_stock' => true,
            'inventories' => [
                [
                    'warehouse_id' => $warehouse->id,
                    'stock' => 25,
                    'low_stock_threshold' => 3,
                ],
            ],
            'image' => UploadedFile::fake()->image('product.jpg'),
        ]);

        $response->assertRedirect(route('admin.products.edit', $product));

        $inventory = Inventory::where('product_id', $product->id)
            ->whereNull('product_variant_id')
            ->where('warehouse_id', $warehouse->id)
            ->first();

        $this->assertNotNull($inventory);
        $this->assertSame(25, $inventory->stock);
        $this->assertSame(3, $inventory->low_stock_threshold);

        $service = app(InventoryService::class);
        $this->assertSame(25, $service->getAvailableStock($product->fresh()));
    }
}
