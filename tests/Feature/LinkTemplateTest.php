<?php

namespace Tests\Feature;

use App\Models\CartRule;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LinkTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_cannot_fetch_link_templates(): void
    {
        $this->getJson(route('admin.link-templates.index'))
            ->assertUnauthorized();
    }

    public function test_admin_can_fetch_link_templates(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();

        CmsPage::query()->updateOrCreate(
            ['slug' => 'kebijakan-privasi'],
            [
                'title' => 'Kebijakan Privasi',
                'status' => 'published',
                'layout_json' => ['content' => [], 'root' => ['props' => []]],
                'layout_version' => 'puck-1',
            ],
        );

        CartRule::create([
            'name' => 'Promo Test',
            'slug' => 'promo-test',
            'discount_type' => 'percentage',
            'discount_amount' => 10,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.link-templates.index'))
            ->assertOk();

        $groups = collect($response->json('groups'));
        $urls = $groups->flatMap(fn (array $group) => collect($group['options'])->pluck('url'));

        $this->assertTrue($urls->contains('/'));
        $this->assertTrue($urls->contains('/products?badge=sale'));
        $this->assertTrue($urls->contains('/page/kebijakan-privasi'));
        $this->assertTrue($urls->contains('/promo/promo-test'));
    }

    public function test_custom_product_badges_appear_in_link_templates(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();
        $category = Category::first();

        Product::factory()->create([
            'category_id' => $category->id,
            'image' => 'products/test-limited.jpg',
            'is_active' => true,
            'badge_preset' => 'custom',
            'badge' => 'Limited Edition',
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.link-templates.index'))
            ->assertOk();

        $urls = collect($response->json('groups'))
            ->flatMap(fn (array $group) => collect($group['options'])->pluck('url'));

        $this->assertTrue($urls->contains('/products?badge_label=Limited%20Edition'));
    }

    public function test_products_can_be_filtered_by_custom_badge_label(): void
    {
        $category = Category::first();

        Product::factory()->create([
            'category_id' => $category->id,
            'image' => 'products/test-limited.jpg',
            'is_active' => true,
            'badge_preset' => 'custom',
            'badge' => 'Limited Edition',
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'image' => 'products/test-sale.jpg',
            'is_active' => true,
            'badge_preset' => 'sale',
            'badge' => 'Sale',
        ]);

        $this->get('/products?badge_label=Limited%20Edition')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.badge_label', 'Limited Edition')
                ->where('pageTitle', 'Produk Badge Limited Edition')
            );
    }

    public function test_products_can_be_filtered_by_badge(): void
    {
        $category = Category::first();

        Product::factory()->create([
            'category_id' => $category->id,
            'image' => 'products/test-sale.jpg',
            'is_active' => true,
            'badge_preset' => 'sale',
            'badge' => 'Sale',
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'image' => 'products/test-new.jpg',
            'is_active' => true,
            'badge_preset' => 'new',
            'badge' => 'New',
        ]);

        $this->get('/products?badge=sale')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.badge', 'sale')
                ->where('pageTitle', 'Produk Badge Sale')
            );
    }
}
