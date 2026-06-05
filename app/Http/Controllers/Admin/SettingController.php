<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function edit()
    {
        $keys = ['wa_number', 'brand_name', 'brand_logo', 'color_gold', 'color_accent',
            'social_instagram', 'social_facebook', 'social_tiktok', 'flash_sale_ends_at',
            'store_location', 'promo_bar_text'];
        $props = ['user' => [
            'name' => Auth::user()->name,
            'email' => Auth::user()->email,
        ]];
        foreach ($keys as $key) {
            $props[Str::camel($key)] = Setting::where('key', $key)->value('value');
        }

        return Inertia::render('Admin/Settings', $props);
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
            'promo_bar_text' => 'nullable|string|max:255',
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
            'promo_bar_text' => $request->promo_bar_text,
        ];

        foreach ($textSettings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        if ($request->hasFile('brand_logo')) {
            $path = $request->file('brand_logo')->store('logos', 'public');
            Setting::updateOrCreate(['key' => 'brand_logo'], ['value' => $path]);
        } elseif ($request->boolean('remove_logo')) {
            Setting::updateOrCreate(['key' => 'brand_logo'], ['value' => null]);
        }

        return redirect()->route('admin.settings')->with('success', 'Pengaturan berhasil disimpan.');
    }
}
