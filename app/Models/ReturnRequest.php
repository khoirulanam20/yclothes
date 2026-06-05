<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class ReturnRequest extends Model
{
    protected $fillable = [
        'request_number', 'order_id', 'replacement_order_id', 'customer_id', 'status', 'resolution_type',
        'admin_note', 'approved_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ReturnRequest $request) {
            if (empty($request->request_number)) {
                do {
                    $number = 'RMA-'.strtoupper(Str::random(8));
                } while (static::where('request_number', $number)->exists());
                $request->request_number = $number;
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function replacementOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'replacement_order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnRequestItem::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ReturnRequestMedia::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(ReturnShipment::class);
    }
}
