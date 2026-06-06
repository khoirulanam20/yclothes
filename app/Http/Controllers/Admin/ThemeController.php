<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ThemeController extends Controller
{
    public function edit()
    {
        $keys = ['brand_name', 'brand_logo', 'favicon', 'color_gold', 'color_accent',
            'social_instagram', 'social_facebook', 'social_tiktok'];
        $props = [];
        foreach ($keys as $key) {
            $props[\Illuminate\Support\Str::camel($key)] = Setting::where('key', $key)->value('value');
        }
        $props['brandLogoUrl'] = storage_url($props['brandLogo'] ?? null);
        $props['faviconUrl'] = storage_url($props['favicon'] ?? null);

        return Inertia::render('Admin/Theme/Edit', $props);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'brand_name' => 'nullable|string|max:255',
            'brand_logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'remove_logo' => 'nullable|boolean',
            'favicon' => 'nullable|image|mimes:png,jpg,jpeg,webp,ico|max:512',
            'remove_favicon' => 'nullable|boolean',
            'color_gold' => 'nullable|string|max:7',
            'color_accent' => 'nullable|string|max:7',
            'social_instagram' => 'nullable|string|max:255',
            'social_facebook' => 'nullable|string|max:255',
            'social_tiktok' => 'nullable|string|max:255',
        ]);

        foreach ([
            'brand_name' => $request->brand_name,
            'color_gold' => $request->color_gold,
            'color_accent' => $request->color_accent,
            'social_instagram' => $request->social_instagram,
            'social_facebook' => $request->social_facebook,
            'social_tiktok' => $request->social_tiktok,
        ] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        if ($request->hasFile('brand_logo')) {
            $old = Setting::where('key', 'brand_logo')->value('value');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            $path = $request->file('brand_logo')->store('logos', 'public');
            Setting::updateOrCreate(['key' => 'brand_logo'], ['value' => $path]);
        } elseif ($request->boolean('remove_logo')) {
            $old = Setting::where('key', 'brand_logo')->value('value');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            Setting::updateOrCreate(['key' => 'brand_logo'], ['value' => null]);
        }

        if ($request->hasFile('favicon')) {
            $old = Setting::where('key', 'favicon')->value('value');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            $path = $request->file('favicon')->store('favicons', 'public');
            Setting::updateOrCreate(['key' => 'favicon'], ['value' => $path]);
        } elseif ($request->boolean('remove_favicon')) {
            $old = Setting::where('key', 'favicon')->value('value');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            Setting::updateOrCreate(['key' => 'favicon'], ['value' => null]);
        }

        return redirect()->route('admin.theme.edit')->with('success', 'Tema berhasil disimpan.');
    }
}
