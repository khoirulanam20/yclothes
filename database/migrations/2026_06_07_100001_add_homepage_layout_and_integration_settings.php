<?php

use App\Models\Setting;
use App\Services\HomepageLayoutService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $layoutService = app(HomepageLayoutService::class);

        Setting::updateOrCreate(
            ['key' => HomepageLayoutService::SETTING_KEY],
            ['value' => json_encode($layoutService->defaultLayout(), JSON_UNESCAPED_UNICODE)],
        );

        foreach ([
            'site_keywords' => '',
            'og_image' => '',
            'meta_pixel_id' => '',
            'google_tag_manager_id' => '',
            'custom_head_scripts' => '',
            'custom_body_scripts' => '',
            'favicon' => '',
        ] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    public function down(): void
    {
        Setting::whereIn('key', [
            HomepageLayoutService::SETTING_KEY,
            'site_keywords',
            'og_image',
            'meta_pixel_id',
            'google_tag_manager_id',
            'custom_head_scripts',
            'custom_body_scripts',
            'favicon',
        ])->delete();
    }
};
