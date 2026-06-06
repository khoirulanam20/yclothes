<?php

namespace Tests\Feature;

use App\Models\CartRule;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\User;
use App\Services\FlashSaleService;
use App\Services\HomepageLayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomepageLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_default_layout_is_available(): void
    {
        $layout = app(HomepageLayoutService::class)->defaultLayout();

        $this->assertNotEmpty($layout);
        $this->assertSame('hero_slider', $layout[0]['type']);
    }

    public function test_admin_can_save_homepage_layout(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();

        $layout = [
            ['id' => 'hero-1', 'type' => 'hero_slider', 'enabled' => true, 'props' => []],
            ['id' => 'blog-1', 'type' => 'blog_posts', 'enabled' => false, 'props' => ['title' => 'Blog', 'limit' => 2]],
        ];

        $this->actingAs($admin)
            ->put(route('admin.homepage.update'), ['layout' => $layout])
            ->assertRedirect(route('admin.homepage.edit'));

        $saved = json_decode(Setting::where('key', HomepageLayoutService::SETTING_KEY)->value('value'), true);
        $this->assertCount(2, $saved);
        $this->assertFalse($saved[1]['enabled']);
    }

    public function test_homepage_renders_dynamic_sections(): void
    {
        Slider::create([
            'title' => 'Home Slider',
            'image' => 'sliders/test.jpg',
            'sort_order' => 0,
            'is_active' => true,
        ]);
        Storage::disk('public')->put('sliders/test.jpg', 'fake');

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Home')
                ->has('sections')
                ->where('sections.0.type', 'hero_slider')
            );
    }

    public function test_admin_can_create_slider_via_homepage_builder(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();

        $this->actingAs($admin)
            ->post(route('admin.homepage.sliders.store'), [
                'title' => 'Promo Slider',
                'image' => UploadedFile::fake()->image('slider.jpg'),
                'link_url' => '/products',
                'sort_order' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sliders', ['title' => 'Promo Slider', 'is_active' => true]);
    }

    public function test_flash_sale_filter_on_products_page(): void
    {
        $product = Product::first();
        app(HomepageLayoutService::class)->saveLayout([
            [
                'id' => 'flash-1',
                'type' => 'flash_sale',
                'enabled' => true,
                'props' => [
                    'endsAt' => now()->addDay()->format('Y-m-d\TH:i'),
                    'items' => [
                        ['productId' => $product->id, 'discountType' => 'percentage', 'discountAmount' => 10],
                    ],
                ],
            ],
        ]);

        $this->get('/products?flash_sale=1')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Products/Index')
                ->where('pageTitle', 'Flash Sale')
                ->where('filters.flash_sale', '1')
            );
    }

    public function test_product_grid_by_badge_preset(): void
    {
        $product = Product::first();
        $product->update(['badge_preset' => 'hot', 'is_active' => true]);

        app(HomepageLayoutService::class)->saveLayout([
            [
                'id' => 'grid-1',
                'type' => 'product_grid',
                'enabled' => true,
                'props' => [
                    'title' => 'Hot Items',
                    'source' => 'badge',
                    'badgePreset' => 'hot',
                    'limit' => 8,
                ],
            ],
        ]);

        $sections = app(HomepageLayoutService::class)->resolveSections(app(\App\Services\PromotionEngine::class));
        $grid = collect($sections)->firstWhere('type', 'product_grid');

        $this->assertNotNull($grid);
        $this->assertTrue(collect($grid['products'])->pluck('id')->contains($product->id));
    }

    public function test_admin_can_upload_banner_image(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();

        $response = $this->actingAs($admin)
            ->post(route('admin.homepage.banner-image'), [
                'image' => UploadedFile::fake()->image('banner.jpg'),
            ])
            ->assertOk()
            ->assertJsonStructure(['path', 'url']);

        $this->assertTrue(Storage::disk('public')->exists($response->json('path')));
    }
}
