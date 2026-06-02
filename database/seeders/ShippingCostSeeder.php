<?php

namespace Database\Seeders;

use App\Models\ShippingCost;
use Illuminate\Database\Seeder;

class ShippingCostSeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['city_name' => 'Jakarta', 'cost' => 15000, 'cost_per_kg' => 5000],
            ['city_name' => 'Bandung', 'cost' => 20000, 'cost_per_kg' => 5000],
            ['city_name' => 'Surabaya', 'cost' => 25000, 'cost_per_kg' => 5000],
            ['city_name' => 'Yogyakarta', 'cost' => 22000, 'cost_per_kg' => 5000],
            ['city_name' => 'Semarang', 'cost' => 20000, 'cost_per_kg' => 5000],
            ['city_name' => 'Medan', 'cost' => 35000, 'cost_per_kg' => 7000],
            ['city_name' => 'Makassar', 'cost' => 40000, 'cost_per_kg' => 7000],
            ['city_name' => 'Denpasar', 'cost' => 35000, 'cost_per_kg' => 5000],
        ];

        foreach ($cities as $city) {
            ShippingCost::create($city);
        }
    }
}
