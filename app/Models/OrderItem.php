<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'product_price',
        'qty', 'subtotal', 'size', 'color',
    ];

    protected function casts(): array
    {
        return [
            'product_price' => 'integer',
            'qty' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
