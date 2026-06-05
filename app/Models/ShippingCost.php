<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingCost extends Model
{
    protected $fillable = ['city_name', 'regency_code', 'regency_name', 'province_name', 'cost', 'cost_per_kg', 'is_active'];

    protected function casts(): array
    {
        return [
            'cost' => 'integer',
            'cost_per_kg' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
