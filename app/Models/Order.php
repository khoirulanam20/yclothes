<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'access_token', 'customer_name', 'customer_phone', 'customer_email',
        'shipping_address', 'shipping_city', 'shipping_cost', 'total_price', 'grand_total',
        'payment_method', 'payment_status', 'payment_due_at', 'paid_at',
        'bank_name', 'bank_account_number', 'bank_account_name',
        'order_status', 'courier', 'courier_service', 'tracking_number', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'shipping_cost' => 'integer',
            'total_price' => 'integer',
            'grand_total' => 'integer',
            'payment_due_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->access_token)) {
                $order->access_token = Str::random(64);
            }
        });
    }
}
