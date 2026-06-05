<?php

namespace App\Services;

use App\Models\CartRule;
use App\Models\CartRuleUsage;
use App\Models\CatalogRule;
use App\Models\Product;
use Illuminate\Support\Collection;

class PromotionEngine
{
    /** @var Collection<int, CatalogRule>|null */
    private ?Collection $catalogRules = null;

    public function __construct(private CategoryTreeService $categoryTree) {}

    /**
     * @param  array<int, array{product: Product, qty: int, unit_price?: int}>  $lineItems
     * @return array{
     *   discount_amount: int,
     *   free_shipping: bool,
     *   cart_rule: ?CartRule,
     *   message: ?string,
     *   buy_x_get_y_savings: int
     * }
     */
    public function applyToCart(
        array $lineItems,
        int $subtotal,
        ?string $couponCode = null,
        ?int $customerId = null
    ): array {
        $result = [
            'discount_amount' => 0,
            'free_shipping' => false,
            'cart_rule' => null,
            'message' => null,
            'buy_x_get_y_savings' => 0,
        ];

        $buyXGetY = $this->calculateBuyXGetYSavings($lineItems);
        $result['buy_x_get_y_savings'] = $buyXGetY;
        $result['discount_amount'] += $buyXGetY;

        $freeShippingThreshold = $this->getActiveFreeShippingThreshold();
        if ($freeShippingThreshold && $subtotal >= $freeShippingThreshold) {
            $result['free_shipping'] = true;
        }

        $rule = $this->findApplicableCartRule($lineItems, $subtotal, $couponCode, $customerId);
        if (! $rule) {
            return $result;
        }

        $result['cart_rule'] = $rule;

        if ($rule->discount_type === 'free_shipping') {
            $result['free_shipping'] = true;

            return $result;
        }

        $discount = $this->calculateCartRuleDiscount($rule, $lineItems, $subtotal);
        $result['discount_amount'] += $discount;

        return $result;
    }

    public function getUnitPrice(Product $product, int $qty = 1): int
    {
        $base = $product->final_price;
        $best = $base;

        foreach ($this->activeCatalogRules() as $rule) {
            if (! $this->catalogRuleMatchesProduct($rule, $product)) {
                continue;
            }

            if (in_array($rule->rule_type, ['percentage_discount', 'fixed_discount'], true)) {
                $discounted = $this->applyCatalogDiscount($base, $rule);
                $best = min($best, $discounted);
            }

            if ($rule->rule_type === 'tiered_qty_discount' && $rule->min_qty && $qty >= $rule->min_qty) {
                $discounted = $this->applyCatalogDiscount($base, $rule);
                $best = min($best, $discounted);
            }
        }

        return max(0, (int) $best);
    }

    public function decorateProduct(Product $product): Product
    {
        $unit = $this->getUnitPrice($product, 1);
        $product->setAttribute('catalog_unit_price', $unit);
        $product->setAttribute('catalog_has_discount', $unit < $product->final_price);

        return $product;
    }

    /**
     * @param  iterable<Product>  $products
     */
    public function decorateProducts(iterable $products): void
    {
        foreach ($products as $product) {
            $this->decorateProduct($product);
        }
    }

    public function getDisplayPrice(Product $product): int
    {
        return $product->getAttribute('catalog_unit_price') ?? $this->getUnitPrice($product, 1);
    }

    public function getActiveFreeShippingThreshold(): ?int
    {
        $rule = $this->activeCatalogRules()
            ->where('rule_type', 'free_shipping_threshold')
            ->sortByDesc('priority')
            ->first();

        if (! $rule || ! $rule->min_order_amount) {
            return null;
        }

        return (int) $rule->min_order_amount;
    }

    public function freeShippingProgress(int $subtotal): array
    {
        $threshold = $this->getActiveFreeShippingThreshold();

        if (! $threshold) {
            return ['threshold' => null, 'remaining' => 0, 'percent' => 100, 'qualified' => false];
        }

        $remaining = max(0, $threshold - $subtotal);
        $percent = min(100, (int) round(($subtotal / $threshold) * 100));

        return [
            'threshold' => $threshold,
            'remaining' => $remaining,
            'percent' => $percent,
            'qualified' => $subtotal >= $threshold,
        ];
    }

    public function validateCoupon(string $code, ?int $customerId = null): ?string
    {
        $rule = CartRule::where('coupon_code', $code)->where('is_active', true)->first();

        if (! $rule || ! $rule->isActiveNow()) {
            return 'Kupon tidak valid atau sudah kedaluwarsa.';
        }

        if ($rule->uses_per_coupon > 0) {
            $totalUses = CartRuleUsage::where('cart_rule_id', $rule->id)->sum('times_used');
            if ($totalUses >= $rule->uses_per_coupon) {
                return 'Kupon sudah mencapai batas penggunaan.';
            }
        }

        if ($customerId && $rule->uses_per_customer > 0) {
            $customerUses = CartRuleUsage::where('cart_rule_id', $rule->id)
                ->where('customer_id', $customerId)
                ->value('times_used') ?? 0;
            if ($customerUses >= $rule->uses_per_customer) {
                return 'Anda sudah menggunakan kupon ini.';
            }
        }

        return null;
    }

