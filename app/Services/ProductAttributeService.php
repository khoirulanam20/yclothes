<?php

namespace App\Services;

use App\Enums\AttributeType;
use App\Models\Attribute;
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

        return Attribute::query()
            ->whereHas('families', fn ($q) => $q->where('attribute_families.id', $familyId))
            ->with('options')
            ->orderBy('sort_order')
            ->get();
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
                $attribute->type === AttributeType::Multiselect, $attribute->code === 'size' => $rule.'|array',
                $attribute->type === AttributeType::Select && $attribute->code === 'color' => $rule.'|string',
                $attribute->type === AttributeType::Select => $rule.'|string|max:255',
                default => $rule.'|string',
            };
        }

        return $rules;
    }

    private function normalizeInputValue(Attribute $attribute, mixed $raw): string
    {
        if ($attribute->type === AttributeType::Multiselect || $attribute->code === 'size') {
            $items = is_array($raw) ? array_values(array_filter($raw, fn ($v) => $v !== '' && $v !== null)) : [];

            return json_encode($items);
        }

        if ($attribute->type === AttributeType::Boolean) {
            return $raw ? '1' : '0';
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
}
