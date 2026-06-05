<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $pembeli = Customer::updateOrCreate(
            ['email' => 'pembeli@yclothes.test'],
            [
                'name' => 'Budi Santoso',
                'phone' => '081234567890',
                'password' => 'password123',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        CustomerAddress::updateOrCreate(
            [
                'customer_id' => $pembeli->id,
                'label' => 'Rumah',
            ],
            [
                'recipient_name' => 'Budi Santoso',
                'phone' => '081234567890',
                'street_address' => 'Jl. Sudirman No. 10',
                'city' => 'Jakarta Pusat',
                'province' => 'DKI Jakarta',
                'postal_code' => '10220',
                'is_default' => true,
                'type' => 'both',
            ]
        );

        $pembeli2 = Customer::updateOrCreate(
            ['email' => 'pembeli2@yclothes.test'],
            [
                'name' => 'Siti Rahayu',
                'phone' => '081987654321',
                'password' => 'password123',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        CustomerAddress::updateOrCreate(
            [
                'customer_id' => $pembeli2->id,
                'label' => 'Kantor',
            ],
            [
                'recipient_name' => 'Siti Rahayu',
                'phone' => '081987654321',
                'street_address' => 'Jl. Asia Afrika No. 25',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'postal_code' => '40111',
                'is_default' => true,
                'type' => 'both',
            ]
        );
    }
}
