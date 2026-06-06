<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentWebhookLog;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\MidtransService;
use App\Services\OrderPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@yclothes.test')->first();
    }

    public function test_configuration_search_returns_results(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/admin/configuration/search?q=midtrans');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Midtrans']);
    }

    public function test_tax_included_can_be_saved_via_configuration(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/configuration/sales/taxes/calculation', [
                'tax_included' => '1',
            ])
            ->assertRedirect('/admin/configuration/sales/taxes/calculation');

        $this->assertEquals('1', Setting::where('key', 'tax_included')->value('value'));
    }

    public function test_order_number_uses_configured_prefix(): void
    {
        Setting::updateOrCreate(['key' => 'order_number_prefix'], ['value' => 'ORD-']);
        Setting::updateOrCreate(['key' => 'order_number_length'], ['value' => '6']);
        Setting::updateOrCreate(['key' => 'order_number_mode'], ['value' => 'random']);
        clear_settings_cache();

        $number = generate_order_number();

        $this->assertStringStartsWith('ORD-', $number);
    }

    public function test_order_number_sequential_mode_increments(): void
    {
        Setting::updateOrCreate(['key' => 'order_number_mode'], ['value' => 'sequential']);
        Setting::updateOrCreate(['key' => 'order_number_prefix'], ['value' => 'ORD-']);
        Setting::updateOrCreate(['key' => 'order_number_length'], ['value' => '4']);
        Setting::updateOrCreate(['key' => 'order_number_start'], ['value' => '100']);
        Setting::updateOrCreate(['key' => 'order_number_suffix'], ['value' => '-ID']);
        Setting::where('key', 'order_number_counter')->delete();
        clear_settings_cache();

        $first = generate_order_number();
        $second = generate_order_number();

        $this->assertSame('ORD-0100-ID', $first);
        $this->assertSame('ORD-0101-ID', $second);
    }

    public function test_order_number_random_includes_suffix(): void
    {
        Setting::updateOrCreate(['key' => 'order_number_mode'], ['value' => 'random']);
        Setting::updateOrCreate(['key' => 'order_number_prefix'], ['value' => 'INV-']);
        Setting::updateOrCreate(['key' => 'order_number_suffix'], ['value' => '-X']);
        Setting::updateOrCreate(['key' => 'order_number_length'], ['value' => '4']);
        clear_settings_cache();

        $number = generate_order_number();

        $this->assertStringStartsWith('INV-', $number);
        $this->assertStringEndsWith('-X', $number);
    }

    public function test_guest_checkout_disabled_redirects_to_login(): void
    {
        Setting::updateOrCreate(['key' => 'guest_checkout_enabled'], ['value' => '0']);
        clear_settings_cache();

        $this->get('/checkout')
            ->assertRedirect(route('customer.login'));
    }

    public function test_midtrans_active_respects_setting(): void
    {
        Setting::updateOrCreate(['key' => 'midtrans_active'], ['value' => '0']);
        clear_settings_cache();

        $this->assertFalse(MidtransService::isActive());

        Setting::updateOrCreate(['key' => 'midtrans_active'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'midtrans_client_key'], ['value' => 'test-key']);
        Setting::updateOrCreate(['key' => 'midtrans_server_key'], ['value' => 'test-secret']);
        clear_settings_cache();

        $this->assertTrue(MidtransService::isActive());
    }

    public function test_reviews_require_login_blocks_guest_review(): void
    {
        Setting::updateOrCreate(['key' => 'reviews_require_login'], ['value' => '1']);
        clear_settings_cache();

        $order = Order::create([
            'order_number' => 'INV-REVTEST1',
            'access_token' => 'token123',
            'customer_name' => 'Test',
            'customer_phone' => '08123456789',
            'customer_email' => 'test@example.com',
            'shipping_address' => 'Jl. Test',
            'shipping_city' => 'Makassar',
            'total_price' => 100000,
            'grand_total' => 100000,
            'payment_status' => 'paid',
            'order_status' => 'completed',
        ]);

        $this->post("/order/{$order->order_number}/reviews?token={$order->access_token}", [
            'order_item_id' => 1,
            'rating' => 5,
        ])->assertRedirect(route('customer.login'));
    }

    public function test_gdpr_settings_shared_to_frontend(): void
    {
        Setting::updateOrCreate(['key' => 'gdpr_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'gdpr_message'], ['value' => 'Kami pakai cookie.']);
        clear_settings_cache();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('gdpr.enabled', true)
                ->where('gdpr.message', 'Kami pakai cookie.')
            );
    }

    public function test_sitemap_generate_command_creates_file(): void
    {
        Setting::updateOrCreate(['key' => 'sitemap_enabled'], ['value' => '1']);
        clear_settings_cache();

        $this->artisan('sitemap:generate')->assertSuccessful();

        $this->assertFileExists(public_path('sitemap.xml'));
        $this->assertStringContainsString('<urlset', file_get_contents(public_path('sitemap.xml')));
    }

    public function test_mail_settings_service_applies_config(): void
    {
        Setting::updateOrCreate(['key' => 'mail_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'mail_host'], ['value' => 'smtp.test.local']);
        Setting::updateOrCreate(['key' => 'mail_port'], ['value' => '2525']);
        Setting::updateOrCreate(['key' => 'mail_from_address'], ['value' => 'shop@test.local']);
        clear_settings_cache();

        app(\App\Services\MailSettingsService::class)->apply();

        $this->assertSame('smtp.test.local', config('mail.mailers.smtp.host'));
        $this->assertSame(2525, config('mail.mailers.smtp.port'));
        $this->assertSame('shop@test.local', config('mail.from.address'));
    }

    public function test_send_test_email_uses_dedicated_route(): void
    {
        $this->actingAs($this->admin)
            ->from('/admin/configuration/general/email')
            ->post('/admin/configuration/test-email', ['email' => 'test@example.com'])
            ->assertRedirect('/admin/configuration/general/email')
            ->assertSessionHas('success');
    }

    public function test_duplicate_midtrans_webhook_does_not_double_mark_paid(): void
    {
        Setting::updateOrCreate(['key' => 'log_duplicate_webhooks'], ['value' => '1']);
        clear_settings_cache();

        $order = Order::create([
            'order_number' => 'INV-DUPTEST1',
            'customer_name' => 'Test',
            'customer_phone' => '08123456789',
            'customer_email' => 'test@example.com',
            'shipping_address' => 'Jl. Test',
            'shipping_city' => 'Makassar',
            'total_price' => 100000,
            'grand_total' => 100000,
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
        ]);

        app(OrderPaymentService::class)->applyMidtransStatus($order->fresh(), 'settlement');

        PaymentWebhookLog::create([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'provider' => 'midtrans',
            'event_type' => 'notification',
            'transaction_status' => 'settlement',
            'amount' => 100000,
            'is_duplicate' => true,
            'payload' => ['test' => true],
        ]);

        $this->assertEquals(1, PaymentWebhookLog::where('order_id', $order->id)->where('is_duplicate', true)->count());
        $this->assertEquals('paid', $order->fresh()->payment_status);
    }

    public function test_expire_command_skips_when_disabled(): void
    {
        Setting::updateOrCreate(['key' => 'auto_cancel_unpaid_orders'], ['value' => '0']);
        clear_settings_cache();

        $order = Order::create([
            'order_number' => 'INV-EXPIRE01',
            'customer_name' => 'Test',
            'customer_phone' => '08123456789',
            'customer_email' => 'test@example.com',
            'shipping_address' => 'Jl. Test',
            'shipping_city' => 'Makassar',
            'total_price' => 100000,
            'grand_total' => 100000,
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'payment_due_at' => now()->subHour(),
        ]);

        $this->artisan('orders:expire-pending')->assertSuccessful();

        $this->assertEquals('pending', $order->fresh()->order_status);
    }

    public function test_out_of_stock_hide_filters_product_index(): void
    {
        Setting::updateOrCreate(['key' => 'out_of_stock_behavior'], ['value' => 'hide']);
        clear_settings_cache();

        $product = Product::where('is_active', true)->first();
        $this->assertNotNull($product);

        $product->update(['track_stock' => true, 'allow_backorder' => false]);
        \App\Models\Inventory::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => 1, 'product_variant_id' => null],
            ['stock' => 0],
        );

        $response = $this->get('/products');
        $response->assertOk();

        $slugs = collect($response->viewData('page')['props']['products']['data'] ?? [])
            ->pluck('slug');

        $this->assertFalse($slugs->contains($product->slug));
    }
}
