<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_products_index_returns_success(): void
    {
        $response = $this->get('/products');
        $response->assertStatus(200);
    }

    public function test_products_index_displays_products(): void
    {
        $response = $this->get('/products');
        $response->assertSee(Product::first()->name);
    }

    public function test_products_index_filters_by_parent_category_includes_children(): void
    {
        $parent = Category::where('slug', 'pria')->first();
        $child = Category::where('slug', 'pria-kemeja')->first();

        if (! $parent || ! $child) {
            $this->markTestSkipped('Hierarchy seed data not available.');
        }

        Product::where('category_id', $child->id)->first()?->update(['name' => 'Produk Kemeja Filter Test']);

        $response = $this->get('/products?category='.$parent->slug);
        $response->assertStatus(200);
        $response->assertSee('Produk Kemeja Filter Test');
    }

    public function test_products_index_filters_by_category(): void
    {
        $category = Category::first();
        $response = $this->get('/products?category='.$category->slug);
        $response->assertStatus(200);
    }

    public function test_products_index_search_returns_results(): void
    {
        $product = Product::first();
        $search = substr($product->name, 0, 3);
        $response = $this->get('/products?search='.$search);
        $response->assertStatus(200);
    }

    public function test_product_detail_shows_product(): void
    {
        $product = Product::first();
        $response = $this->get('/products/'.$product->slug);
        $response->assertStatus(200);
        $response->assertSee($product->name);
    }

    public function test_product_detail_returns_404_for_invalid_slug(): void
    {
        $response = $this->get('/products/tidak-ada');
        $response->assertStatus(404);
    }

    public function test_products_index_supports_sorting(): void
    {
        $response = $this->get('/products?sort=price_asc');
        $response->assertStatus(200);

        $response = $this->get('/products?sort=price_desc');
        $response->assertStatus(200);
    }
}
