<?php

namespace Database\Seeders;

use App\Models\AdminRole;
use Illuminate\Database\Seeder;

class AdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        AdminRole::updateOrCreate(
            ['name' => 'Super Admin'],
            [
                'description' => 'Akses penuh ke semua modul admin',
                'permissions' => ['*'],
            ]
        );

        AdminRole::updateOrCreate(
            ['name' => 'Staff'],
            [
                'description' => 'Kelola pesanan dan lihat produk',
                'permissions' => ['products.view', 'orders.view', 'orders.manage'],
            ]
        );

        AdminRole::updateOrCreate(
            ['name' => 'Finance'],
            [
                'description' => 'Lihat pesanan dan laporan',
                'permissions' => ['orders.view', 'reports.view'],
            ]
        );
    }
}
