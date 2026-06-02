<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $keys = ['wa_number', 'brand_name', 'brand_logo', 'color_gold', 'color_accent',
            'social_instagram', 'social_facebook', 'social_tiktok', 'flash_sale_ends_at',
            'store_location', 'about_banner', 'about_content',
            'cara_belanja_banner', 'cara_belanja_content'];
        $settings = [];
        foreach ($keys as $key) {
            $settings[Str::camel($key)] = Setting::where('key', $key)->value('value');
        }

        return view('admin.settings', $settings);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|min:8|confirmed',
            'wa_number' => 'nullable|string|max:20',
            'brand_name' => 'nullable|string|max:255',
            'brand_logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'remove_logo' => 'nullable|boolean',
            'color_gold' => 'nullable|string|max:7',
            'color_accent' => 'nullable|string|max:7',
            'social_instagram' => 'nullable|string|max:255',
            'social_facebook' => 'nullable|string|max:255',
            'social_tiktok' => 'nullable|string|max:255',
            'flash_sale_ends_at' => 'nullable|date',
            'store_location' => 'nullable|string|max:255',
            'about_banner' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'about_content' => 'nullable|string',
            'cara_belanja_banner' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'cara_belanja_content' => 'nullable|string',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $textSettings = [
            'wa_number' => $request->wa_number,
            'brand_name' => $request->brand_name,
            'color_gold' => $request->color_gold,
            'color_accent' => $request->color_accent,
            'social_instagram' => $request->social_instagram,
            'social_facebook' => $request->social_facebook,
            'social_tiktok' => $request->social_tiktok,
            'flash_sale_ends_at' => $request->flash_sale_ends_at,
            'store_location' => $request->store_location,
            'about_content' => HtmlSanitizer::clean($request->about_content),
            'cara_belanja_content' => HtmlSanitizer::clean($request->cara_belanja_content),
        ];

        foreach ($textSettings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        if ($request->hasFile('brand_logo')) {
            $path = $request->file('brand_logo')->store('logos', 'public');
            Setting::updateOrCreate(
                ['key' => 'brand_logo'],
                ['value' => $path],
            );
        } elseif ($request->boolean('remove_logo')) {
            Setting::updateOrCreate(
                ['key' => 'brand_logo'],
                ['value' => null],
            );
        }

        if ($request->hasFile('about_banner')) {
            $path = $request->file('about_banner')->store('about', 'public');
            Setting::updateOrCreate(
                ['key' => 'about_banner'],
                ['value' => $path],
            );
        } elseif ($request->boolean('remove_about_banner')) {
            Setting::updateOrCreate(
                ['key' => 'about_banner'],
                ['value' => null],
            );
        }

        if ($request->hasFile('cara_belanja_banner')) {
            $path = $request->file('cara_belanja_banner')->store('cara-belanja', 'public');
            Setting::updateOrCreate(
                ['key' => 'cara_belanja_banner'],
                ['value' => $path],
            );
        } elseif ($request->boolean('remove_cara_belanja_banner')) {
            Setting::updateOrCreate(
                ['key' => 'cara_belanja_banner'],
                ['value' => null],
            );
        }

        return redirect()->route('admin.settings')->with('success', 'Pengaturan berhasil disimpan.');
    }
}
