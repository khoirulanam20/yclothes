<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_featured_filter_returns_only_featured_products(): void
    {
        Product::query()->delete();
        $category = Category::first();

        Product::factory()->create([
            'category_id' => $category->id,
            'image' => 'products/featured.jpg',
            'is_active' => true,
            'is_featured' => true,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'image' => 'products/regular.jpg',
            'is_active' => true,
            'is_featured' => false,
        ]);

        $this->get('/products?featured=1')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.featured', '1')
                ->where('pageTitle', 'Produk Unggulan')
                ->has('products.data', 1)
            );
    }

    public function test_on_sale_filter_returns_only_discounted_products(): void
    {
        Product::query()->delete();
        $category = Category::first();

        Product::factory()->create([
            'category_id' => $category->id,
            'image' => 'products/sale.jpg',
            'is_active' => true,
            'price' => 200_000,
            'sale_price' => 150_000,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'image' => 'products/full.jpg',
            'is_active' => true,
            'price' => 200_000,
            'sale_price' => null,
        ]);

        $this->get('/products?on_sale=1')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.on_sale', '1')
                ->where('pageTitle', 'Produk Sale')
                ->has('products.data', 1)
            );
    }

    public function test_badge_label_filter_ignores_overlong_values(): void
    {
        $category = Category::first();

        Product::factory()->create([
            'category_id' => $category->id,
            'image' => 'products/custom.jpg',
            'is_active' => true,
            'badge_preset' => 'custom',
            'badge' => 'Limited Edition',
        ]);

        $this->get('/products?badge_label='.str_repeat('a', 60))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.badge_label', null)
            );
    }

    public function test_category_filter_preserves_badge_label_query(): void
    {
        $category = Category::first();

        Product::factory()->create([
            'category_id' => $category->id,
            'image' => 'products/custom.jpg',
            'is_active' => true,
            'badge_preset' => 'custom',
            'badge' => 'Limited Edition',
        ]);

        $this->get('/products?badge_label=Limited%20Edition&category='.$category->slug.'&sort=price_asc')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.badge_label', 'Limited Edition')
                ->where('filters.category', $category->slug)
                ->where('filters.sort', 'price_asc')
            );
    }
}
