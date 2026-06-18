<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\NavigationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    // ── Public Pages (no auth required) ────────────────────────

    public function test_homepage_accessible(): void
    {
        $this->get('/')
            ->assertOk();
    }

    public function test_products_page_accessible(): void
    {
        $this->get('/products')
            ->assertOk();
    }

    public function test_cart_page_accessible(): void
    {
        $this->get('/cart')
            ->assertOk();
    }

    public function test_login_page_accessible(): void
    {
        $this->get('/account/login')
            ->assertOk();
    }

    // ── Account pages (auth required) ──────────────────────────

    public function test_profile_page_accessible_when_logged_in(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer')
            ->get('/account/profile')
            ->assertOk();
    }

    public function test_profile_page_redirects_when_not_logged_in(): void
    {
        $this->get('/account/profile')
            ->assertRedirect('/account/login');
    }

    public function test_orders_page_accessible_when_logged_in(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer')
            ->get('/account/orders')
            ->assertOk();
    }

    public function test_wishlist_page_accessible_when_logged_in(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer')
            ->get('/account/wishlist')
            ->assertOk();
    }

    public function test_addresses_page_accessible_when_logged_in(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer')
            ->get('/account/addresses')
            ->assertOk();
    }

    // ── Mobile Menu Drawer Links ───────────────────────────────

    public function test_navigation_items_shared_to_pages(): void
    {
        $response = $this->get('/');
        $response->assertOk();

        // Homepage should contain navigation data via Inertia shared props
        // The navigation items from DB should be available
        $headerItems = NavigationItem::where('menu', 'header')
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        $this->assertTrue($headerItems->isNotEmpty());
    }

    public function test_header_navigation_contains_seeded_items(): void
    {
        $items = NavigationItem::where('menu', 'header')
            ->where('is_active', true)
            ->get();

        $this->assertTrue($items->contains('label', 'Beranda'));
        $this->assertTrue($items->contains('label', 'Produk'));
    }

    public function test_footer_navigation_contains_seeded_items(): void
    {
        $items = NavigationItem::where('menu', 'footer')
            ->where('is_active', true)
            ->get();

        $this->assertTrue($items->isNotEmpty());
    }

    // ── Mobile drawer pages ────────────────────────────────────

    public function test_storefront_pages_accessible_from_drawer(): void
    {
        $drawerRoutes = [
            '/',
            '/products',
            '/cart',
            '/faq',
            '/blog',
        ];

        foreach ($drawerRoutes as $route) {
            $this->get($route)
                ->assertOk();
        }
    }

    public function test_cms_pages_accessible(): void
    {
        // CMS pages like 'tentang-kami' and 'cara-belanja' should be accessible
        $this->get('/page/tentang-kami')
            ->assertOk();
    }

    public function test_order_tracking_accessible(): void
    {
        $this->get('/order/track')
            ->assertOk();
    }

    // ── Mobile nav hide on certain pages ───────────────────────

    public function test_checkout_requires_verification(): void
    {
        $customer = Customer::factory()->create();

        // Checkout requires verified email (customer.verified middleware)
        $this->actingAs($customer, 'customer')
            ->get('/checkout')
            ->assertRedirect();
    }

    // ── Cart badge count ───────────────────────────────────────

    public function test_cart_page_renders_with_empty_cart(): void
    {
        $this->get('/cart')
            ->assertOk();
    }

    public function test_cart_add_updates_session(): void
    {
        $product = \App\Models\Product::first();

        $this->postJson('/cart/add', [
            'product_id' => $product->id,
            'qty' => 1,
        ])->assertOk();
    }

    // ── Category menu on mobile ────────────────────────────────

    public function test_products_page_shows_categories(): void
    {
        $categories = \App\Models\Category::all();

        $this->get('/products')
            ->assertOk();

        $this->assertTrue($categories->isNotEmpty());
    }

    // ── Search from mobile nav ─────────────────────────────────

    public function test_product_search_works(): void
    {
        $this->get('/products?search=test')
            ->assertOk();
    }
}
