<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoBarSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_update_promo_bar_settings(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();

        $this->actingAs($admin)
            ->post(route('admin.promo-bar.update'), [
                'promo_bar_enabled' => true,
                'store_location' => 'Jakarta',
                'promo_bar_text' => 'Gratis Ongkir',
                'promo_bar_cta_label' => 'Chat Kami',
                'wa_number' => '6281111111111',
                'promo_bar_bg_color' => '#112233',
                'promo_bar_text_color' => '#ffffff',
            ])
            ->assertRedirect(route('admin.promo-bar.edit'));

        $this->assertDatabaseHas('settings', ['key' => 'store_location', 'value' => 'Jakarta']);
        $this->assertDatabaseHas('settings', ['key' => 'promo_bar_cta_label', 'value' => 'Chat Kami']);
        $this->assertDatabaseHas('settings', ['key' => 'promo_bar_enabled', 'value' => '1']);
    }

    public function test_promo_bar_hidden_when_disabled(): void
    {
        Setting::updateOrCreate(['key' => 'promo_bar_enabled'], ['value' => '0']);
        Setting::updateOrCreate(['key' => 'promo_bar_text'], ['value' => 'Promo Test Hidden']);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('theme.promoBarEnabled', false));
    }

    public function test_promo_bar_renders_when_enabled(): void
    {
        Setting::updateOrCreate(['key' => 'promo_bar_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'promo_bar_text'], ['value' => 'Promo Test Visible']);
        Setting::updateOrCreate(['key' => 'promo_bar_cta_label'], ['value' => 'Hubungi Sekarang']);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('theme.promoBarEnabled', true)
                ->where('theme.promoBarText', 'Promo Test Visible')
                ->where('theme.promoBarCtaLabel', 'Hubungi Sekarang')
            );
    }
}
