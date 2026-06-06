<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhookLog extends Model
{
    protected $fillable = [
        'order_id',
        'order_number',
        'provider',
        'event_type',
        'transaction_status',
        'amount',
        'is_duplicate',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'is_duplicate' => 'boolean',
            'payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
