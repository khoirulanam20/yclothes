<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\HtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_html_sanitizer_strips_script_tags(): void
    {
        $clean = HtmlSanitizer::clean('<p>Hello</p><script>alert(1)</script>');
        $this->assertStringNotContainsString('<script>', $clean);
        $this->assertStringContainsString('Hello', $clean);
    }

    public function test_cms_page_sanitizes_script_on_save(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();

        $layout = json_encode([
            'root' => ['props' => ['showBreadcrumb' => true, 'pageTitle' => 'About']],
            'content' => [
                [
                    'type' => 'RichText',
                    'props' => [
                        'id' => 'about-1',
                        'html' => '<p>About</p><script>alert(1)</script>',
                    ],
                ],
            ],
        ]);

        $this->actingAs($admin)->post('/admin/cms-pages/builder', [
            'title' => 'About',
            'slug' => 'about-test',
            'status' => 'published',
            'layout_json' => $layout,
        ])->assertRedirect();

        $page = \App\Models\CmsPage::where('slug', 'about-test')->firstOrFail();
        $html = $page->layout_json['content'][0]['props']['html'] ?? '';
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('About', $html);
    }

    public function test_security_headers_are_present(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_cart_add_rejects_invalid_product(): void
    {
        $this->postJson('/cart/add', ['product_id' => 99999, 'qty' => 1])
            ->assertStatus(422);
    }

    public function test_cart_add_validates_qty(): void
    {
        $product = Product::first();

        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 0])
            ->assertStatus(422);
    }
}
