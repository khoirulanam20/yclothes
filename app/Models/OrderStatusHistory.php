<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    protected $fillable = [
        'order_id', 'from_status', 'to_status', 'actor_type', 'actor_id', 'note', 'notify_customer',
    ];

    protected function casts(): array
    {
        return ['notify_customer' => 'boolean'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
