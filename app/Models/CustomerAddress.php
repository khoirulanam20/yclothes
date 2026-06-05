<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id', 'label', 'recipient_name', 'phone', 'street_address',
        'city', 'province', 'postal_code', 'is_default', 'type',
    ];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function fullAddress(): string
    {
        return "{$this->street_address}, {$this->city}, {$this->province}".($this->postal_code ? " {$this->postal_code}" : '');
    }
}
