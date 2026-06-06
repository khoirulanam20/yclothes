<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Carbon;

class FlashSaleService
{
    public function __construct(private HomepageLayoutService $layoutService) {}

    /** @return list<int> */
    public function activeProductIds(): array
    {
        $ids = [];

        foreach ($this->activeFlashSections() as $section) {
            foreach ($this->itemsFromProps($section['props'] ?? []) as $item) {
                $ids[] = (int) $item['productId'];
            }
        }

        return array_values(array_unique($ids));
    }

    public function priceForProduct(Product $product): ?int
    {
        $best = null;

        foreach ($this->activeFlashSections() as $section) {
            foreach ($this->itemsFromProps($section['props'] ?? []) as $item) {
                if ((int) $item['productId'] !== $product->id) {
                    continue;
                }

                $discounted = $this->applyItemDiscount($product->final_price, $item);
                $best = $best === null ? $discounted : min($best, $discounted);
            }
        }

        return $best;
    }

    /** @return list<array<string, mixed>> */
    public function activeFlashSections(): array
    {
        $sections = [];

        foreach ($this->layoutService->getLayout() as $section) {
            if (($section['type'] ?? '') !== 'flash_sale') {
                continue;
            }

            if (! ($section['enabled'] ?? true)) {
                continue;
            }

            $props = is_array($section['props'] ?? null) ? $section['props'] : [];
            if (! $this->isWithinPeriod($props)) {
                continue;
            }

            $sections[] = $section;
        }

        return $sections;
    }

    /** @param  array<string, mixed>  $props
     * @return list<array<string, mixed>>
     */
    public function itemsFromProps(array $props): array
    {
        $items = $props['items'] ?? [];

        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, fn ($item) => is_array($item) && ! empty($item['productId'])));
    }

    /** @param  array<string, mixed>  $props */
    public function isWithinPeriod(array $props): bool
    {
        $now = now();

        if (! empty($props['startsAt'])) {
            $starts = Carbon::parse((string) $props['startsAt']);
            if ($now->lt($starts)) {
                return false;
            }
        }

        $endsAt = $props['endsAt'] ?? null;
        if ($endsAt !== null && $endsAt !== '') {
            $ends = Carbon::parse((string) $endsAt);
            if ($now->gt($ends)) {
                return false;
            }
        }

        return true;
    }

    /** @param  array<string, mixed>  $item */
    public function applyItemDiscount(int $basePrice, array $item): int
    {
        $type = $item['discountType'] ?? 'percentage';
        $amount = (float) ($item['discountAmount'] ?? 0);

        if ($amount <= 0) {
            return $basePrice;
        }

        if ($type === 'fixed') {
            return max(0, $basePrice - (int) $amount);
        }

        return max(0, (int) round($basePrice * (1 - ($amount / 100))));
    }

    /** @param  array<string, mixed>  $props */
    public function endsAtTimestamp(array $props): int
    {
        $endsAt = $props['endsAt'] ?? null;

        if ($endsAt !== null && $endsAt !== '') {
            return Carbon::parse((string) $endsAt)->timestamp;
        }

        return now()->endOfDay()->timestamp;
    }
}
