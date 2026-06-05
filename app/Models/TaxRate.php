<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected $fillable = ['name', 'rate', 'type', 'is_active'];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function categories()
    {
        return $this->hasMany(TaxRateCategory::class);
    }

    public function zones()
    {
        return $this->hasMany(TaxZone::class);
    }
}
