<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            AttributeSeeder::class,
            AdminUserSeeder::class,
            CustomerSeeder::class,
            SettingSeeder::class,
            PaymentBankSeeder::class,
            ShippingCostSeeder::class,
            WarehouseSeeder::class,
            TaxRateSeeder::class,
            CmsPageSeeder::class,
            NavigationItemSeeder::class,
            AdminRoleSeeder::class,
        ]);
    }
}
