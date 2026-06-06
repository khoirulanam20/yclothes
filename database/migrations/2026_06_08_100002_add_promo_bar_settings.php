<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'promo_bar_enabled' => '1',
            'promo_bar_cta_label' => 'Hubungi WA',
            'promo_bar_bg_color' => '',
            'promo_bar_text_color' => '',
        ] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    public function down(): void
    {
        Setting::whereIn('key', [
            'promo_bar_enabled',
            'promo_bar_cta_label',
            'promo_bar_bg_color',
            'promo_bar_text_color',
        ])->delete();
    }
};
