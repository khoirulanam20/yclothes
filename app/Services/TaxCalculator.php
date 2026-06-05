<?php

namespace App\Services;

use App\Models\TaxRate;
use App\Models\TaxZone;

class TaxCalculator
{
    public function __construct(private CategoryTreeService $categoryTree) {}

    /**
     * @param  array<int, array{product: \App\Models\Product, qty: int, unit_price: int, subtotal: int}>  $lineItems
     * @return array{tax_amount: int, breakdown: array<int, array{label: string, amount: int}>}
     */
    public function calculate(array $lineItems, ?string $shippingCity = null): array
    {
        $taxIncluded = filter_var(setting('tax_included', '0'), FILTER_VALIDATE_BOOLEAN);
        $breakdown = [];
        $totalTax = 0;

        foreach ($lineItems as $line) {
            $product = $line['product'];
            $subtotal = $line['subtotal'];
            $rate = $this->resolveRate($product->category_id, $shippingCity);

            if (! $rate || $rate->type !== 'percentage') {
                continue;
            }

            $percent = (float) $rate->rate;
            $lineTax = $this->taxFromSubtotal($subtotal, $percent, $taxIncluded);
            $totalTax += $lineTax;

            $label = $rate->name.' ('.rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.').'%)';
            $breakdown[$label] = ($breakdown[$label] ?? 0) + $lineTax;
        }

        $formatted = [];
        foreach ($breakdown as $label => $amount) {
            $formatted[] = ['label' => $label, 'amount' => $amount];
        }

        return ['tax_amount' => (int) round($totalTax), 'breakdown' => $formatted];
    }

    public function grandTotalWithTax(int $subtotal, int $taxAmount, int $shipping, int $discount, bool $taxIncluded): int
    {
        if ($taxIncluded) {
            return max(0, $subtotal - $discount + $shipping);
        }

        return max(0, $subtotal - $discount + $taxAmount + $shipping);
    }

    private function taxFromSubtotal(int $subtotal, float $percent, bool $taxIncluded): float
    {
        if ($subtotal <= 0 || $percent <= 0) {
            return 0;
        }

        if ($taxIncluded) {
            return $subtotal - ($subtotal / (1 + ($percent / 100)));
        }

        return $subtotal * ($percent / 100);
    }

    private function resolveRate(?int $categoryId, ?string $shippingCity): ?TaxRate
    {
        if ($shippingCity) {
            $zone = TaxZone::query()
                ->whereHas('taxRate', fn ($q) => $q->where('is_active', true))
                ->where(function ($q) use ($shippingCity) {
                    $q->where('city', $shippingCity)
                        ->orWhereNull('city');
                })
                ->orderByRaw('city IS NULL')
                ->first();

            if ($zone?->taxRate) {
                return $zone->taxRate;
            }
        }

        if ($categoryId) {
            $categoryRates = TaxRate::query()
                ->where('is_active', true)
                ->whereHas('categories')
                ->with('categories')
                ->get();

            foreach ($categoryRates as $rate) {
                $categoryIds = $rate->categories->pluck('category_id')->all();
                $expandedIds = $this->categoryTree->expandIds($categoryIds);

                if (in_array($categoryId, $expandedIds, true)) {
                    return $rate;
                }
            }
        }

        return TaxRate::where('is_active', true)->orderBy('id')->first();
    }
}
