<?php

namespace App\Models;

use App\Enums\AttributeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    protected $fillable = [
        'code', 'name', 'type', 'is_required', 'is_filterable',
        'validation', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => AttributeType::class,
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function families(): BelongsToMany
    {
        return $this->belongsToMany(AttributeFamily::class, 'attribute_family_attributes');
    }

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class)->orderBy('sort_order');
    }

    public function productValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public static function typeOptions(): array
    {
        return collect(AttributeType::cases())
            ->mapWithKeys(fn (AttributeType $type) => [$type->value => $type->label()])
            ->all();
    }
}
