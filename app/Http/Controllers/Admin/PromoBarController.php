<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PromoBarController extends Controller
{
    public function edit()
    {
        $keys = [
            'promo_bar_enabled', 'store_location', 'promo_bar_text',
            'promo_bar_cta_label', 'wa_number', 'promo_bar_bg_color', 'promo_bar_text_color',
            'color_gold',
        ];
        $settings = Setting::whereIn('key', $keys)->pluck('value', 'key');

        return Inertia::render('Admin/PromoBar/Edit', [
            'promoBarEnabled' => ($settings['promo_bar_enabled'] ?? '1') === '1',
            'storeLocation' => $settings['store_location'] ?? '',
            'promoBarText' => $settings['promo_bar_text'] ?? '',
            'promoBarCtaLabel' => $settings['promo_bar_cta_label'] ?? 'Hubungi WA',
            'waNumber' => $settings['wa_number'] ?? '',
            'promoBarBgColor' => $settings['promo_bar_bg_color'] ?? '',
            'promoBarTextColor' => $settings['promo_bar_text_color'] ?? '',
            'themeColorGold' => $settings['color_gold'] ?? '#C2A56D',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'promo_bar_enabled' => 'nullable|boolean',
            'store_location' => 'nullable|string|max:255',
            'promo_bar_text' => 'nullable|string|max:255',
            'promo_bar_cta_label' => 'nullable|string|max:100',
            'wa_number' => 'nullable|string|max:20',
            'promo_bar_bg_color' => 'nullable|string|max:7',
            'promo_bar_text_color' => 'nullable|string|max:7',
        ]);

        Setting::updateOrCreate(
            ['key' => 'promo_bar_enabled'],
            ['value' => $request->boolean('promo_bar_enabled') ? '1' : '0'],
        );

        foreach ([
            'store_location', 'promo_bar_text', 'promo_bar_cta_label', 'wa_number',
            'promo_bar_bg_color', 'promo_bar_text_color',
        ] as $key) {
            Setting::updateOrCreate(['key' => $key], ['value' => $validated[$key] ?? '']);
        }

        return redirect()->route('admin.promo-bar.edit')->with('success', 'Bar promo berhasil disimpan.');
    }
}
