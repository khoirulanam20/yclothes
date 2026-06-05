<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartRuleUsage extends Model
{
    protected $fillable = ['cart_rule_id', 'customer_id', 'times_used'];

    protected function casts(): array
    {
        return ['times_used' => 'integer'];
    }

    public function cartRule()
    {
        return $this->belongsTo(CartRule::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
