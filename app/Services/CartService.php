<?php

namespace App\Services;

class CartService
{
    public const SESSION_KEY = 'cart';

    public const COUPON_SESSION_KEY = 'cart_coupon';

    public function get(): array
    {
        return session()->get(self::SESSION_KEY, []);
    }

    public function put(array $cart): void
    {
        session()->put(self::SESSION_KEY, $cart);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return array_sum(array_column($this->get(), 'qty'));
    }

    public function merge(array $incoming): array
    {
        $cart = $this->get();

        foreach ($incoming as $key => $item) {
            if (isset($cart[$key])) {
                $cart[$key]['qty'] = min(99, $cart[$key]['qty'] + ($item['qty'] ?? 1));
            } else {
                $cart[$key] = $item;
            }
        }

        $this->put($cart);

        return $cart;
    }

    public function mergeFromSession(?string $sourceKey = 'guest_cart'): void
    {
        if (! $sourceKey) {
            return;
        }

        $guestCart = session()->get($sourceKey, []);
        if ($guestCart !== []) {
            $this->merge($guestCart);
            session()->forget($sourceKey);
        }
    }

    public function backupToGuest(): void
    {
        $cart = $this->get();
        if ($cart !== []) {
            session()->put('guest_cart', $cart);
        }
    }
}
