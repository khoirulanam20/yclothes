<?php

namespace App\Services;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\AttributeFamily;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProductAttributeService
{
    public function familyAttributes(?int $familyId): Collection
    {
        if (! $familyId) {
            return collect();
        }

        $family = AttributeFamily::find($familyId);
        if (! $family) {
            return collect();
        }

        return $family->attributes()->with('options')->orderBy('sort_order')->get();
    }

    public function variantAxesForFamily(?int $familyId): Collection
    {
        if (! $familyId) {
            return collect();
        }

        $family = AttributeFamily::find($familyId);
        if (! $family) {
            return collect();
        }

        return $family->attributes()
            ->wherePivot('is_variant_axis', true)
            ->with('options')
            ->orderBy('sort_order')
            ->get();
    }

    public function familyHasVariantAxes(?int $familyId): bool
    {
        return $this->variantAxesForFamily($familyId)->isNotEmpty();
    }

    public function syncFromRequest(Product $product, Request $request): void
    {
        $attributes = $this->familyAttributes($product->attribute_family_id);

        foreach ($attributes as $attribute) {
            $key = 'attributes.'.$attribute->code;
            $raw = $request->input($key);

            if ($raw === null || $raw === '' || $raw === []) {
                ProductAttributeValue::where('product_id', $product->id)
                    ->where('attribute_id', $attribute->id)
                    ->delete();

                continue;
            }

            $value = $this->normalizeInputValue($attribute, $raw);

            ProductAttributeValue::updateOrCreate(
                ['product_id' => $product->id, 'attribute_id' => $attribute->id],
                ['value' => $value]
            );
        }
    }

    public function validationRules(?int $familyId): array
    {
        $rules = [];

        foreach ($this->familyAttributes($familyId) as $attribute) {
            $key = 'attributes.'.$attribute->code;
            $rule = $attribute->is_required ? 'required' : 'nullable';

            $rules[$key] = match (true) {
                $attribute->type === AttributeType::Boolean => $rule.'|boolean',
                $attribute->type === AttributeType::Decimal => $rule.'|numeric',
                $attribute->type === AttributeType::Price => $rule.'|integer|min:0',
                $this->isMultivalueAttribute($attribute) => $rule.'|array',
                $attribute->type === AttributeType::Select => $rule.'|string|max:255',
                default => $rule.'|string',
            };
        }

        return $rules;
    }

    public function definitionsForFamily(?int $familyId): array
    {
        return $this->familyAttributes($familyId)->map(function (Attribute $attribute) {
            return [
                'id' => $attribute->id,
                'code' => $attribute->code,
                'name' => $attribute->name,
                'type' => $attribute->type->value,
                'isRequired' => $attribute->is_required,
                'isFilterable' => $attribute->is_filterable,
                'isVariantAxis' => (bool) $attribute->pivot?->is_variant_axis,
                'options' => $attribute->options->map(fn ($o) => [
                    'id' => $o->id,
                    'name' => $o->name,
                ])->values()->all(),
            ];
        })->values()->all();
    }

    public function valuesForProduct(Product $product): array
    {
        $map = $this->valueMapForProduct($product);
        $decoded = [];

        foreach ($this->familyAttributes($product->attribute_family_id) as $attribute) {
            $raw = $map[$attribute->code] ?? null;
            if ($raw === null) {
                $decoded[$attribute->code] = null;
                continue;
            }

            if ($this->isMultivalueAttribute($attribute)) {
                if ($attribute->code === 'color') {
                    $colors = json_decode($raw, true);
                    $decoded[$attribute->code] = is_array($colors) ? $colors : [];
                } else {
                    $items = json_decode($raw, true) ?: [];
                    $decoded[$attribute->code] = $this->filterMultiselectValues($attribute, $items);
                }
            } elseif ($attribute->type === AttributeType::Boolean) {
                $decoded[$attribute->code] = filter_var($raw, FILTER_VALIDATE_BOOLEAN);
            } else {
                $decoded[$attribute->code] = $raw;
            }
        }

        return $decoded;
    }

    public function axisValuesForProduct(Product $product): array
    {
        $values = $this->valuesForProduct($product);
        $axes = [];

        foreach ($this->variantAxesForFamily($product->attribute_family_id) as $attribute) {
            $raw = $values[$attribute->code] ?? null;
            $normalized = $this->normalizeAxisValues($attribute, $raw);

            $axes[] = [
                'attribute' => $attribute,
                'code' => $attribute->code,
                'values' => $normalized !== [] ? $normalized : [null],
            ];
        }

        return $axes;
    }

    public function variantAxisValuesChanged(Product $product, Request $request, int $familyId): bool
    {
        if ((int) $product->attribute_family_id !== $familyId) {
            return true;
        }

        $beforeAxes = $this->variantAxesForFamily($product->attribute_family_id);
        $afterAxes = $this->variantAxesForFamily($familyId);

        if ($beforeAxes->pluck('code')->sort()->values()->all() !== $afterAxes->pluck('code')->sort()->values()->all()) {
            return true;
        }

        $beforeValues = $this->valuesForProduct($product);

        foreach ($afterAxes as $attribute) {
            $beforeTokens = $this->snapshotTokensForAxis($attribute, $beforeValues[$attribute->code] ?? null);
            $afterTokens = $this->snapshotTokensForAxis(
                $attribute,
                $request->input('attributes.'.$attribute->code),
            );

            if ($beforeTokens !== $afterTokens) {
                return true;
            }
        }

        return false;
    }

