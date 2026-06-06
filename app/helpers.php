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

if (! function_exists('setting_bool')) {
    function setting_bool(string $key, bool $default = false): bool
    {
        $value = setting($key, $default ? '1' : '0');

        return $value === '1' || $value === 1 || $value === true;
    }
}

if (! function_exists('site_app_name')) {
    /** Nama situs untuk tab browser & suffix judul halaman (utamakan Nama Brand dari konfigurasi). */
    function site_app_name(): string
    {
        $brand = setting('brand_name');
        if (is_string($brand) && $brand !== '') {
            return $brand;
        }

        $title = setting('site_title');
        if (is_string($title) && $title !== '') {
            return $title;
        }

        return 'YClothes';
    }
}

if (! function_exists('site_seo_title')) {
    /** Judul SEO/Open Graph — Judul Situs jika diisi, fallback ke Nama Brand. */
    function site_seo_title(): string
    {
        $title = setting('site_title');
        if (is_string($title) && $title !== '') {
            return $title;
        }

        return site_app_name();
    }
}

if (! function_exists('generate_order_number')) {
    function generate_order_number(): string
    {
        $prefix = (string) setting('order_number_prefix', 'INV-');
        $suffix = (string) setting('order_number_suffix', '');
        $length = max(4, min(16, (int) setting('order_number_length', 8)));
        $mode = (string) setting('order_number_mode', 'random');

        if ($mode === 'sequential') {
            return \Illuminate\Support\Facades\DB::transaction(function () use ($prefix, $suffix, $length) {
                $start = max(1, (int) setting('order_number_start', 1));
                $counterSetting = Setting::lockForUpdate()->firstOrCreate(
                    ['key' => 'order_number_counter'],
                    ['value' => (string) ($start - 1)],
                );

                $next = max($start, (int) $counterSetting->value + 1);
                $counterSetting->update(['value' => (string) $next]);
                clear_settings_cache();

                $padded = str_pad((string) $next, $length, '0', STR_PAD_LEFT);

                return $prefix.$padded.$suffix;
            });
        }

        do {
            $number = $prefix.strtoupper(\Illuminate\Support\Str::random($length)).$suffix;
        } while (\App\Models\Order::where('order_number', $number)->exists());

        return $number;
    }
}

if (! function_exists('weight_unit_label')) {
    function weight_unit_label(?string $unit = null): string
    {
        $unit ??= (string) setting('weight_unit', 'gram');

        return match ($unit) {
            'kg' => 'kg',
            'lb' => 'lb',
            default => 'gram',
        };
    }
}

if (! function_exists('format_product_weight')) {
    function format_product_weight(?int $weightGrams): ?string
    {
        if ($weightGrams === null || $weightGrams <= 0) {
            return null;
        }

        if ($weightGrams >= 1000) {
            $kg = $weightGrams / 1000;
            $formatted = rtrim(rtrim(number_format($kg, 2, '.', ''), '0'), '.');

            return "{$formatted} kg";
        }

        return "{$weightGrams} gram";
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
            'brand_name', 'site_title', 'site_description', 'site_keywords', 'og_image', 'favicon',
            'meta_pixel_id', 'google_tag_manager_id',
            'custom_head_scripts', 'custom_body_scripts',
        ];
        $settings = Setting::whereIn('key', $keys)->pluck('value', 'key');

        return [
            'appName' => site_app_name(),
            'siteTitle' => $settings['site_title'] ?? null,
            'ogTitle' => site_seo_title(),
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
