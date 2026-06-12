<?php

namespace App\Services;

use App\Models\CartRule;
use Illuminate\Validation\ValidationException;

class PosPricingService
{
    public function __construct(
        private CartItemResolver $cartItemResolver,
        private PromotionEngine $promotionEngine,
        private TaxCalculator $taxCalculator,
        private InventoryService $inventoryService,
    ) {}

    /**
     * @param  list<array{product_id: int, variant_id?: int|null, qty: int, discount_percent?: int}>  $items
     * @return array{
     *   items: array,
     *   line_items: array,
     *   subtotal: int,
     *   tax_amount: int,
     *   tax_breakdown: array,
     *   discount_amount: int,
     *   coupon_code: ?string,
     *   cart_rule: ?CartRule,
     *   tax_included: bool,
     *   grand_total: int,
     *   stock_warnings: list<array{product_id: int, variant_id: ?int, message: string}>
     * }
     */
    public function build(
        array $items,
        int $warehouseId,
        ?string $couponCode = null,
        ?int $customerId = null,
        ?string $customerEmail = null,
    ): array {
        $resolvedItems = [];
        $lineItems = [];
        $subtotal = 0;
        $lineDiscountTotal = 0;
        $stockWarnings = [];

        foreach ($items as $index => $item) {
            $resolved = $this->cartItemResolver->resolve([
                'id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'qty' => $item['qty'],
            ]);

            if (! $resolved) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'Produk tidak ditemukan atau tidak aktif.',
                ]);
            }

            $product = $resolved['product'];
            if (! $product->is_active) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'Produk tidak aktif.',
                ]);
            }

            $qty = max(1, (int) $item['qty']);
            $this->promotionEngine->decorateProduct($product);
            $unitPrice = $resolved['variant']
                ? $resolved['unit_price']
                : $this->promotionEngine->getUnitPrice($product, $qty);
            $discountPercent = min(100, max(0, (int) ($item['discount_percent'] ?? 0)));
            $lineGross = $unitPrice * $qty;
            $lineDiscount = (int) round($lineGross * ($discountPercent / 100));
            $lineSubtotal = $lineGross - $lineDiscount;
            $lineDiscountTotal += $lineDiscount;

            $row = [
                'product' => $product,
                'variant' => $resolved['variant'],
                'size' => $resolved['size'],
                'color' => $resolved['color'],
                'sku' => $resolved['sku'],
                'product_name' => $resolved['product_name'],
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'subtotal' => $lineSubtotal,
            ];

            $resolvedItems[] = $row;
            $lineItems[] = [
                'product' => $product,
                'variant' => $resolved['variant'],
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'subtotal' => $lineSubtotal,
                'product_name' => $resolved['product_name'],
            ];
            $subtotal += $lineSubtotal;

            if (
                $this->inventoryService->tracksStock($product, $resolved['variant'])
                && ! $this->inventoryService->effectiveAllowBackorder($product, $resolved['variant'])
                && ! $this->inventoryService->canOrderAtWarehouse($product, $resolved['variant'], $qty, $warehouseId)
            ) {
                $available = $this->inventoryService->getAvailableStockAtWarehouse(
                    $product,
                    $resolved['variant'],
                    $warehouseId,
                );
                $stockWarnings[] = [
                    'product_id' => $product->id,
                    'variant_id' => $resolved['variant']?->id,
                    'message' => "Stok tidak mencukupi untuk {$resolved['product_name']} (tersedia: {$available}).",
                ];
            }
        }

        if ($resolvedItems === []) {
            throw ValidationException::withMessages([
                'items' => 'Minimal satu item diperlukan.',
            ]);
        }

        $promo = $this->promotionEngine->applyToCart($lineItems, $subtotal, $couponCode, $customerId);

        if ($couponCode && ! $promo['cart_rule']) {
            $couponCode = null;
        }

        if ($couponCode && $promo['cart_rule']) {
            $couponError = $this->promotionEngine->validateCoupon(
                $couponCode,
                $customerId,
                $customerEmail ?? '',
            );

            if ($couponError) {
                throw ValidationException::withMessages([
                    'coupon_code' => $couponError,
                ]);
            }
        }

        $taxIncluded = filter_var(setting('tax_included', '0'), FILTER_VALIDATE_BOOLEAN);
        $ratio = $subtotal > 0
            ? max(0, $subtotal - $promo['discount_amount']) / $subtotal
            : 1;
        $taxLines = array_map(fn ($line) => [
            'product' => $line['product'],
            'qty' => $line['qty'],
            'subtotal' => (int) round($line['subtotal'] * $ratio),
        ], $lineItems);
        $taxResult = $this->taxCalculator->calculate($taxLines, null);

        $grandTotal = $this->taxCalculator->grandTotalWithTax(
            $subtotal,
            $taxResult['tax_amount'],
            0,
            $promo['discount_amount'],
            $taxIncluded,
        );

        return [
            'items' => $resolvedItems,
            'line_items' => $lineItems,
            'subtotal' => $subtotal,
            'tax_amount' => $taxResult['tax_amount'],
            'tax_breakdown' => $taxResult['breakdown'],
            'discount_amount' => $promo['discount_amount'] + $lineDiscountTotal,
            'coupon_code' => $couponCode,
            'cart_rule' => $promo['cart_rule'],
            'tax_included' => $taxIncluded,
            'grand_total' => $grandTotal,
            'stock_warnings' => $stockWarnings,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function previewLineItems(array $pricing): array
    {
        return array_map(fn (array $row) => [
            'productId' => $row['product']->id,
            'variantId' => $row['variant']?->id,
            'sku' => $row['sku'],
            'productName' => $row['product_name'],
            'qty' => $row['qty'],
            'unitPrice' => $row['unit_price'],
            'subtotal' => $row['subtotal'],
            'size' => $row['size'],
            'color' => $row['color'],
        ], $pricing['items']);
    }
}