    public function recordCouponUsage(?CartRule $rule, ?int $customerId): void
    {
        if (! $rule || ! $rule->coupon_code) {
            return;
        }

        CartRuleUsage::updateOrCreate(
            ['cart_rule_id' => $rule->id, 'customer_id' => $customerId],
            []
        )->increment('times_used');
    }

    /**
     * @param  array<int, array{product: Product, qty: int}>  $lineItems
     */
    private function findApplicableCartRule(
        array $lineItems,
        int $subtotal,
        ?string $couponCode,
        ?int $customerId
    ): ?CartRule {
        $query = CartRule::where('is_active', true)
            ->orderByDesc('priority');

        if ($couponCode) {
            $query->where('coupon_code', $couponCode);
        } else {
            $query->whereNull('coupon_code');
        }

        $rules = $query->get()->filter(fn (CartRule $r) => $r->isActiveNow());

        foreach ($rules as $rule) {
            if ($rule->coupon_code && $error = $this->validateCoupon($rule->coupon_code, $customerId)) {
                continue;
            }

            if ($rule->min_order_amount && $subtotal < (int) $rule->min_order_amount) {
                continue;
            }

            if ($rule->category_ids && ! $this->cartMatchesCategories($lineItems, $rule->category_ids)) {
                continue;
            }

            return $rule;
        }

        return null;
    }

    /**
     * @param  array<int, array{product: Product, qty: int}>  $lineItems
     */
    private function calculateCartRuleDiscount(CartRule $rule, array $lineItems, int $subtotal): int
    {
        $eligibleSubtotal = $subtotal;

        if ($rule->category_ids) {
            $expandedIds = $this->categoryTree->expandIds($rule->category_ids);
            $eligibleSubtotal = 0;
            foreach ($lineItems as $line) {
                if (in_array($line['product']->category_id, $expandedIds, true)) {
                    $unit = $line['unit_price'] ?? $this->getUnitPrice($line['product'], $line['qty']);
                    $eligibleSubtotal += $unit * $line['qty'];
                }
            }
        }

        if ($rule->discount_type === 'percentage') {
            $discount = (int) round($eligibleSubtotal * ((float) $rule->discount_amount / 100));
            if ($rule->max_discount) {
                $discount = min($discount, (int) $rule->max_discount);
            }

            return $discount;
        }

        if ($rule->discount_type === 'fixed') {
            return min($eligibleSubtotal, (int) $rule->discount_amount);
        }

        return 0;
    }

    /**
     * @param  array<int, array{product: Product, qty: int}>  $lineItems
     */
    private function calculateBuyXGetYSavings(array $lineItems): int
    {
        $savings = 0;

        foreach ($this->activeCatalogRules()->where('rule_type', 'buy_x_get_y') as $rule) {
            if (! $rule->buy_qty || ! $rule->get_qty) {
                continue;
            }

            foreach ($lineItems as $line) {
                if (! $this->catalogRuleMatchesProduct($rule, $line['product'])) {
                    continue;
                }

                $sets = intdiv($line['qty'], $rule->buy_qty + $rule->get_qty);
                if ($sets <= 0) {
                    continue;
                }

                $freeItems = $sets * $rule->get_qty;
                $unit = $line['unit_price'] ?? $this->getUnitPrice($line['product'], 1);
                $discountPercent = (float) ($rule->get_discount_percent ?? 100);
                $savings += (int) round($freeItems * $unit * ($discountPercent / 100));
            }
        }

        return $savings;
    }

    private function applyCatalogDiscount(int $base, CatalogRule $rule): int
    {
        if ($rule->discount_type === 'fixed') {
            return max(0, $base - (int) $rule->discount_amount);
        }

        $percent = (float) $rule->discount_amount;
        $discounted = (int) round($base * (1 - $percent / 100));

        return max(0, $discounted);
    }

    private function catalogRuleMatchesProduct(CatalogRule $rule, Product $product): bool
    {
        if ($rule->product_ids && in_array($product->id, $rule->product_ids, true)) {
            return true;
        }

        if ($rule->category_ids) {
            $expandedIds = $this->categoryTree->expandIds($rule->category_ids);

            return in_array($product->category_id, $expandedIds, true);
        }

        return ! $rule->product_ids && ! $rule->category_ids;
    }

    /**
     * @param  array<int, array{product: Product, qty: int}>  $lineItems
     * @param  list<int>  $categoryIds
     */
    private function cartMatchesCategories(array $lineItems, array $categoryIds): bool
    {
        $expandedIds = $this->categoryTree->expandIds($categoryIds);

        foreach ($lineItems as $line) {
            if (in_array($line['product']->category_id, $expandedIds, true)) {
                return true;
            }
        }

        return false;
    }

    private function activeCatalogRules(): Collection
    {
        if ($this->catalogRules === null) {
            $this->catalogRules = CatalogRule::where('is_active', true)
                ->orderByDesc('priority')
                ->get()
                ->filter(fn (CatalogRule $r) => $r->isActiveNow())
                ->values();
        }

        return $this->catalogRules;
    }
}
