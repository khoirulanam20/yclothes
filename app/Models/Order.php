<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number', 'customer_id', 'customer_name', 'customer_phone', 'customer_email', 'newsletter_opt_in',
        'shipping_address', 'province_code', 'province_name', 'regency_code', 'regency_name',
        'district_code', 'district_name', 'village_code', 'village_name', 'postal_code',
        'shipping_city', 'shipping_cost', 'shipping_method', 'total_price',
        'tax_amount', 'discount_amount', 'coupon_code', 'grand_total', 'unique_payment_amount',
        'payment_method', 'payment_due_at', 'delivered_at', 'completed_at',
        'bank_name', 'bank_account_number', 'bank_account_name',
        'is_replacement', 'source_return_request_id',
        'courier', 'courier_service', 'tracking_number', 'notes',
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
            'newsletter_opt_in' => 'boolean',
        ];
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
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

    public function canCustomerReview(): bool
    {
        return $this->order_status === 'completed' && $this->completed_at !== null;
    }

    public function canSubmitPaymentConfirmation(): bool
    {
        return in_array($this->order_status, ['pending', 'awaiting_verification', 'confirmed'], true);
    }

    public function hasUnreviewedItems(): bool
    {
        if (! $this->relationLoaded('items')) {
            return false;
        }

        $reviewedItemIds = $this->relationLoaded('reviews')
            ? $this->reviews->pluck('order_item_id')->filter()->all()
            : [];

        return $this->items->contains(
            fn ($item) => ! in_array($item->id, $reviewedItemIds, true),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function createTrusted(array $attributes): static
    {
        return static::forceCreate($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateTrusted(array $attributes): bool
    {
        return $this->forceFill($attributes)->save();
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->access_token)) {
                $order->access_token = Str::random(64);
            }
        });

        static::created(function (Order $order) {
            if (
                in_array($order->payment_method, ['bank_transfer', 'qris'], true)
                && empty($order->unique_payment_amount)
                && setting_bool('unique_payment_amount_enabled', true)
            ) {
                $order->updateTrusted([
                    'unique_payment_amount' => generate_unique_payment_amount($order->grand_total, $order->id),
                ]);
            }
        });
    }
}
