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

    public function test_admin_settings_sanitizes_about_content(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();

        $this->actingAs($admin)->post('/admin/settings', [
            'name' => 'Admin',
            'email' => 'admin@yclothes.test',
            'about_content' => '<p>About</p><script>alert(1)</script>',
            'cara_belanja_content' => '<p>Cara</p>',
        ])->assertRedirect('/admin/settings');

        $saved = Setting::where('key', 'about_content')->value('value');
        $this->assertStringNotContainsString('<script>', $saved);
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
