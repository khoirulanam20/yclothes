<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AppearanceController extends Controller
{
    public function edit(): View
    {
        $keys = ['site_title', 'site_description', 'hero_title', 'hero_subtitle',
            'hero_image', 'banner_title', 'banner_text', 'banner_button',
            'banner_link', 'banner_image', 'cta_text', 'cta_link'];
        $settings = [];
        foreach ($keys as $key) {
            $settings[Str::camel($key)] = Setting::where('key', $key)->value('value');
        }

        return view('admin.appearance', $settings);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'site_title' => 'nullable|string|max:255',
            'site_description' => 'nullable|string|max:500',
            'hero_title' => 'nullable|string|max:500',
            'hero_subtitle' => 'nullable|string|max:1000',
            'hero_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_hero_image' => 'nullable|boolean',
            'banner_title' => 'nullable|string|max:500',
            'banner_text' => 'nullable|string|max:1000',
            'banner_button' => 'nullable|string|max:100',
            'banner_link' => 'nullable|string|max:500',

            'cta_text' => 'nullable|string|max:100',
            'cta_link' => 'nullable|string|max:500',
        ]);

        collect(['site_title', 'site_description', 'hero_title', 'hero_subtitle',
            'banner_title', 'banner_text', 'banner_button', 'banner_link',
            'cta_text', 'cta_link',
        ])->each(function ($key) use ($request) {
            $value = $request->$key;
            if ($key === 'hero_title') {
                $value = strip_tags($value, '<br>');
            }
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        });

        if ($request->hasFile('hero_image')) {
            $old = Setting::where('key', 'hero_image')->value('value');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            $path = $request->file('hero_image')->store('hero', 'public');
            Setting::updateOrCreate(['key' => 'hero_image'], ['value' => $path]);
        } elseif ($request->boolean('remove_hero_image')) {
            $old = Setting::where('key', 'hero_image')->value('value');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            Setting::updateOrCreate(['key' => 'hero_image'], ['value' => null]);
        }

        return redirect()->route('admin.appearance')->with('success', 'Tampilan toko berhasil disimpan.');
    }
}
