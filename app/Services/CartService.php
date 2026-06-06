<?php

namespace App\Services;

class CartService
{
    public const SESSION_KEY = 'cart';

    public const COUPON_SESSION_KEY = 'cart_coupon';

    public const CHECKOUT_SELECTION_KEY = 'cart_checkout_selection';

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

    /** @return array<int, string>|null */
    public function getCheckoutSelection(): ?array
    {
        $selection = session()->get(self::CHECKOUT_SELECTION_KEY);

        return is_array($selection) && $selection !== [] ? array_values($selection) : null;
    }

    /** @param  array<int, string>  $keys */
    public function setCheckoutSelection(array $keys): void
    {
        session()->put(self::CHECKOUT_SELECTION_KEY, array_values(array_unique($keys)));
    }

    public function clearCheckoutSelection(): void
    {
        session()->forget(self::CHECKOUT_SELECTION_KEY);
    }

    /** @param  array<int, string>  $keys */
    public function removeKeys(array $keys): void
    {
        $cart = $this->get();

        foreach ($keys as $key) {
            unset($cart[$key]);
        }

        $this->put($cart);
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
                $cart[$key]['qty'] = $cart[$key]['qty'] + ($item['qty'] ?? 1);
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
