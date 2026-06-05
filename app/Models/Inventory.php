<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'product_id', 'product_variant_id', 'warehouse_id',
        'stock', 'low_stock_threshold',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
