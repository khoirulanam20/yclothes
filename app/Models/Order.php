<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'access_token', 'customer_id', 'customer_name', 'customer_phone', 'customer_email',
        'shipping_address', 'province_code', 'province_name', 'regency_code', 'regency_name',
        'district_code', 'district_name', 'village_code', 'village_name', 'postal_code',
        'shipping_city', 'shipping_cost', 'shipping_method', 'total_price',
        'tax_amount', 'discount_amount', 'coupon_code', 'grand_total', 'unique_payment_amount',
        'payment_method', 'payment_status', 'payment_confirmation_status', 'payment_due_at', 'paid_at',
        'delivered_at', 'completed_at', 'bank_name', 'bank_account_number', 'bank_account_name',
        'order_status', 'inventory_decremented', 'is_replacement', 'source_return_request_id',
        'courier', 'courier_service', 'tracking_number', 'notes',
        'refund_status', 'refunded_amount',
    ];

    protected function casts(): array
    {
        return [
            'shipping_cost' => 'integer',
            'total_price' => 'integer',
            'tax_amount' => 'integer',
            'discount_amount' => 'integer',
            'grand_total' => 'integer',
            'unique_payment_amount' => 'integer',
            'refunded_amount' => 'integer',
            'payment_due_at' => 'datetime',
            'paid_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
            'inventory_decremented' => 'boolean',
            'is_replacement' => 'boolean',
        ];
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    public function paymentConfirmations()
    {
        return $this->hasMany(PaymentConfirmation::class);
    }

    public function returnRequests()
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function sourceReturnRequest()
    {
        return $this->belongsTo(ReturnRequest::class, 'source_return_request_id');
    }

    public function fullShippingAddress(): string
    {
        $parts = array_filter([
            $this->shipping_address,
            $this->village_name,
            $this->district_name,
            $this->regency_name ?? $this->shipping_city,
            $this->province_name,
            $this->postal_code,
        ]);

        return implode(', ', $parts);
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->access_token)) {
                $order->access_token = Str::random(64);
            }
        });

        static::created(function (Order $order) {
            if ($order->payment_method === 'bank_transfer' && empty($order->unique_payment_amount)) {
                $order->update([
                    'unique_payment_amount' => generate_unique_payment_amount($order->grand_total, $order->id),
                ]);
            }
        });
    }
}
