<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        if (! isset($GLOBALS['__settings_cache'])) {
            $GLOBALS['__settings_cache'] = Setting::pluck('value', 'key')->all();
        }

        return $GLOBALS['__settings_cache'][$key] ?? $default;
    }
}

if (! function_exists('clear_settings_cache')) {
    function clear_settings_cache(): void
    {
        unset($GLOBALS['__settings_cache']);
    }
}

if (! function_exists('generate_order_number')) {
    function generate_order_number(): string
    {
        do {
            $number = 'INV-'.strtoupper(\Illuminate\Support\Str::random(8));
        } while (\App\Models\Order::where('order_number', $number)->exists());

        return $number;
    }
}

if (! function_exists('generate_unique_payment_amount')) {
    function generate_unique_payment_amount(int $grandTotal, int $orderId): int
    {
        $suffix = str_pad((string) ($orderId % 1000), 3, '0', STR_PAD_LEFT);

        return $grandTotal + (int) $suffix;
    }
}

if (! function_exists('grant_order_access')) {
    function grant_order_access(\App\Models\Order $order): void
    {
        session(['order_access.'.$order->order_number => $order->access_token]);
    }
}

if (! function_exists('order_public_url')) {
    function order_public_url(string $routeName, \App\Models\Order $order, array $parameters = []): string
    {
        return route($routeName, array_merge([
            'order' => $order->order_number,
            'token' => $order->access_token,
        ], $parameters));
    }
}

if (! function_exists('storage_url')) {
    function storage_url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}

if (! function_exists('site_integrations')) {
    function site_integrations(): array
    {
        $keys = [
            'site_title', 'site_description', 'site_keywords', 'og_image', 'favicon',
            'meta_pixel_id', 'google_tag_manager_id',
            'custom_head_scripts', 'custom_body_scripts',
        ];
        $settings = Setting::whereIn('key', $keys)->pluck('value', 'key');

        return [
            'siteTitle' => $settings['site_title'] ?? null,
            'siteDescription' => $settings['site_description'] ?? null,
            'siteKeywords' => $settings['site_keywords'] ?? null,
            'ogImageUrl' => storage_url($settings['og_image'] ?? null),
            'faviconUrl' => storage_url($settings['favicon'] ?? null),
            'metaPixelId' => $settings['meta_pixel_id'] ?? null,
            'googleTagManagerId' => $settings['google_tag_manager_id'] ?? null,
            'customHeadScripts' => $settings['custom_head_scripts'] ?? null,
            'customBodyScripts' => $settings['custom_body_scripts'] ?? null,
        ];
    }
}

if (! function_exists('navigation')) {
    function navigation(string $menu): \Illuminate\Support\Collection
    {
        if (! \App\Models\NavigationItem::exists()) {
            return collect();
        }

        return \App\Models\NavigationItem::forMenu($menu)
            ->active()
            ->roots()
            ->with(['children' => fn ($q) => $q->active()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();
    }
}
