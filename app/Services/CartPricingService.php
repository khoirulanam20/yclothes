<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ShippingCost;
use Illuminate\Support\Facades\Auth;

class CartPricingService
{
    public function __construct(
        private CartService $cartService,
        private PromotionEngine $promotionEngine,
        private TaxCalculator $taxCalculator,
        private InventoryService $inventoryService,
        private CartItemResolver $cartItemResolver,
    ) {}

    /**
     * @return array{
     *   items: array,
     *   line_items: array,
     *   subtotal: int,
     *   tax_amount: int,
     *   tax_breakdown: array,
     *   discount_amount: int,
     *   free_shipping: bool,
     *   coupon_code: ?string,
     *   cart_rule: ?\App\Models\CartRule,
     *   free_shipping_progress: array,
     *   backorder_notes: array,
     *   tax_included: bool,
     *   total_weight: int,
     *   total_qty: int
     * }
     */
    public function build(?string $shippingCity = null, ?string $couponCode = null, ?array $onlyKeys = null): array
    {
        $cart = $this->cartService->get();

        if ($onlyKeys !== null) {
            $cart = array_intersect_key($cart, array_flip($onlyKeys));
        }

        $couponCode ??= session(CartService::COUPON_SESSION_KEY);
        $customerId = Auth::guard('customer')->id();

        $items = [];
        $lineItems = [];
        $subtotal = 0;
        $totalWeight = 0;
        $totalQty = 0;

        foreach ($cart as $key => $item) {
            $resolved = $this->cartItemResolver->resolve($item);
            if (! $resolved) {
                continue;
            }

            $product = $resolved['product'];
            $this->promotionEngine->decorateProduct($product);
            $unitPrice = $resolved['variant']
                ? $resolved['unit_price']
                : $this->promotionEngine->getUnitPrice($product, $item['qty']);
            $lineSubtotal = $unitPrice * $item['qty'];

            $row = [
                'key' => $key,
                'product' => $product,
                'variant' => $resolved['variant'],
                'size' => $resolved['size'],
                'color' => $resolved['color'],
                'variant_label' => $resolved['variant_label'],
                'sku' => $resolved['sku'],
                'product_name' => $resolved['product_name'],
                'qty' => $item['qty'],
                'unit_price' => $unitPrice,
                'subtotal' => $lineSubtotal,
            ];

            $items[] = $row;
            $lineItems[] = [
                'product' => $product,
                'variant' => $resolved['variant'],
                'qty' => $item['qty'],
                'unit_price' => $unitPrice,
                'subtotal' => $lineSubtotal,
            ];
            $subtotal += $lineSubtotal;
            $totalWeight += ($product->weight ?? 0) * $item['qty'];
            $totalQty += $item['qty'];
        }

        $promo = $this->promotionEngine->applyToCart($lineItems, $subtotal, $couponCode, $customerId);

        if ($couponCode && ! $promo['cart_rule']) {
            session()->forget(CartService::COUPON_SESSION_KEY);
            $couponCode = null;
        }

        $taxIncluded = filter_var(setting('tax_included', '0'), FILTER_VALIDATE_BOOLEAN);
        $ratio = $subtotal > 0
            ? max(0, $subtotal - $promo['discount_amount']) / $subtotal
            : 1;
        $taxLines = array_map(fn ($l) => [
            'product' => $l['product'],
            'qty' => $l['qty'],
            'subtotal' => (int) round($l['subtotal'] * $ratio),
        ], $lineItems);
        $taxResult = $this->taxCalculator->calculate($taxLines, $shippingCity);

        return [
            'items' => $items,
            'line_items' => $lineItems,
            'subtotal' => $subtotal,
            'tax_amount' => $taxResult['tax_amount'],
            'tax_breakdown' => $taxResult['breakdown'],
            'discount_amount' => $promo['discount_amount'],
            'free_shipping' => $promo['free_shipping'],
            'coupon_code' => $couponCode,
            'cart_rule' => $promo['cart_rule'],
            'free_shipping_progress' => $this->promotionEngine->freeShippingProgress($subtotal),
            'backorder_notes' => $this->inventoryService->hasBackorderItems($lineItems),
            'tax_included' => $taxIncluded,
            'total_weight' => $totalWeight,
            'total_qty' => $totalQty,
        ];
    }

    public function calculateShipping(ShippingCost $shipping, int $totalWeight, bool $freeShipping): int
    {
        if ($freeShipping) {
            return 0;
        }

        $cost = $shipping->cost;
        if ($shipping->cost_per_kg && $totalWeight > 0) {
            $totalKg = max(1, ceil($totalWeight / 1000));
            $cost = $shipping->cost + ($totalKg - 1) * $shipping->cost_per_kg;
        }

        return $cost;
    }

    public function grandTotal(array $pricing, int $shippingCost): int
    {
        return $this->taxCalculator->grandTotalWithTax(
            $pricing['subtotal'],
            $pricing['tax_amount'],
            $shippingCost,
            $pricing['discount_amount'],
            $pricing['tax_included'],
        );
    }
}
