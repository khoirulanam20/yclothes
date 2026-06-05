<?php

namespace Tests\Feature;

use App\Enums\ProductType;
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

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'type' => ProductType::Configurable->value,
            'name' => 'Kaos Configurable',
            'price' => 150000,
            'sizes' => 'S,M,L',
            'colors' => "#000000|Hitam\n#FFFFFF|Putih",
            'image' => UploadedFile::fake()->image('product.jpg'),
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Kaos Configurable')->first();
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
