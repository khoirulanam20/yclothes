<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id', 'label', 'recipient_name', 'phone', 'street_address',
        'province_code', 'province_name', 'regency_code', 'regency_name',
        'district_code', 'district_name', 'village_code', 'village_name',
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
        $parts = array_filter([
            $this->street_address,
            $this->village_name,
            $this->district_name,
            $this->regency_name ?? $this->city,
            $this->province_name ?? $this->province,
            $this->postal_code,
        ]);

        return implode(', ', $parts);
    }

    public function toOrderSnapshot(): array
    {
        return [
            'shipping_address' => $this->street_address,
            'province_code' => $this->province_code,
            'province_name' => $this->province_name,
            'regency_code' => $this->regency_code,
            'regency_name' => $this->regency_name ?? $this->city,
            'district_code' => $this->district_code,
            'district_name' => $this->district_name,
            'village_code' => $this->village_code,
            'village_name' => $this->village_name,
            'postal_code' => $this->postal_code,
        ];
    }
}
