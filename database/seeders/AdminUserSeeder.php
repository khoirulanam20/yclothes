<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@yclothes.test'],
            [
                'name' => 'Admin',
                'password' => 'admin123',
                'is_admin' => true,
            ]
        );
    }
}
