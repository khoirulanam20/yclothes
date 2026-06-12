<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosOrderPayment extends Model
{
    protected $fillable = [
        'order_id',
        'method',
        'amount',
        'payment_bank_id',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentBank(): BelongsTo
    {
        return $this->belongsTo(PaymentBank::class);
    }
}
