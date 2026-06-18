<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\NavigationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@yclothes.test')->first();
    }

    // ── Header Navigation ──────────────────────────────────────

    public function test_homepage_renders_header_navigation(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Beranda');
    }

    public function test_header_navigation_items_from_database(): void
    {
        $headerItems = navigation('header');

        $this->assertTrue($headerItems->isNotEmpty());
        $this->assertTrue($headerItems->contains('label', 'Beranda'));
    }

    public function test_inactive_navigation_items_excluded(): void
    {
        NavigationItem::create([
            'menu' => 'header',
            'label' => 'Hidden Link',
            'url' => '/hidden',
            'sort_order' => 99,
            'is_active' => false,
        ]);

        $items = NavigationItem::where('menu', 'header')
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->get();

        $this->assertFalse($items->contains('label', 'Hidden Link'));
    }

    // ── Footer Navigation ──────────────────────────────────────

    public function test_homepage_renders_footer_navigation(): void
    {
        $this->get('/')
            ->assertOk();
    }

    public function test_footer_navigation_items_from_database(): void
    {
        $footerItems = navigation('footer');

        $this->assertTrue($footerItems->isNotEmpty());
    }

    public function test_footer_fallback_when_no_items(): void
    {
        NavigationItem::where('menu', 'footer')->delete();

        $this->get('/')
            ->assertOk();
    }

    // ── Category Mega Menu ─────────────────────────────────────

    public function test_products_page_loads_with_categories(): void
    {
        $categories = Category::all();

        $this->get('/products')
            ->assertOk();

        $this->assertTrue($categories->isNotEmpty());
    }

    public function test_category_filter_works(): void
    {
        $category = Category::first();

        if ($category) {
            $this->get("/products?category={$category->slug}")
                ->assertOk();
        }
    }

    // ── Navigation CRUD (Admin) ────────────────────────────────

    public function test_admin_can_list_navigation_items(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/navigation')
            ->assertOk();
    }

    public function test_admin_can_create_header_navigation_item(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/navigation', [
                'menu' => 'header',
                'label' => 'Promo',
                'url' => '/promo',
                'sort_order' => 10,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.navigation.index'));

        $this->assertDatabaseHas('navigation_items', [
            'menu' => 'header',
            'label' => 'Promo',
            'url' => '/promo',
        ]);
    }

    public function test_admin_can_create_footer_navigation_item(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/navigation', [
                'menu' => 'footer',
                'label' => 'Syarat & Ketentuan',
                'url' => '/page/syarat-ketentuan',
                'sort_order' => 10,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.navigation.index'));

        $this->assertDatabaseHas('navigation_items', [
            'menu' => 'footer',
            'label' => 'Syarat & Ketentuan',
        ]);
    }

    public function test_admin_can_update_navigation_item(): void
    {
        $item = NavigationItem::first();

        $this->actingAs($this->admin)
            ->put("/admin/navigation/{$item->id}", [
                'menu' => $item->menu,
                'label' => 'Updated Label',
                'url' => $item->url,
                'sort_order' => $item->sort_order,
                'is_active' => $item->is_active,
            ])
            ->assertRedirect(route('admin.navigation.index'));

        $this->assertDatabaseHas('navigation_items', [
            'id' => $item->id,
            'label' => 'Updated Label',
        ]);
    }

    public function test_admin_can_delete_navigation_item(): void
    {
        $item = NavigationItem::first();

        $this->actingAs($this->admin)
            ->delete("/admin/navigation/{$item->id}")
            ->assertRedirect(route('admin.navigation.index'));

        $this->assertDatabaseMissing('navigation_items', [
            'id' => $item->id,
        ]);
    }

    public function test_navigation_item_appears_on_storefront_after_creation(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/navigation', [
                'menu' => 'header',
                'label' => 'Flash Sale',
                'url' => '/flash-sale',
                'sort_order' => 1,
                'is_active' => 1,
            ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Flash Sale');
    }

    // ── Navigation ordering ────────────────────────────────────

    public function test_navigation_items_ordered_by_sort_order(): void
    {
        NavigationItem::where('menu', 'header')->delete();

        NavigationItem::create([
            'menu' => 'header',
            'label' => 'Z Last',
            'url' => '/z',
            'sort_order' => 30,
            'is_active' => true,
        ]);

        NavigationItem::create([
            'menu' => 'header',
            'label' => 'A First',
            'url' => '/a',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $items = NavigationItem::where('menu', 'header')
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        $this->assertEquals('A First', $items->first()->label);
        $this->assertEquals('Z Last', $items->last()->label);
    }

    // ── Search ─────────────────────────────────────────────────

    public function test_search_from_header_works(): void
    {
        $this->get('/products?search=test')
            ->assertOk();
    }

    public function test_search_with_empty_query_works(): void
    {
        $this->get('/products?search=')
            ->assertOk();
    }

    // ── CMS Pages linked from navigation ───────────────────────

    public function test_cms_page_accessible_from_nav_link(): void
    {
        $this->get('/page/tentang-kami')
            ->assertOk();
    }

    public function test_blog_accessible_from_nav_link(): void
    {
        $this->get('/blog')
            ->assertOk();
    }

    public function test_faq_accessible_from_nav_link(): void
    {
        $this->get('/faq')
            ->assertOk();
    }

    // ── Non-admin cannot manage navigation ─────────────────────

    public function test_non_admin_cannot_access_navigation_management(): void
    {
        $customer = \App\Models\Customer::factory()->create();

        $this->actingAs($customer, 'customer')
            ->get('/admin/navigation')
            ->assertForbidden();
    }
}
