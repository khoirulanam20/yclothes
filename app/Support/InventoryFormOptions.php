<?php

namespace App\Support;

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Validation\ValidationException;

class InventoryFormOptions
{
    /** @return list<array{id: int, name: string, sku: string|null, type: string, variants: list<array{id: int, sku: string, label: string|null}>}> */
    public static function products(): array
    {
        return Product::with(['variants' => fn ($query) => $query->orderBy('sku')])
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'type' => $product->type?->value ?? $product->type,
                    'variants' => $product->isConfigurable()
                        ? $product->variants->map(fn (ProductVariant $variant) => [
                            'id' => $variant->id,
                            'sku' => $variant->sku,
                            'label' => self::variantLabel($variant) ?? $variant->name ?? $variant->sku,
                        ])->values()->all()
                        : [],
                ];
            })
            ->values()
            ->all();
    }

    public static function variantLabel(ProductVariant $variant): ?string
    {
        $attributes = $variant->attributes ?? [];
        $parts = array_filter([
            $attributes['size'] ?? null,
            $attributes['color'] ?? null,
        ]);

        if ($parts !== []) {
            return implode(' / ', $parts);
        }

        return $variant->name ?: null;
    }

    public static function resolveVariantId(Product $product, mixed $variantId): ?int
    {
        $variantId = $variantId ? (int) $variantId : null;

        if ($product->type === ProductType::Configurable) {
            if (! $variantId) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'Pilih varian untuk produk dengan varian.',
                ]);
            }

            $variant = ProductVariant::where('id', $variantId)
                ->where('parent_product_id', $product->id)
                ->first();

            if (! $variant) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'Varian tidak valid untuk produk ini.',
                ]);
            }

            return $variant->id;
        }

        return null;
    }
}
