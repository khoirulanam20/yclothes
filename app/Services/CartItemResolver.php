<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ModelSerializer;

class CartItemResolver
{
    /**
     * @return array{
     *   product: Product,
     *   variant: ?ProductVariant,
     *   unit_price: int,
     *   size: ?string,
     *   color: ?string,
     *   variant_label: ?string,
     *   sku: ?string,
     *   product_name: string
     * }|null
     */
    public function resolve(array $item): ?array
    {
        if (! empty($item['variant_id'])) {
            $variant = ProductVariant::with('parentProduct')->find($item['variant_id']);
            if (! $variant || ! $variant->is_active || ! $variant->parentProduct) {
                return null;
            }

            $attrs = $variant->attributes ?? [];

            return [
                'product' => $variant->parentProduct,
                'variant' => $variant,
                'unit_price' => $variant->final_price,
                'size' => $attrs['size'] ?? null,
                'color' => $attrs['color'] ?? null,
                'variant_label' => ModelSerializer::variantLabel($variant),
                'sku' => $variant->sku,
                'product_name' => $variant->name,
            ];
        }

        $product = Product::find($item['id'] ?? null);
        if (! $product) {
            return null;
        }

        return [
            'product' => $product,
            'variant' => null,
            'unit_price' => $product->final_price,
            'size' => $item['size'] ?? null,
            'color' => $item['color'] ?? null,
            'variant_label' => null,
            'sku' => null,
            'product_name' => $product->name,
        ];
    }
}
