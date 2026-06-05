<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Models\AttributeFamily;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductCreateFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
        $this->admin = User::where('email', 'admin@yclothes.test')->first();
    }

    public function test_minimal_create_redirects_to_edit(): void
    {
        $family = AttributeFamily::where('name', 'Fashion Default')->first();

        $response = $this->actingAs($this->admin)->post('/admin/products', [
            'type' => ProductType::Simple->value,
            'attribute_family_id' => $family->id,
            'sku' => 'TEST-SKU-001',
            'name' => 'Produk Draft',
        ]);

        $product = Product::where('sku', 'TEST-SKU-001')->first();
        $this->assertNotNull($product);
        $this->assertFalse($product->is_active);

        $response->assertRedirect(route('admin.products.edit', $product));
    }

    public function test_full_update_with_special_price_dates(): void
    {
        $family = AttributeFamily::where('name', 'Fashion Default')->first();
        $category = Category::first();

        $product = Product::create([
            'category_id' => $category->id,
            'attribute_family_id' => $family->id,
            'type' => ProductType::Simple,
            'sku' => 'SPECIAL-001',
            'name' => 'Produk Spesial',
            'slug' => 'produk-spesial',
            'price' => 200000,
            'image' => '',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)->put("/admin/products/{$product->id}", [
            'category_id' => $category->id,
            'attribute_family_id' => $family->id,
            'type' => ProductType::Simple->value,
            'sku' => 'SPECIAL-001',
            'name' => 'Produk Spesial',
            'price' => 200000,
            'sale_price' => 150000,
            'sale_price_starts_at' => now()->subDay()->toDateTimeString(),
            'sale_price_ends_at' => now()->addDay()->toDateTimeString(),
            'short_description' => 'Ringkas',
            'description' => '<p>Detail</p>',
            'is_active' => true,
            'image' => UploadedFile::fake()->image('p.jpg'),
        ])->assertRedirect(route('admin.products.edit', $product));

        $product->refresh();
        $this->assertTrue($product->is_active);
        $this->assertEquals(150000, $product->final_price);
        $this->assertTrue($product->isSalePriceActive());
    }
}
