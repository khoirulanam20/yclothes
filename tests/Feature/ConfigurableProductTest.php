<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Models\AttributeFamily;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConfigurableProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_admin_can_create_configurable_product_with_variants(): void
    {
        $admin = User::where('is_admin', true)->first();
        $category = Category::first();

        $family = AttributeFamily::where('name', 'Fashion Default')->first();

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'type' => ProductType::Configurable->value,
            'attribute_family_id' => $family->id,
            'sku' => 'CFG-001',
            'name' => 'Kaos Configurable',
        ]);

        $product = Product::where('sku', 'CFG-001')->first();
        $response->assertRedirect(route('admin.products.edit', $product));

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'attribute_family_id' => $family->id,
            'type' => ProductType::Configurable->value,
            'sku' => 'CFG-001',
            'name' => 'Kaos Configurable',
            'price' => 150000,
            'is_active' => true,
            'image' => UploadedFile::fake()->image('product.jpg'),
            'attributes' => [
                'size' => ['S', 'M', 'L'],
                'color' => [
                    ['hex' => '#000000', 'name' => 'Hitam'],
                    ['hex' => '#FFFFFF', 'name' => 'Putih'],
                ],
            ],
        ]);

        $product->refresh();
        $this->assertNotNull($product);
        $this->assertTrue($product->isConfigurable());
        $this->assertEquals(6, $product->variants()->count());
    }

    public function test_cart_accepts_variant_id(): void
    {
        $product = $this->createConfigurableProduct();
        $variant = $product->variants()->first();

        $response = $this->postJson('/cart/add', [
            'variant_id' => $variant->id,
            'qty' => 2,
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $cart = session('cart');
        $this->assertArrayHasKey('variant-'.$variant->id, $cart);
        $this->assertEquals(2, $cart['variant-'.$variant->id]['qty']);
    }

    public function test_checkout_saves_variant_in_order_item(): void
    {
        $product = $this->createConfigurableProduct();
        $variant = $product->variants()->first();

        $this->postJson('/cart/add', ['variant_id' => $variant->id, 'qty' => 1]);

        $shipping = \App\Models\ShippingCost::first();
        $bank = \App\Models\PaymentBank::first();

        $this->post('/checkout/process', array_merge([
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'test@example.com',
            'shipping_address' => 'Jl. Test',
            'shipping_city' => $shipping->id,
            'payment_method' => 'bank_'.$bank->id,
        ], $this->checkoutWilayahFields()));

        $this->assertDatabaseHas('order_items', [
            'product_variant_id' => $variant->id,
            'sku' => $variant->sku,
        ]);
    }

    public function test_admin_can_upload_variant_gallery(): void
    {
        $admin = User::where('is_admin', true)->first();
        $product = $this->createConfigurableProduct();
        $variant = $product->variants()->first();

        $response = $this->actingAs($admin)->put(route('admin.products.variants.update', $product), [
            'variants' => [
                [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'new_images' => [
                        UploadedFile::fake()->image('variant-1.jpg'),
                        UploadedFile::fake()->image('variant-2.jpg'),
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();
        $variant->refresh();

        $this->assertCount(2, $variant->images);
        $this->assertSame($variant->images[0], $variant->image);
        Storage::disk('public')->assertExists($variant->images[0]);
        Storage::disk('public')->assertExists($variant->images[1]);
    }

    public function test_admin_can_remove_variant_gallery_image(): void
    {
        $admin = User::where('is_admin', true)->first();
        $product = $this->createConfigurableProduct();
        $variant = $product->variants()->first();

        $pathOne = UploadedFile::fake()->image('one.jpg')->store('products/variants/gallery', 'public');
        $pathTwo = UploadedFile::fake()->image('two.jpg')->store('products/variants/gallery', 'public');
        $variant->update([
            'images' => [$pathOne, $pathTwo],
            'image' => $pathOne,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.products.variants.update', $product), [
            'variants' => [
                [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'existing_images' => [$pathOne],
                    'remove_images' => [$pathTwo],
                ],
            ],
        ]);

        $response->assertRedirect();
        $variant->refresh();

        $this->assertSame([$pathOne], $variant->images);
        Storage::disk('public')->assertMissing($pathTwo);
    }

    public function test_admin_can_append_second_variant_gallery_image(): void
    {
        $admin = User::where('is_admin', true)->first();
        $product = $this->createConfigurableProduct();
        $variant = $product->variants()->first();

        $pathOne = UploadedFile::fake()->image('one.jpg')->store('products/variants/gallery', 'public');
        $variant->update([
            'images' => [$pathOne],
            'image' => $pathOne,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.products.variants.update', $product), [
            'variants' => [
                [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'existing_images' => [$pathOne],
                    'new_images' => [
                        UploadedFile::fake()->image('two.jpg'),
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();
        $variant->refresh();

        $this->assertCount(2, $variant->images);
        $this->assertSame($pathOne, $variant->images[0]);
        Storage::disk('public')->assertExists($variant->images[1]);
    }

    public function test_storefront_includes_variant_gallery_urls(): void
    {
        $product = $this->createConfigurableProduct();
        $variant = $product->variants()->first();
        $variant->update([
            'images' => ['products/variants/gallery/a.jpg', 'products/variants/gallery/b.jpg'],
            'image' => 'products/variants/gallery/a.jpg',
        ]);

        Storage::disk('public')->put('products/variants/gallery/a.jpg', 'a');
        Storage::disk('public')->put('products/variants/gallery/b.jpg', 'b');

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Guest/Products/Show')
            ->has('variants', 4)
            ->where('variants.0.imagesUrl', fn ($urls) => count($urls) === 2)
        );
    }

    public function test_legacy_variant_single_image_exposes_gallery_urls(): void
    {
        $product = $this->createConfigurableProduct();
        $variant = $product->variants()->first();
        $variant->update([
            'image' => 'products/variants/legacy.jpg',
            'images' => null,
        ]);

        Storage::disk('public')->put('products/variants/legacy.jpg', 'legacy');

        $variant->refresh();

        $this->assertSame(['products/variants/legacy.jpg'], $variant->resolved_image_paths);
        $this->assertCount(1, $variant->images_url);
        $this->assertCount(1, $variant->own_images_url);
    }

    public function test_variant_images_not_wiped_when_save_without_image_changes(): void
    {
        $admin = User::where('is_admin', true)->first();
        $product = $this->createConfigurableProduct();
        $variant = $product->variants()->first();

        $path = UploadedFile::fake()->image('keep.jpg')->store('products/variants/gallery', 'public');
        $variant->update([
            'images' => [$path],
            'image' => $path,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.products.variants.update', $product), [
            'variants' => [
                [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'price' => 120000,
                ],
            ],
        ]);

        $response->assertRedirect();
        $variant->refresh();

        $this->assertSame([$path], $variant->images);
        Storage::disk('public')->assertExists($path);
    }

    public function test_storefront_variant_includes_own_images_stock_and_purchasability(): void
    {
        $product = $this->createConfigurableProduct();
        $product->update(['is_active' => true]);
        $variant = $product->variants()->first();
        $variant->update([
            'images' => ['products/variants/gallery/a.jpg'],
            'image' => 'products/variants/gallery/a.jpg',
            'price' => 150000,
        ]);

        Storage::disk('public')->put('products/variants/gallery/a.jpg', 'a');

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Guest/Products/Show')
            ->where('variants.0.ownImagesUrl', fn ($urls) => count($urls) === 1)
            ->where('variants.0.finalPrice', 150000)
            ->has('variants.0.stock')
            ->has('variants.0.isPurchasable')
            ->has('variants.0.isOutOfStock')
        );
    }

    public function test_configurable_product_not_out_of_stock_when_one_variant_has_stock(): void
    {
        $product = $this->createConfigurableProduct();
        $variants = $product->variants()->orderBy('id')->get();

        $this->assertGreaterThanOrEqual(2, $variants->count());

        $variants[0]->update(['stock' => 0]);
        $variants[1]->update(['stock' => 5]);

        $inventory = app(\App\Services\InventoryService::class);
        $freshProduct = $product->fresh(['activeVariants']);

        $this->assertFalse($inventory->isOutOfStock($freshProduct));
        $this->assertTrue($inventory->canOrder($freshProduct, null, 1));

        $serialized = \App\Support\ModelSerializer::product($freshProduct);

        $this->assertFalse($serialized['isOutOfStock']);
        $this->assertTrue($serialized['isPurchasable']);
    }

    private function createConfigurableProduct(): Product
    {
        $category = Category::first();

        $product = Product::create([
            'category_id' => $category->id,
            'type' => ProductType::Configurable,
            'name' => 'Test Config Product',
            'slug' => 'test-config-product',
            'price' => 100000,
            'image' => 'products/test.jpg',
            'sizes' => ['S', 'M'],
            'colors' => [
                ['hex' => '#000000', 'name' => 'Hitam'],
                ['hex' => '#FFFFFF', 'name' => 'Putih'],
            ],
        ]);

        app(\App\Services\ProductVariantService::class)->syncFromProduct($product);

        return $product->fresh(['variants']);
    }
}
