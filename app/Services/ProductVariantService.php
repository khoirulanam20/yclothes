<?php

namespace App\Services;

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Str;

class ProductVariantService
{
    public function syncFromProduct(Product $product): void
    {
        if ($product->type !== ProductType::Configurable) {
            $product->variants()->delete();

            return;
        }

        $sizes = $product->sizes ?: [null];
        $colors = $product->colors ?: [null];

        $existing = $product->variants()->get()->keyBy(function (ProductVariant $variant) {
            $attrs = $variant->attributes ?? [];

            return ($attrs['size'] ?? '').'|'.($attrs['color'] ?? '');
        });

        $activeKeys = [];

        foreach ($sizes as $size) {
            foreach ($colors as $color) {
                $colorName = is_array($color) ? ($color['name'] ?? $color['hex'] ?? '') : $color;
                $colorHex = is_array($color) ? ($color['hex'] ?? '') : $color;
                $key = ($size ?? '').'|'.($colorName ?? '');
                $activeKeys[] = $key;

                $attributes = array_filter([
                    'size' => $size,
                    'color' => $colorName,
                    'color_hex' => $colorHex,
                ], fn ($v) => $v !== null && $v !== '');

                $name = $product->name;
                if ($size) {
                    $name .= ' - '.$size;
                }
                if ($colorName) {
                    $name .= ' / '.$colorName;
                }

                $skuBase = Str::slug($product->slug.'-'.($size ?: 'default').'-'.($colorName ?: 'default'));

                if ($existing->has($key)) {
                    $existing[$key]->update([
                        'name' => $name,
                        'attributes' => $attributes,
                    ]);
                } else {
                    $sku = $this->uniqueSku($skuBase);
                    $product->variants()->create([
                        'sku' => $sku,
                        'name' => $name,
                        'attributes' => $attributes,
                        'is_active' => true,
                    ]);
                }
            }
        }

        $product->variants()->get()->each(function (ProductVariant $variant) use ($activeKeys) {
            $attrs = $variant->attributes ?? [];
            $key = ($attrs['size'] ?? '').'|'.($attrs['color'] ?? '');
            if (! in_array($key, $activeKeys, true)) {
                $variant->delete();
            }
        });
    }

    private function uniqueSku(string $base): string
    {
        $sku = $base;
        $counter = 1;

        while (ProductVariant::where('sku', $sku)->exists()) {
            $sku = $base.'-'.$counter;
            $counter++;
        }

        return $sku;
    }
}
