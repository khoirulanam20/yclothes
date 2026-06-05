<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        static $all = null;
        if ($all === null) {
            $all = Setting::pluck('value', 'key')->all();
        }

        return $all[$key] ?? $default;
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
