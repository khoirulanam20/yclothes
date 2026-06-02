<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentBank extends Model
{
    protected $fillable = ['bank_name', 'account_number', 'account_name', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
