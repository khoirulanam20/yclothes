<?php

namespace Tests\Feature;

use App\Models\AttributeFamily;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\User;
use App\Services\ProductRelationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRelationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_sync_all_relation_types(): void
    {
        $admin = User::where('is_admin', true)->first();
        $product = $this->createProduct('main-product');
        $related = $this->createProduct('related-product');
        $upSell = $this->createProduct('upsell-product');
        $crossSell = $this->createProduct('cross-product');

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $product->category_id,
            'attribute_family_id' => $product->attribute_family_id,
            'type' => $product->type->value,
            'sku' => $product->sku,
            'name' => $product->name,
            'price' => $product->price,
            'related_products' => [$related->id],
            'up_sell_products' => [$upSell->id],
            'cross_sell_products' => [$crossSell->id],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('product_relations', [
            'product_id' => $product->id,
            'related_product_id' => $related->id,
            'type' => 'related',
        ]);
        $this->assertDatabaseHas('product_relations', [
            'product_id' => $product->id,
            'related_product_id' => $upSell->id,
            'type' => 'up_sell',
        ]);
        $this->assertDatabaseHas('product_relations', [
            'product_id' => $product->id,
            'related_product_id' => $crossSell->id,
            'type' => 'cross_sell',
        ]);
    }

    public function test_storefront_includes_up_sell_products(): void
    {
        $product = $this->createProduct('show-product', true);
        $upSell = $this->createProduct('show-upsell', true);

        ProductRelation::create([
            'product_id' => $product->id,
            'related_product_id' => $upSell->id,
            'type' => ProductRelationService::TYPE_UP_SELL,
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Guest/Products/Show')
            ->has('upSellProducts', 1)
            ->where('upSellProducts.0.id', $upSell->id)
        );
    }

    public function test_cart_includes_cross_sell_products(): void
    {
        $product = $this->createProduct('cart-product', true);
        $crossSell = $this->createProduct('cart-cross', true);

        ProductRelation::create([
            'product_id' => $product->id,
            'related_product_id' => $crossSell->id,
            'type' => ProductRelationService::TYPE_CROSS_SELL,
        ]);

        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $response = $this->get(route('cart.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Guest/Cart/Index')
            ->has('crossSellProducts', 1)
            ->where('crossSellProducts.0.id', $crossSell->id)
        );
    }

    private function createProduct(string $slug, bool $active = false): Product
    {
        $category = Category::first();
        $family = AttributeFamily::first();

        return Product::create([
            'category_id' => $category->id,
            'attribute_family_id' => $family->id,
            'type' => 'simple',
            'sku' => strtoupper(str_replace('-', '_', $slug)),
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'price' => 100000,
            'image' => 'products/test.jpg',
            'is_active' => $active,
            'track_stock' => false,
        ]);
    }
}
