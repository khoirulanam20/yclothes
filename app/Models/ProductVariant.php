<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    protected $fillable = [
        'parent_product_id', 'sku', 'name', 'price', 'stock', 'image',
        'attributes', 'is_active', 'track_stock', 'allow_backorder',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'price' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'track_stock' => 'boolean',
            'allow_backorder' => 'boolean',
        ];
    }

    public function parentProduct()
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function getImageUrlAttribute(): string
    {
        if (empty($this->image)) {
            return $this->parentProduct?->image_url ?? '';
        }
        if (Str::startsWith($this->image, 'http')) {
            return $this->image;
        }

        return storage_url($this->image) ?? '';
    }

    public function getFinalPriceAttribute(): int
    {
        return $this->price ?? $this->parentProduct->final_price;
    }

    public function getAvailableStockAttribute(): int
    {
        if ($this->inventories()->exists()) {
            return (int) $this->inventories()->sum('stock');
        }

        return $this->stock;
    }
}
