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

class ConfigurableInventorySyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_variant_inventory_syncs_per_warehouse(): void
    {
        $admin = User::where('is_admin', true)->first();
        $category = Category::first();
        $warehouse = Warehouse::where('is_active', true)->first();
        $family = AttributeFamily::where('name', 'Fashion Default')->first();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'type' => ProductType::Configurable->value,
            'attribute_family_id' => $family->id,
            'sku' => 'CFG-INV-001',
            'name' => 'Kaos Inventory',
        ]);

        $product = Product::where('sku', 'CFG-INV-001')->first();

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'attribute_family_id' => $family->id,
            'type' => ProductType::Configurable->value,
            'sku' => 'CFG-INV-001',
            'name' => 'Kaos Inventory',
            'price' => 150000,
            'track_stock' => true,
            'is_active' => true,
            'image' => UploadedFile::fake()->image('product.jpg'),
            'attributes' => [
                'size' => ['S', 'M'],
                'color' => [
                    ['hex' => '#000000', 'name' => 'Hitam'],
                ],
            ],
        ]);

        $variant = $product->fresh()->variants()->first();
        $this->assertNotNull($variant);

        $response = $this->actingAs($admin)->put(route('admin.products.variants.update', $product), [
            'variants' => [
                [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'price' => 175000,
                    'is_active' => true,
                    'inventories' => [
                        [
                            'warehouse_id' => $warehouse->id,
                            'stock' => 12,
                            'low_stock_threshold' => 2,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();

        $variant->refresh();
        $this->assertSame(175000, $variant->price);

        $inventory = Inventory::where('product_id', $product->id)
            ->where('product_variant_id', $variant->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        $this->assertNotNull($inventory);
        $this->assertSame(12, $inventory->stock);

        $service = app(InventoryService::class);
        $this->assertSame(12, $service->getAvailableStock($product->fresh(), $variant->fresh()));
    }

    public function test_variant_inventory_syncs_from_json_when_sent_as_form_field(): void
    {
        $admin = User::where('is_admin', true)->first();
        $warehouse = Warehouse::where('is_active', true)->first();
        $product = $this->createConfigurableProductWithVariants();
        $variant = $product->variants()->first();
        $this->assertNotNull($variant);

        $response = $this->actingAs($admin)->put(route('admin.products.variants.update', $product), [
            'variants' => [
                [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'is_active' => true,
                    'inventories_json' => json_encode([
                        [
                            'warehouse_id' => $warehouse->id,
                            'stock' => 7,
                            'low_stock_threshold' => 1,
                        ],
                    ]),
                ],
            ],
        ]);

        $response->assertRedirect();

        $inventory = Inventory::where('product_id', $product->id)
            ->where('product_variant_id', $variant->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        $this->assertNotNull($inventory);
        $this->assertSame(7, $inventory->stock);
    }

    public function test_variant_inventory_syncs_even_when_track_stock_is_disabled(): void
    {
        $admin = User::where('is_admin', true)->first();
        $warehouse = Warehouse::where('is_active', true)->first();
        $product = $this->createConfigurableProductWithVariants();
        $product->update(['track_stock' => false]);
        $variant = $product->variants()->first();
        $this->assertNotNull($variant);

        $this->actingAs($admin)->put(route('admin.products.variants.update', $product), [
            'variants' => [
                [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'is_active' => true,
                    'inventories' => [
                        [
                            'warehouse_id' => $warehouse->id,
                            'stock' => 9,
                            'low_stock_threshold' => 2,
                        ],
                    ],
                ],
            ],
        ]);

        $inventory = Inventory::where('product_id', $product->id)
            ->where('product_variant_id', $variant->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        $this->assertNotNull($inventory);
        $this->assertSame(9, $inventory->stock);
    }

    private function createConfigurableProductWithVariants(): Product
    {
        $admin = User::where('is_admin', true)->first();
        $category = Category::first();
        $family = AttributeFamily::where('name', 'Fashion Default')->first();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'type' => ProductType::Configurable->value,
            'attribute_family_id' => $family->id,
            'sku' => 'CFG-INV-'.uniqid(),
            'name' => 'Kaos Inventory Test',
        ]);

        $product = Product::where('name', 'Kaos Inventory Test')->latest('id')->first();

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'attribute_family_id' => $family->id,
            'type' => ProductType::Configurable->value,
            'sku' => $product->sku,
            'name' => $product->name,
            'price' => 150000,
            'track_stock' => true,
            'is_active' => true,
            'image' => UploadedFile::fake()->image('product.jpg'),
            'attributes' => [
                'size' => ['S', 'M'],
                'color' => [
                    ['hex' => '#000000', 'name' => 'Hitam'],
                ],
            ],
        ]);

        return $product->fresh();
    }
}
