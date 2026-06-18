<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminMenuTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->superAdmin = User::where('email', 'admin@yclothes.test')->first();
    }

    // ── Dashboard ──────────────────────────────────────────────

    public function test_dashboard_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin')
            ->assertOk();
    }

    // ── Penjualan Group ────────────────────────────────────────

    public function test_orders_accessible_with_permission(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/orders')
            ->assertOk();
    }

    public function test_orders_blocked_without_permission(): void
    {
        $staff = $this->createStaffWithPermissions(['products.manage']);

        $this->actingAs($staff)
            ->get('/admin/orders')
            ->assertForbidden();
    }

    public function test_returns_accessible_with_orders_manage(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/returns')
            ->assertOk();
    }

    public function test_returns_blocked_without_permission(): void
    {
        $staff = $this->createStaffWithPermissions(['products.view']);

        $this->actingAs($staff)
            ->get('/admin/returns')
            ->assertForbidden();
    }

    public function test_reviews_accessible_with_products_view(): void
    {
        $staff = $this->createStaffWithPermissions(['products.view']);

        $this->actingAs($staff)
            ->get('/admin/reviews')
            ->assertOk();
    }

    public function test_reviews_blocked_without_permission(): void
    {
        $staff = $this->createStaffWithPermissions(['orders.view']);

        $this->actingAs($staff)
            ->get('/admin/reviews')
            ->assertForbidden();
    }

    // ── Katalog Group ──────────────────────────────────────────

    public function test_products_accessible_with_products_manage(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/products')
            ->assertOk();
    }

    public function test_products_blocked_without_permission(): void
    {
        $staff = $this->createStaffWithPermissions(['orders.view']);

        $this->actingAs($staff)
            ->get('/admin/products')
            ->assertForbidden();
    }

    public function test_categories_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/categories')
            ->assertOk();
    }

    public function test_categories_blocked_without_permission(): void
    {
        $staff = $this->createStaffWithPermissions(['orders.view']);

        $this->actingAs($staff)
            ->get('/admin/categories')
            ->assertForbidden();
    }

    public function test_attributes_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/attributes')
            ->assertOk();
    }

    public function test_attribute_families_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/attribute-families')
            ->assertOk();
    }

    // ── CMS Group ──────────────────────────────────────────────

    public function test_cms_pages_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/cms-pages')
            ->assertOk();
    }

    public function test_cms_pages_blocked_without_permission(): void
    {
        $staff = $this->createStaffWithPermissions(['orders.view']);

        $this->actingAs($staff)
            ->get('/admin/cms-pages')
            ->assertForbidden();
    }

    public function test_blog_posts_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/blog-posts')
            ->assertOk();
    }

    public function test_navigation_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/navigation')
            ->assertOk();
    }

    public function test_faq_categories_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/faq-categories')
            ->assertOk();
    }

    // ── Inventory Group ────────────────────────────────────────

    public function test_inventories_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/inventories')
            ->assertOk();
    }

    public function test_inventories_blocked_without_permission(): void
    {
        $staff = $this->createStaffWithPermissions(['orders.view']);

        $this->actingAs($staff)
            ->get('/admin/inventories')
            ->assertForbidden();
    }

    public function test_warehouses_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/warehouses')
            ->assertOk();
    }

    public function test_stock_movements_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/stock-movements')
            ->assertOk();
    }

    // ── Promosi Group ──────────────────────────────────────────

    public function test_cart_rules_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/cart-rules')
            ->assertOk();
    }

    public function test_cart_rules_blocked_without_permission(): void
    {
        $staff = $this->createStaffWithPermissions(['orders.view']);

        $this->actingAs($staff)
            ->get('/admin/cart-rules')
            ->assertForbidden();
    }

    public function test_catalog_rules_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/catalog-rules')
            ->assertOk();
    }

    public function test_promotion_popups_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/promotion-popups')
            ->assertOk();
    }

    // ── Konfigurasi Group ──────────────────────────────────────

    public function test_configuration_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/configuration')
            ->assertOk();
    }

    public function test_configuration_blocked_without_permission(): void
    {
        $staff = $this->createStaffWithPermissions(['orders.view']);

        $this->actingAs($staff)
            ->get('/admin/configuration')
            ->assertForbidden();
    }

    // ── Pengaturan Group ───────────────────────────────────────

    public function test_settings_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/settings')
            ->assertOk();
    }

    public function test_settings_blocked_without_permission(): void
    {
        $staff = $this->createStaffWithPermissions(['orders.view']);

        $this->actingAs($staff)
            ->get('/admin/settings')
            ->assertForbidden();
    }

    public function test_roles_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/roles')
            ->assertOk();
    }

    public function test_roles_blocked_without_permission(): void
    {
        $staff = $this->createStaffWithPermissions(['orders.view']);

        $this->actingAs($staff)
            ->get('/admin/roles')
            ->assertForbidden();
    }

    public function test_staff_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/staff')
            ->assertOk();
    }

    public function test_activity_logs_accessible(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/activity-logs')
            ->assertOk();
    }

    // ── Auth Guard ─────────────────────────────────────────────

    public function test_all_admin_menu_routes_require_auth(): void
    {
        $routes = [
            '/admin',
            '/admin/orders',
            '/admin/returns',
            '/admin/reviews',
            '/admin/products',
            '/admin/categories',
            '/admin/attributes',
            '/admin/attribute-families',
            '/admin/cms-pages',
            '/admin/blog-posts',
            '/admin/navigation',
            '/admin/faq-categories',
            '/admin/inventories',
            '/admin/warehouses',
            '/admin/stock-movements',
            '/admin/cart-rules',
            '/admin/catalog-rules',
            '/admin/promotion-popups',
            '/admin/configuration',
            '/admin/settings',
            '/admin/roles',
            '/admin/staff',
            '/admin/activity-logs',
        ];

        foreach ($routes as $route) {
            $this->get($route)
                ->assertRedirect('/admin/login', "Route {$route} should require auth");
        }
    }

    public function test_customer_guard_cannot_access_admin(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer')
            ->get('/admin')
            ->assertForbidden();
    }

    // ── Group-based permission tests ───────────────────────────

    public function test_staff_with_cms_manage_can_access_cms_group(): void
    {
        $staff = $this->createStaffWithPermissions(['cms.manage']);

        $this->actingAs($staff)->get('/admin/cms-pages')->assertOk();
        $this->actingAs($staff)->get('/admin/blog-posts')->assertOk();
        $this->actingAs($staff)->get('/admin/navigation')->assertOk();
        $this->actingAs($staff)->get('/admin/faq-categories')->assertOk();
    }

    public function test_staff_with_inventory_manage_can_access_inventory_group(): void
    {
        $staff = $this->createStaffWithPermissions(['inventory.manage']);

        $this->actingAs($staff)->get('/admin/inventories')->assertOk();
        $this->actingAs($staff)->get('/admin/warehouses')->assertOk();
        $this->actingAs($staff)->get('/admin/stock-movements')->assertOk();
    }

    public function test_staff_with_promotions_manage_can_access_promo_group(): void
    {
        $staff = $this->createStaffWithPermissions(['promotions.manage']);

        $this->actingAs($staff)->get('/admin/cart-rules')->assertOk();
        $this->actingAs($staff)->get('/admin/catalog-rules')->assertOk();
        $this->actingAs($staff)->get('/admin/promotion-popups')->assertOk();
    }

    public function test_staff_with_settings_manage_can_access_config_and_settings(): void
    {
        $staff = $this->createStaffWithPermissions(['settings.manage']);

        $this->actingAs($staff)->get('/admin/configuration')->assertOk();
        $this->actingAs($staff)->get('/admin/settings')->assertOk();
    }

    public function test_staff_with_staff_manage_can_access_staff_group(): void
    {
        $staff = $this->createStaffWithPermissions(['staff.manage']);

        $this->actingAs($staff)->get('/admin/roles')->assertOk();
        $this->actingAs($staff)->get('/admin/staff')->assertOk();
        $this->actingAs($staff)->get('/admin/activity-logs')->assertOk();
    }

    public function test_staff_with_products_manage_can_access_catalog_group(): void
    {
        $staff = $this->createStaffWithPermissions(['products.manage']);

        $this->actingAs($staff)->get('/admin/products')->assertOk();
        $this->actingAs($staff)->get('/admin/categories')->assertOk();
        $this->actingAs($staff)->get('/admin/attributes')->assertOk();
        $this->actingAs($staff)->get('/admin/attribute-families')->assertOk();
        $this->actingAs($staff)->get('/admin/reviews')->assertOk();
    }

    // ── Helper ─────────────────────────────────────────────────

    protected function createStaffWithPermissions(array $permissions): User
    {
        $role = AdminRole::create([
            'name' => 'Test Role '.uniqid(),
            'description' => 'Test role',
            'permissions' => $permissions,
        ]);

        return User::create([
            'name' => 'Staff '.uniqid(),
            'email' => 'staff-'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
            'admin_role_id' => $role->id,
        ]);
    }
}
