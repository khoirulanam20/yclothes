<?php

namespace Tests\Feature;

use App\Enums\AttributeType;
use App\Enums\ProductType;
use App\Models\Attribute;
use App\Models\AttributeFamily;
use App\Models\AttributeOption;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\ProductAttributeValue;
use App\Services\ProductVariantService;
use App\Support\ModelSerializer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DynamicVariantAttributeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_configurable_product_generates_variants_from_custom_multiselect_axis(): void
    {
        $admin = User::where('is_admin', true)->first();
        $category = Category::first();

        $berat = Attribute::create([
            'code' => 'berat',
            'name' => 'Berat',
            'type' => AttributeType::Multiselect,
            'sort_order' => 10,
        ]);
        foreach (['400 gr', '350 gr', '300 gr'] as $i => $name) {
            AttributeOption::create([
                'attribute_id' => $berat->id,
                'name' => $name,
                'sort_order' => $i,
            ]);
        }

        $family = AttributeFamily::create(['name' => 'Makanan']);
        $family->attributes()->sync([
            $berat->id => ['is_variant_axis' => true],
        ]);

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'type' => ProductType::Configurable->value,
            'attribute_family_id' => $family->id,
            'sku' => 'FOOD-001',
            'name' => 'Chili Oil',
        ]);

        $product = Product::where('sku', 'FOOD-001')->first();

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'attribute_family_id' => $family->id,
            'type' => ProductType::Configurable->value,
            'sku' => 'FOOD-001',
            'name' => 'Chili Oil',
            'price' => 100000,
            'is_active' => true,
            'image' => UploadedFile::fake()->image('chili.jpg'),
            'attributes' => [
                'berat' => ['400 gr', '350 gr', '300 gr'],
            ],
        ]);

        $product->refresh();
        $variants = $product->variants()->orderBy('sku')->get();

        $this->assertCount(3, $variants);
        $this->assertEquals(
            ['300-gr', '350-gr', '400-gr'],
            $variants->map(fn ($variant) => str_replace('chili-oil-', '', $variant->sku))->sort()->values()->all()
        );

        $first = $variants->first();
        $this->assertEquals('300 gr', $first->attributes['berat'] ?? null);
        $this->assertEquals('Chili Oil - 300 gr', $first->name);
        $this->assertEquals('300 gr', ModelSerializer::variantLabel($first));
    }

    public function test_variant_service_cartesian_product_supports_multiple_axes(): void
    {
        $service = app(ProductVariantService::class);

        $combinations = $service->cartesianProduct([
            ['code' => 'size', 'values' => ['S', 'M']],
            ['code' => 'color', 'values' => [
                ['name' => 'Hitam', 'hex' => '#000'],
                ['name' => 'Putih', 'hex' => '#fff'],
            ]],
        ]);

        $this->assertCount(4, $combinations);
    }

    public function test_changing_variant_axes_replaces_old_variants(): void
    {
        $admin = User::where('is_admin', true)->first();
        $category = Category::first();
        $family = AttributeFamily::where('name', 'Fashion Default')->first();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'type' => ProductType::Configurable->value,
            'attribute_family_id' => $family->id,
            'sku' => 'REPLACE-001',
            'name' => 'Produk Replace',
        ]);

        $product = Product::where('sku', 'REPLACE-001')->first();

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'attribute_family_id' => $family->id,
            'type' => ProductType::Configurable->value,
            'sku' => 'REPLACE-001',
            'name' => 'Produk Replace',
            'price' => 100000,
            'is_active' => true,
            'image' => UploadedFile::fake()->image('product.jpg'),
            'attributes' => [
                'size' => ['S', 'M'],
                'color' => [
                    ['hex' => '#000000', 'name' => 'Hitam'],
                ],
            ],
        ]);

        $product->refresh();
        $this->assertCount(2, $product->variants);
        $oldIds = $product->variants()->pluck('id')->all();

        $berat = Attribute::create([
            'code' => 'berat',
            'name' => 'Berat',
            'type' => AttributeType::Multiselect,
            'sort_order' => 99,
        ]);
        AttributeOption::create(['attribute_id' => $berat->id, 'name' => '400 gr', 'sort_order' => 0]);
        AttributeOption::create(['attribute_id' => $berat->id, 'name' => '350 gr', 'sort_order' => 1]);

        $foodFamily = AttributeFamily::create(['name' => 'Makanan Replace']);
        $foodFamily->attributes()->sync([
            $berat->id => ['is_variant_axis' => true],
        ]);

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'attribute_family_id' => $foodFamily->id,
            'type' => ProductType::Configurable->value,
            'sku' => 'REPLACE-001',
            'name' => 'Produk Replace',
            'price' => 100000,
            'is_active' => true,
            'attributes' => [
                'berat' => ['400 gr', '350 gr'],
            ],
        ]);

        $response->assertRedirect(route('admin.products.edit', $product));
        $response->assertSessionHas('warning');

        $product->refresh();
        $variants = $product->variants()->get();

        $this->assertCount(2, $variants);
        $this->assertEmpty(array_intersect($oldIds, $variants->pluck('id')->all()));
        $this->assertEquals(['350 gr', '400 gr'], $variants->pluck('attributes')->map(fn ($attrs) => $attrs['berat'] ?? null)->sort()->values()->all());
    }

    public function test_stale_multiselect_values_are_dropped_and_variants_replaced(): void
    {
        $admin = User::where('is_admin', true)->first();
        $category = Category::first();

        $kemasan = Attribute::create([
            'code' => 'kemasan',
            'name' => 'Kemasan',
            'type' => AttributeType::Multiselect,
            'sort_order' => 30,
        ]);
        AttributeOption::create(['attribute_id' => $kemasan->id, 'name' => '100 gr', 'sort_order' => 0]);
        AttributeOption::create(['attribute_id' => $kemasan->id, 'name' => '200 gr', 'sort_order' => 1]);

        $berat = Attribute::create([
            'code' => 'berat',
            'name' => 'Berat',
            'type' => AttributeType::Multiselect,
            'sort_order' => 31,
        ]);
        AttributeOption::create(['attribute_id' => $berat->id, 'name' => '400 gr', 'sort_order' => 0]);

        $family = AttributeFamily::create(['name' => 'Chili Pack']);
        $family->attributes()->sync([
            $kemasan->id => ['is_variant_axis' => true],
            $berat->id => ['is_variant_axis' => true],
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'attribute_family_id' => $family->id,
            'type' => ProductType::Configurable,
            'sku' => 'STALE-001',
            'name' => 'Chili Oil',
            'slug' => 'chili-oil-stale',
            'price' => 100000,
            'image' => 'products/test.jpg',
        ]);

        ProductAttributeValue::create([
            'product_id' => $product->id,
            'attribute_id' => $kemasan->id,
            'value' => json_encode(['100', '200']),
        ]);
        ProductAttributeValue::create([
            'product_id' => $product->id,
            'attribute_id' => $berat->id,
            'value' => json_encode(['400 gr']),
        ]);

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'attribute_family_id' => $family->id,
            'type' => ProductType::Configurable->value,
            'sku' => 'STALE-001',
            'name' => 'Chili Oil',
            'price' => 100000,
            'is_active' => true,
            'attributes' => [
                'kemasan' => ['100 gr', '200 gr'],
                'berat' => ['400 gr'],
            ],
        ])->assertRedirect(route('admin.products.edit', $product));

        $product->refresh();
        $labels = $product->variants()->get()->map(fn ($v) => ModelSerializer::variantLabel($v))->sort()->values()->all();

        $this->assertCount(2, $labels);
        foreach ($labels as $label) {
            $this->assertStringContainsString('400 gr', $label);
            $this->assertTrue(
                str_contains($label, '100 gr') || str_contains($label, '200 gr'),
            );
        }
        $this->assertFalse($product->variants()->where('attributes->kemasan', '100')->exists());
        $this->assertFalse($product->variants()->where('attributes->kemasan', '200')->exists());
        $this->assertDatabaseMissing('product_attribute_values', [
            'product_id' => $product->id,
            'attribute_id' => $kemasan->id,
            'value' => json_encode(['100', '200']),
        ]);
    }

    public function test_attribute_family_can_toggle_variant_axis_per_family(): void
    {
        $admin = User::where('is_admin', true)->first();
        $berat = Attribute::create([
            'code' => 'kemasan',
            'name' => 'Kemasan',
            'type' => AttributeType::Multiselect,
            'sort_order' => 20,
        ]);

        $this->actingAs($admin)->post('/admin/attribute-families', [
            'name' => 'Kemasan Only',
            'attribute_ids' => [$berat->id],
            'variant_axis_ids' => [$berat->id],
        ])->assertRedirect('/admin/attribute-families');

        $family = AttributeFamily::where('name', 'Kemasan Only')->first();
        $pivot = $family->attributes()->where('attributes.id', $berat->id)->first();

        $this->assertTrue((bool) $pivot->pivot->is_variant_axis);
    }
}
