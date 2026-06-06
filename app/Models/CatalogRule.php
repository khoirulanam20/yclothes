<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogRule extends Model
{
    protected $fillable = [
        'name', 'description', 'rule_type', 'discount_type', 'discount_amount',
        'min_order_amount', 'min_qty', 'buy_qty', 'get_qty', 'get_discount_percent',
        'category_ids', 'product_ids', 'start_date', 'end_date', 'is_active', 'priority',
        'slug', 'meta_title', 'meta_description', 'banner_image',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'get_discount_percent' => 'decimal:2',
            'category_ids' => 'array',
            'product_ids' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'min_qty' => 'integer',
            'buy_qty' => 'integer',
            'get_qty' => 'integer',
            'priority' => 'integer',
        ];
    }

    public function isActiveNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = now()->startOfDay();

        return $today->between($this->start_date->startOfDay(), $this->end_date->endOfDay());
    }
}
