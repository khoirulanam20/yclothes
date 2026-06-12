<?php

namespace App\Support\Serializers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;

class PosProductSerializer
{
    public static function listItem(Product $product, ?int $warehouseId = null): array
    {
        $inventory = app(InventoryService::class);
        $stock = $warehouseId
            ? $inventory->getAvailableStockAtWarehouse($product, null, $warehouseId)
            : $inventory->getAvailableStock($product);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'type' => $product->type?->value ?? $product->type,
            'price' => (int) $product->price,
            'salePrice' => $product->sale_price !== null ? (int) $product->sale_price : null,
            'finalPrice' => (int) $product->final_price,
            'imageUrl' => $product->image_url,
            'stock' => $stock,
            'isPurchasable' => $warehouseId
                ? $inventory->canOrderAtWarehouse($product, null, 1, $warehouseId)
                : $inventory->canOrder($product, null, 1),
        ];
    }

    public static function detail(Product $product, ?int $warehouseId = null): array
    {
        $data = self::listItem($product, $warehouseId);
        $data['variants'] = $product->relationLoaded('activeVariants')
            ? $product->activeVariants
                ->map(fn (ProductVariant $variant) => self::variant($variant, $product, $warehouseId))
                ->values()
                ->all()
            : [];

        return $data;
    }

    public static function variant(
        ProductVariant $variant,
        ?Product $product = null,
        ?int $warehouseId = null,
    ): array {
        $product ??= $variant->parentProduct;
        $inventory = app(InventoryService::class);
        $stock = $product && $warehouseId
            ? $inventory->getAvailableStockAtWarehouse($product, $variant, $warehouseId)
            : ($product ? $inventory->getAvailableStock($product, $variant) : $variant->stock);

        $attrs = $variant->attributes ?? [];

        return [
            'id' => $variant->id,
            'parentProductId' => $variant->parent_product_id,
            'sku' => $variant->sku,
            'name' => $variant->name,
            'price' => $variant->price !== null ? (int) $variant->price : null,
            'finalPrice' => (int) $variant->final_price,
            'size' => $attrs['size'] ?? null,
            'color' => $attrs['color'] ?? null,
            'imageUrl' => $variant->image_url,
            'stock' => $stock,
            'isPurchasable' => $product && $warehouseId
                ? $inventory->canOrderAtWarehouse($product, $variant, 1, $warehouseId)
                : ($product ? $inventory->canOrder($product, $variant, 1) : false),
        ];
    }

    public static function skuLookup(Product|ProductVariant $match, ?int $warehouseId = null): array
    {
        if ($match instanceof ProductVariant) {
            $product = $match->parentProduct;

            return [
                'matchType' => 'variant',
                'product' => $product ? self::detail($product->loadMissing('activeVariants'), $warehouseId) : null,
                'variant' => self::variant($match, $product, $warehouseId),
            ];
        }

        return [
            'matchType' => 'product',
            'product' => self::detail($match->loadMissing('activeVariants'), $warehouseId),
            'variant' => null,
        ];
    }
}
