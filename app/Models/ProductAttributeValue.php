<?php

namespace App\Models;

use App\Enums\AttributeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttributeValue extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'attribute_id', 'value'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function decodedValue(): mixed
    {
        if ($this->value === null || $this->value === '') {
            return null;
        }

        $type = $this->attribute?->type;

        if ($type === AttributeType::Boolean) {
            return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
        }

        if ($type === AttributeType::Multiselect || $this->attribute?->code === 'size') {
            $decoded = json_decode($this->value, true);

            return is_array($decoded) ? $decoded : array_map('trim', explode(',', $this->value));
        }

        if ($this->attribute?->code === 'color') {
            $decoded = json_decode($this->value, true);

            if (is_array($decoded)) {
                return array_map(fn ($c) => is_string($c) ? ['hex' => $c, 'name' => $c] : $c, $decoded);
            }
        }

        return $this->value;
    }
}
