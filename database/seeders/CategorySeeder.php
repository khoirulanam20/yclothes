<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Pria', 'slug' => 'pria', 'image' => 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?w=600&h=600&fit=crop', 'order' => 1],
            ['name' => 'Wanita', 'slug' => 'wanita', 'image' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=600&h=600&fit=crop', 'order' => 2],
            ['name' => 'Aksesoris', 'slug' => 'aksesoris', 'image' => 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=600&h=600&fit=crop', 'order' => 3],
            ['name' => 'Sepatu', 'slug' => 'sepatu', 'image' => 'https://images.unsplash.com/photo-1485968579580-b6d095142e6e?w=600&h=600&fit=crop', 'order' => 4],
            ['name' => 'Tas', 'slug' => 'tas', 'image' => 'https://images.unsplash.com/photo-1566150905458-1bf1fc113f0d?w=600&h=600&fit=crop', 'order' => 5],
            ['name' => 'Muslimah', 'slug' => 'muslimah', 'image' => 'https://images.unsplash.com/photo-1613048605431-6057d1b3b40d?w=600&h=600&fit=crop', 'order' => 6],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }
    }
}
