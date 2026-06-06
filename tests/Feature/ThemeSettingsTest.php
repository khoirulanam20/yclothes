<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThemeSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_admin_can_update_theme_settings(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();

        $this->actingAs($admin)
            ->post(route('admin.configuration.update', ['slug' => 'general/design']), [
                'brand_logo' => UploadedFile::fake()->image('logo.png'),
                'favicon' => UploadedFile::fake()->image('favicon.png'),
                'color_gold' => '#111111',
                'color_accent' => '#222222',
            ])
            ->assertRedirect('/admin/configuration/general/design');

        $this->actingAs($admin)
            ->post(route('admin.configuration.update', ['slug' => 'general/store']), [
                'brand_name' => 'YClothes Store',
            ])
            ->assertRedirect('/admin/configuration/general/store');

        $this->assertDatabaseHas('settings', ['key' => 'brand_name', 'value' => 'YClothes Store']);
        $this->assertDatabaseHas('settings', ['key' => 'color_gold', 'value' => '#111111']);
        $this->assertNotNull(Setting::where('key', 'brand_logo')->value('value'));
        $this->assertNotNull(Setting::where('key', 'favicon')->value('value'));
    }
}
