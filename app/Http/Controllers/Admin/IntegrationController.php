<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class IntegrationController extends Controller
{
    public function edit()
    {
        $keys = [
            'site_title', 'site_description', 'site_keywords', 'og_image',
            'meta_pixel_id', 'google_tag_manager_id',
            'custom_head_scripts', 'custom_body_scripts',
        ];
        $props = [];
        foreach ($keys as $key) {
            $props[Str::camel($key)] = Setting::where('key', $key)->value('value');
        }
        $props['ogImageUrl'] = storage_url($props['ogImage'] ?? null);

        return Inertia::render('Admin/Integrations/Edit', $props);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'site_title' => 'nullable|string|max:255',
            'site_description' => 'nullable|string|max:500',
            'site_keywords' => 'nullable|string|max:500',
            'og_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_og_image' => 'nullable|boolean',
            'meta_pixel_id' => 'nullable|string|max:50|regex:/^[0-9]*$/',
            'google_tag_manager_id' => 'nullable|string|max:50|regex:/^[A-Za-z0-9-]*$/',
            'custom_head_scripts' => 'nullable|string|max:10000',
            'custom_body_scripts' => 'nullable|string|max:10000',
        ]);

        foreach ([
            'site_title', 'site_description', 'site_keywords',
            'meta_pixel_id', 'google_tag_manager_id',
            'custom_head_scripts', 'custom_body_scripts',
        ] as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->input($key)],
            );
        }

        if ($request->hasFile('og_image')) {
            $old = Setting::where('key', 'og_image')->value('value');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            $path = $request->file('og_image')->store('seo', 'public');
            Setting::updateOrCreate(['key' => 'og_image'], ['value' => $path]);
        } elseif ($request->boolean('remove_og_image')) {
            $old = Setting::where('key', 'og_image')->value('value');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            Setting::updateOrCreate(['key' => 'og_image'], ['value' => null]);
        }

        return redirect()->route('admin.integrations.edit')->with('success', 'SEO & integrasi berhasil disimpan.');
    }
}
