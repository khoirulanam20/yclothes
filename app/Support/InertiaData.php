<?php

namespace App\Support;

use App\Models\Category;
use App\Models\NavigationItem;
use App\Models\Setting;
use App\Models\User;
use App\Services\AdminBadgeService;
use App\Services\CartItemResolver;
use App\Services\CartService;
use App\Services\CategoryTreeService;
use App\Services\PromotionPopupService;
use Illuminate\Support\Facades\Auth;

class InertiaData
{
    public static function theme(): array
    {
        $keys = [
            'brand_name', 'brand_logo', 'favicon', 'color_gold', 'color_accent', 'wa_number', 'store_location',
            'site_title', 'site_description', 'site_keywords', 'promo_bar_text',
            'promo_bar_enabled', 'promo_bar_cta_label', 'promo_bar_bg_color', 'promo_bar_text_color',
            'social_instagram', 'social_facebook', 'social_tiktok',
        ];
        $settings = Setting::whereIn('key', $keys)->pluck('value', 'key');

        return [
            'brandName' => filled($settings['brand_name'] ?? null) ? $settings['brand_name'] : site_app_name(),
            'brandLogo' => isset($settings['brand_logo']) ? storage_url($settings['brand_logo']) : null,
            'faviconUrl' => isset($settings['favicon']) ? storage_url($settings['favicon']) : null,
            'colorGold' => $settings['color_gold'] ?? '#C2A56D',
            'colorAccent' => $settings['color_accent'] ?? '#547A95',
            'waNumber' => $settings['wa_number'] ?? '6280000000000',
            'storeLocation' => $settings['store_location'] ?? 'Makassar',
            'siteTitle' => $settings['site_title'] ?? '',
            'appName' => site_app_name(),
            'siteDescription' => $settings['site_description'] ?? '',
            'siteKeywords' => $settings['site_keywords'] ?? '',
            'promoBarText' => $settings['promo_bar_text'] ?? 'Free Ongkir Pembelian > Rp 200rb',
            'promoBarEnabled' => ($settings['promo_bar_enabled'] ?? '1') === '1',
            'promoBarCtaLabel' => $settings['promo_bar_cta_label'] ?? 'Hubungi WA',
            'promoBarBgColor' => $settings['promo_bar_bg_color'] ?? null,
            'promoBarTextColor' => $settings['promo_bar_text_color'] ?? null,
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
                'completedTourVariants' => $admin->adminTourProgress(),
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
        $cartService = app(CartService::class);

        return $cartService->resolvableCount(app(CartItemResolver::class));
    }

    public static function promotionPopup(): ?array
    {
        $routeName = request()->route()?->getName();
        $popup = app(PromotionPopupService::class)->resolveForRoute($routeName);

        return app(PromotionPopupService::class)->serialize($popup);
    }

    public static function gdpr(): array
    {
        if (! setting_bool('gdpr_enabled')) {
            return ['enabled' => false];
        }

        return [
            'enabled' => true,
            'message' => setting('gdpr_message', 'Situs ini menggunakan cookie untuk meningkatkan pengalaman Anda.'),
            'policyUrl' => setting('gdpr_policy_url'),
            'cookieLifetimeDays' => (int) setting('gdpr_cookie_lifetime_days', 365),
        ];
    }

    public static function flash(): array
    {
        return [
            'success' => session('success'),
            'error' => session('error'),
            'warning' => session('warning'),
        ];
    }

    /** @return array{orders: int, returns: int, reviews: int, lowStock: int, notificationsUnread: int}|null */
    public static function adminBadges(): ?array
    {
        $request = request();
        if (! $request->is('admin*') || $request->routeIs('admin.login')) {
            return null;
        }

        $admin = Auth::guard('web')->user();
        if (! $admin || ! $admin->canAccessAdmin()) {
            return null;
        }

        return app(AdminBadgeService::class)->countsForAdmin($admin);
    }
}
