<?php

namespace Database\Seeders;

use App\Models\TaxRate;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        TaxRate::firstOrCreate(
            ['name' => 'PPN'],
            [
                'rate' => 11,
                'type' => 'percentage',
                'is_active' => true,
            ],
        );
    }
}
