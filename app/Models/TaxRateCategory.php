<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRateCategory extends Model
{
    public $timestamps = false;

    protected $fillable = ['tax_rate_id', 'category_id'];

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
