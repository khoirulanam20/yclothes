<?php

namespace App\Support;

use App\Models\Category;
use App\Models\NavigationItem;
use App\Models\Setting;
use App\Models\User;
use App\Services\CartService;
use App\Services\CategoryTreeService;
use Illuminate\Support\Facades\Auth;

class InertiaData
{
    public static function theme(): array
    {
        $keys = [
            'brand_name', 'brand_logo', 'color_gold', 'color_accent', 'wa_number', 'store_location',
            'site_title', 'site_description', 'promo_bar_text',
            'social_instagram', 'social_facebook', 'social_tiktok',
        ];
        $settings = Setting::whereIn('key', $keys)->pluck('value', 'key');

        return [
            'brandName' => $settings['brand_name'] ?? 'yClothes',
            'brandLogo' => isset($settings['brand_logo']) ? storage_url($settings['brand_logo']) : null,
            'colorGold' => $settings['color_gold'] ?? '#C2A56D',
            'colorAccent' => $settings['color_accent'] ?? '#547A95',
            'waNumber' => $settings['wa_number'] ?? '6280000000000',
            'storeLocation' => $settings['store_location'] ?? 'Makassar',
            'siteTitle' => $settings['site_title'] ?? 'yClothes',
            'siteDescription' => $settings['site_description'] ?? '',
            'promoBarText' => $settings['promo_bar_text'] ?? 'Free Ongkir Pembelian > Rp 200rb',
            'socialInstagram' => $settings['social_instagram'] ?? null,
            'socialFacebook' => $settings['social_facebook'] ?? null,
            'socialTiktok' => $settings['social_tiktok'] ?? null,
        ];
    }

    public static function categories(): array
    {
        $roots = Category::tree();
        app(CategoryTreeService::class)->loadCounts($roots);

        return app(CategoryTreeService::class)->serializeTree($roots);
    }

    public static function navigation(): array
    {
        if (! NavigationItem::exists()) {
            return ['header' => [], 'footer' => []];
        }

        $map = fn (NavigationItem $item) => [
            'id' => $item->id,
            'label' => $item->label,
            'url' => $item->url,
            'children' => $item->children->map(fn ($c) => [
                'id' => $c->id,
                'label' => $c->label,
                'url' => $c->url,
            ])->values()->all(),
        ];

        return [
            'header' => navigation('header')->map($map)->values()->all(),
            'footer' => navigation('footer')->map($map)->values()->all(),
        ];
    }

    public static function auth(): array
    {
        $customer = Auth::guard('customer')->user();
        $admin = Auth::guard('web')->user();

        return [
            'customer' => $customer ? [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'emailVerified' => (bool) $customer->email_verified_at,
            ] : null,
            'admin' => $admin && $admin->canAccessAdmin() ? [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'isSuperAdmin' => $admin->isSuperAdmin(),
                'permissions' => self::adminPermissions($admin),
            ] : null,
        ];
    }

    private static function adminPermissions(User $admin): array
    {
        if ($admin->isSuperAdmin()) {
            return ['*'];
        }

        return $admin->adminRole?->permissions ?? [];
    }

    public static function cartCount(): int
    {
        $cart = app(CartService::class)->get();

        return (int) array_sum(array_column($cart, 'qty'));
    }

    public static function flash(): array
    {
        return [
            'success' => session('success'),
            'error' => session('error'),
        ];
    }
}
