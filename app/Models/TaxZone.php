<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxZone extends Model
{
    protected $fillable = ['province', 'city', 'tax_rate_id'];

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }
}
