<?php

namespace Database\Seeders;

use App\Models\PaymentBank;
use Illuminate\Database\Seeder;

class PaymentBankSeeder extends Seeder
{
    public function run(): void
    {
        PaymentBank::create([
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'Toko yClothes',
            'is_active' => true,
        ]);

        PaymentBank::create([
            'bank_name' => 'Mandiri',
            'account_number' => '9876543210',
            'account_name' => 'Toko yClothes',
            'is_active' => true,
        ]);
    }
}
