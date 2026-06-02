<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@yclothes.test',
            'password' => bcrypt('admin123'),
            'is_admin' => true,
        ]);
    }
}
