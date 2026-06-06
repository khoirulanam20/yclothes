<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_save_integration_settings(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();

        $this->actingAs($admin)
            ->post(route('admin.configuration.update', ['slug' => 'general/seo']), [
                'site_title' => 'YClothes Official',
                'site_description' => 'Toko fashion terbaik',
                'site_keywords' => 'fashion, pakaian',
            ])
            ->assertRedirect('/admin/configuration/general/seo');

        $this->actingAs($admin)
            ->post(route('admin.configuration.update', ['slug' => 'general/tracking']), [
                'meta_pixel_id' => '1234567890',
                'google_tag_manager_id' => 'GTM-ABC123',
                'custom_head_scripts' => '<meta name="custom" content="test">',
                'custom_body_scripts' => '<!-- chatbot -->',
            ])
            ->assertRedirect('/admin/configuration/general/tracking');

        $this->assertDatabaseHas('settings', ['key' => 'site_title', 'value' => 'YClothes Official']);
        $this->assertDatabaseHas('settings', ['key' => 'meta_pixel_id', 'value' => '1234567890']);
    }

    public function test_integration_scripts_render_in_html(): void
    {
        Setting::updateOrCreate(['key' => 'meta_pixel_id'], ['value' => '999888777']);
        Setting::updateOrCreate(['key' => 'site_description'], ['value' => 'Deskripsi toko']);
        clear_settings_cache();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('999888777', false);
        $response->assertSee('name="description"', false);
    }
}