    public function variantAxisSnapshotForProduct(Product $product): array
    {
        return $this->buildVariantAxisSnapshot(
            $this->variantAxesForFamily($product->attribute_family_id),
            $this->valuesForProduct($product),
        );
    }

    public function variantAxisSnapshotFromRequest(Request $request, ?int $familyId): array
    {
        $axes = $this->variantAxesForFamily($familyId);
        $values = [];

        foreach ($axes as $attribute) {
            $values[$attribute->code] = $request->input('attributes.'.$attribute->code);
        }

        return $this->buildVariantAxisSnapshot($axes, $values);
    }

    public function variantAxisSnapshotsDiffer(array $before, array $after): bool
    {
        return json_encode($before) !== json_encode($after);
    }

    /**
     * @param  Collection<int, Attribute>  $axes
     * @param  array<string, mixed>  $values
     */
    private function buildVariantAxisSnapshot(Collection $axes, array $values): array
    {
        $snapshot = [];

        foreach ($axes as $attribute) {
            $snapshot[$attribute->code] = $this->snapshotTokensForAxis($attribute, $values[$attribute->code] ?? null);
        }

        return [
            'axes' => $axes->pluck('code')->sort()->values()->all(),
            'values' => $snapshot,
        ];
    }

    private function snapshotTokensForAxis(Attribute $attribute, mixed $raw): array
    {
        return collect($this->normalizeAxisValues($attribute, $raw))
            ->map(function ($value) use ($attribute) {
                if ($attribute->code === 'color' && is_array($value)) {
                    return ($value['name'] ?? '').'|'.($value['hex'] ?? '');
                }

                return (string) $value;
            })
            ->sort()
            ->values()
            ->all();
    }

    public function valueMapForProduct(Product $product): array
    {
        $product->loadMissing(['attributeValues.attribute']);

        $map = [];
        foreach ($product->attributeValues as $pav) {
            if ($pav->attribute) {
                $map[$pav->attribute->code] = $pav->value;
            }
        }

        return $map;
    }

    public function isMultivalueAttribute(Attribute $attribute): bool
    {
        return $attribute->type === AttributeType::Multiselect
            || in_array($attribute->code, ['size', 'color'], true);
    }

  /**
     * @return list<array{hex: string, name: string}|string>
     */
    public function normalizeAxisValues(Attribute $attribute, mixed $raw): array
    {
        if ($attribute->code === 'color') {
            if (! is_array($raw) || $raw === []) {
                return [];
            }

            return array_values(array_filter(array_map(function ($item) {
                if (is_string($item)) {
                    return ['hex' => $item, 'name' => $item];
                }
                if (! is_array($item)) {
                    return null;
                }
                $hex = trim($item['hex'] ?? '');
                $name = trim($item['name'] ?? $hex);

                return $hex ? ['hex' => $hex, 'name' => $name] : null;
            }, $raw)));
        }

        if ($attribute->type === AttributeType::Multiselect || $attribute->code === 'size') {
            $items = is_array($raw)
                ? array_values(array_filter($raw, fn ($v) => $v !== '' && $v !== null))
                : [];

            return $this->filterMultiselectValues($attribute, $items);
        }

        return [];
    }

    private function normalizeInputValue(Attribute $attribute, mixed $raw): string
    {
        if ($attribute->type === AttributeType::Multiselect || $attribute->code === 'size') {
            $items = is_array($raw) ? array_values(array_filter($raw, fn ($v) => $v !== '' && $v !== null)) : [];
            $items = $this->filterMultiselectValues($attribute, $items);

            return json_encode($items);
        }

        if ($attribute->type === AttributeType::Boolean) {
            return $raw ? '1' : '0';
        }

        if ($attribute->code === 'color' && is_array($raw)) {
            $colors = array_values(array_filter(array_map(function ($item) {
                if (is_string($item)) {
                    return ['hex' => $item, 'name' => $item];
                }
                if (! is_array($item)) {
                    return null;
                }
                $hex = trim($item['hex'] ?? '');
                $name = trim($item['name'] ?? $hex);

                return $hex ? ['hex' => $hex, 'name' => $name] : null;
            }, $raw)));

            return json_encode($colors);
        }

        if ($attribute->code === 'color' && is_string($raw)) {
            $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
            $colors = array_values(array_filter(array_map(function ($line) {
                $line = trim($line);
                if (! $line) {
                    return null;
                }
                $parts = explode('|', $line, 2);
                $hex = trim($parts[0]);
                $name = isset($parts[1]) ? trim($parts[1]) : $hex;

                return $hex ? ['hex' => $hex, 'name' => $name] : null;
            }, $lines)));

            return json_encode($colors);
        }

        return is_array($raw) ? json_encode($raw) : (string) $raw;
    }

    /**
     * @param  list<string>  $items
     * @return list<string>
     */
    private function filterMultiselectValues(Attribute $attribute, array $items): array
    {
        $attribute->loadMissing('options');
        $valid = $attribute->options->pluck('name')->all();
        if ($valid === []) {
            return $items;
        }

        return array_values(array_intersect($items, $valid));
    }
}
