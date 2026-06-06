<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $brandName = Setting::where('key', 'brand_name')->value('value') ?: config('app.name');

        Warehouse::firstOrCreate(
            ['name' => 'Gudang Pusat'],
            [
                'address' => "Gudang utama {$brandName}",
                'city' => 'Makassar',
                'is_active' => true,
            ],
        );
    }
}
