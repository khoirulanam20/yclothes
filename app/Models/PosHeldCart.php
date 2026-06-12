<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosHeldCart extends Model
{
    protected $fillable = [
        'user_id',
        'warehouse_id',
        'pos_shift_id',
        'label',
        'customer_name',
        'customer_phone',
        'customer_id',
        'items',
        'coupon_code',
        'notes',
        'held_at',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'held_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function posShift(): BelongsTo
    {
        return $this->belongsTo(PosShift::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
