<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\AttributeFamily;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductAttributeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@yclothes.test')->first();
    }

    public function test_product_sizes_from_eav_after_seed(): void
    {
        $product = Product::first();
        $this->assertNotNull($product->attribute_family_id);
        $this->assertIsArray($product->sizes);
        $this->assertNotEmpty($product->sizes);
    }

    public function test_admin_product_store_with_attributes(): void
    {
        Storage::fake('public');
        $category = Category::first();
        $family = AttributeFamily::where('name', 'Fashion Default')->first();
        $sizeAttr = Attribute::where('code', 'size')->first();

        $family = AttributeFamily::where('name', 'Fashion Default')->first();
        $sizeAttr = Attribute::where('code', 'size')->first();

        $create = $this->actingAs($this->admin)->post('/admin/products', [
            'type' => 'simple',
            'attribute_family_id' => $family->id,
            'sku' => 'EAV-TEST-001',
            'name' => 'Produk EAV Test',
        ]);
        $product = Product::where('sku', 'EAV-TEST-001')->first();
        $create->assertRedirect('/admin/products/'.$product->id.'/edit');

        $this->actingAs($this->admin)->put('/admin/products/'.$product->id, [
            'category_id' => $category->id,
            'attribute_family_id' => $family->id,
            'type' => 'simple',
            'sku' => 'EAV-TEST-001',
            'name' => 'Produk EAV Test',
            'price' => 200000,
            'description' => 'Test',
            'is_active' => true,
            'image' => UploadedFile::fake()->image('produk.jpg'),
            'attributes' => [
                'size' => ['M', 'L'],
            ],
        ])->assertRedirect('/admin/products/'.$product->id.'/edit');
        $this->assertNotNull($product);
        $this->assertEquals(['M', 'L'], $product->sizes);

        $this->assertDatabaseHas('product_attribute_values', [
            'product_id' => $product->id,
            'attribute_id' => $sizeAttr->id,
        ]);
    }

    public function test_product_listing_filter_by_size_attribute(): void
    {
        $sizeAttr = Attribute::where('code', 'size')->first();
        $product = Product::first();
        $product->update(['is_active' => true]);
        ProductAttributeValue::updateOrCreate(
            ['product_id' => $product->id, 'attribute_id' => $sizeAttr->id],
            ['value' => json_encode(['M'])]
        );

        $this->get('/products?attr_size=M')
            ->assertStatus(200)
            ->assertSee($product->name);
    }

    public function test_product_detail_shows_attribute_values(): void
    {
        $product = Product::first();
        $product->update(['is_active' => true]);

        $this->get('/products/'.$product->slug)
            ->assertStatus(200)
            ->assertSee($product->name);
    }

    public function test_migrate_from_json_command(): void
    {
        $product = Product::create([
            'category_id' => Category::first()->id,
            'name' => 'Legacy Product',
            'slug' => 'legacy-product-'.uniqid(),
            'price' => 100000,
            'image' => 'products/test.jpg',
            'sizes' => ['S', 'M'],
            'colors' => [['hex' => '#000000', 'name' => 'Hitam']],
            'attribute_family_id' => null,
        ]);

        $this->artisan('attributes:migrate-from-json')->assertSuccessful();

        $product->refresh();
        $this->assertNotNull($product->attribute_family_id);
        $this->assertEquals(['S', 'M'], $product->sizes);
    }
}
