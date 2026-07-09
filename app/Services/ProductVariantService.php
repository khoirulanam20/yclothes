<?php

namespace App\Services;

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductVariantService
{
    public function __construct(
        private ProductImageService $imageService,
        private ProductAttributeService $attributeService,
    ) {}

    public function syncFromProduct(Product $product, bool $replaceAll = false): void
    {
        if ($product->type !== ProductType::Configurable) {
            $this->purgeVariants($product);

            return;
        }

        $axes = $this->attributeService->variantAxesForFamily($product->attribute_family_id);
        if ($axes->isEmpty()) {
            $this->purgeVariants($product);

            return;
        }

        $axisData = $this->attributeService->axisValuesForProduct($product);
        $combinations = $this->cartesianProduct($axisData);
        $newKeys = array_map(fn (array $combo) => $this->combinationKey($combo), $combinations);

        if ($replaceAll || $this->shouldReplaceAllVariants($product, $axes, $newKeys)) {
            $this->purgeVariants($product);
            $existing = collect();
        } else {
            $existing = $product->variants()->get()->keyBy(
                fn (ProductVariant $variant) => $this->keyFromStoredAttributes($variant->attributes ?? [], $axes)
            );
        }

        $activeKeys = [];

        foreach ($combinations as $combo) {
            $key = $this->combinationKey($combo);
            $activeKeys[] = $key;

            $attributes = $this->buildAttributesJson($combo);
            $name = $this->buildName($product, $combo);
            $skuBase = $this->buildSkuBase($product, $combo);

            if ($existing->has($key)) {
                $existing[$key]->update([
                    'name' => $name,
                    'attributes' => $attributes,
                ]);
            } else {
                $product->variants()->create([
                    'sku' => $this->uniqueSku($skuBase),
                    'name' => $name,
                    'attributes' => $attributes,
                    'is_active' => true,
                ]);
            }
        }

        $product->variants()->get()->each(function (ProductVariant $variant) use ($activeKeys, $axes) {
            $key = $this->keyFromStoredAttributes($variant->attributes ?? [], $axes);
            if (! in_array($key, $activeKeys, true)) {
                $this->deleteVariant($variant);
                $variant->delete();
            }
        });
    }

    /**
     * @param  list<array{code: string, values: list<mixed>}>  $axisData
     * @return list<array<string, mixed>>
     */
    public function cartesianProduct(array $axisData): array
    {
        $result = [[]];

        foreach ($axisData as $axis) {
            $next = [];
            foreach ($result as $partial) {
                foreach ($axis['values'] as $value) {
                    $next[] = array_merge($partial, [$axis['code'] => $value]);
                }
            }
            $result = $next;
        }

        return $result;
    }

    /**
     * @param  list<string>  $newKeys
     */
    private function shouldReplaceAllVariants(Product $product, Collection $axes, array $newKeys): bool
    {
        $variants = $product->variants()->get();
        if ($variants->isEmpty()) {
            return false;
        }

        $axisCodes = $axes->pluck('code')->all();
        $existingKeys = $variants
            ->map(fn (ProductVariant $variant) => $this->keyFromStoredAttributes($variant->attributes ?? [], $axes))
            ->unique()
            ->values();
        $newKeysCollection = collect($newKeys)->unique()->values();

        if ($newKeysCollection->intersect($existingKeys)->isEmpty()) {
            return true;
        }

        if ($existingKeys->diff($newKeysCollection)->isNotEmpty() && $newKeysCollection->diff($existingKeys)->isNotEmpty()) {
            return true;
        }

        $allowedByCode = $this->allowedAxisTokensByCode($product, $axes);
        foreach ($variants as $variant) {
            $attrs = $variant->attributes ?? [];
            foreach ($axes as $attribute) {
                $code = $attribute->code;
                if ($code === 'color') {
                    $stored = $attrs['color'] ?? null;
                } else {
                    $stored = $attrs[$code] ?? null;
                }

                if ($stored === null || $stored === '') {
                    continue;
                }

                if (! in_array((string) $stored, $allowedByCode[$code] ?? [], true)) {
                    return true;
                }
            }

            foreach (array_keys($attrs) as $code) {
                if (str_ends_with((string) $code, '_hex')) {
                    continue;
                }
                if (! in_array($code, $axisCodes, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<string, list<string>>
     */
    private function allowedAxisTokensByCode(Product $product, Collection $axes): array
    {
        $values = $this->attributeService->valuesForProduct($product);
        $allowed = [];

        foreach ($axes as $attribute) {
            $allowed[$attribute->code] = collect(
                $this->attributeService->normalizeAxisValues($attribute, $values[$attribute->code] ?? null)
            )->map(function ($value) use ($attribute) {
                if ($attribute->code === 'color' && is_array($value)) {
                    return (string) ($value['name'] ?? $value['hex'] ?? '');
                }

                return is_array($value) ? (string) ($value['name'] ?? '') : (string) $value;
            })->filter()->values()->all();
        }

        return $allowed;
    }

    private function purgeVariants(Product $product): void
    {
        $product->variants()->get()->each(function (ProductVariant $variant) {
            $this->deleteVariant($variant);
            $variant->delete();
        });
    }

    private function deleteVariant(ProductVariant $variant): void
    {
        foreach ($variant->resolved_image_paths as $path) {
            $this->imageService->deletePath($path);
        }
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

    /**
     * @param  array<string, mixed>  $combo
     */
    private function combinationKey(array $combo): string
    {
        ksort($combo);
        $parts = [];

        foreach ($combo as $code => $value) {
            $parts[] = $code.':'.$this->axisValueToken($code, $value);
        }

        return implode('|', $parts);
    }

    private function axisValueToken(string $code, mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($code === 'color' && is_array($value)) {
            return (string) ($value['name'] ?? $value['hex'] ?? '');
        }

        return is_array($value) ? (string) ($value['name'] ?? '') : (string) $value;
    }

    /**
     * @param  array<string, mixed>  $combo
     * @return array<string, string>
     */
    private function buildAttributesJson(array $combo): array
    {
        $attributes = [];

        foreach ($combo as $code => $value) {
            if ($value === null) {
                continue;
            }

            if ($code === 'color' && is_array($value)) {
                $attributes['color'] = $value['name'] ?? $value['hex'] ?? '';
                if (! empty($value['hex'])) {
                    $attributes['color_hex'] = $value['hex'];
                }
            } else {
                $attributes[$code] = is_array($value) ? (string) ($value['name'] ?? '') : (string) $value;
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $combo
     */
    private function buildName(Product $product, array $combo): string
    {
        $parts = [];

        foreach ($combo as $code => $value) {
            if ($value === null) {
                continue;
            }

            if ($code === 'color' && is_array($value)) {
                $parts[] = $value['name'] ?? $value['hex'] ?? '';
            } else {
                $parts[] = is_array($value) ? (string) ($value['name'] ?? '') : (string) $value;
            }
        }

        if ($parts === []) {
            return $product->name;
        }

        return $product->name.' - '.implode(' / ', $parts);
    }

    /**
     * @param  array<string, mixed>  $combo
     */
    private function buildSkuBase(Product $product, array $combo): string
    {
        $segments = [Str::slug($product->slug)];
        ksort($combo);

        foreach ($combo as $code => $value) {
            if ($value === null) {
                $segments[] = 'default';
                continue;
            }

            $label = $this->axisValueToken($code, $value);
            $segments[] = Str::slug($label !== '' ? $label : 'default');
        }

        return implode('-', $segments);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function keyFromStoredAttributes(array $attrs, Collection $axes): string
    {
        $combo = [];

        foreach ($axes as $attribute) {
            $code = $attribute->code;
            if ($code === 'color') {
                $name = $attrs['color'] ?? null;
                $combo[$code] = $name !== null && $name !== ''
                    ? ['name' => $name, 'hex' => $attrs['color_hex'] ?? '']
                    : null;
            } else {
                $combo[$code] = $attrs[$code] ?? null;
            }
        }

        return $this->combinationKey($combo);
    }
}
