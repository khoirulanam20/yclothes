<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartRule extends Model
{
    protected $fillable = [
        'name', 'description', 'coupon_code', 'uses_per_coupon', 'uses_per_customer',
        'discount_type', 'discount_amount', 'min_order_amount', 'max_discount',
        'category_ids', 'start_date', 'end_date', 'is_active', 'priority',
        'slug', 'meta_title', 'meta_description', 'banner_image',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'category_ids' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'uses_per_coupon' => 'integer',
            'uses_per_customer' => 'integer',
            'priority' => 'integer',
        ];
    }

    public function usages()
    {
        return $this->hasMany(CartRuleUsage::class);
    }

    public function promotionPopup()
    {
        return $this->hasOne(PromotionPopup::class);
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
