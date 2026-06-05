<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $roots = [
            ['name' => 'Pria', 'slug' => 'pria', 'image' => 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?w=600&h=600&fit=crop', 'order' => 1],
            ['name' => 'Wanita', 'slug' => 'wanita', 'image' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=600&h=600&fit=crop', 'order' => 2],
            ['name' => 'Aksesoris', 'slug' => 'aksesoris', 'image' => 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=600&h=600&fit=crop', 'order' => 3],
            ['name' => 'Sepatu', 'slug' => 'sepatu', 'image' => 'https://images.unsplash.com/photo-1485968579580-b6d095142e6e?w=600&h=600&fit=crop', 'order' => 4],
            ['name' => 'Tas', 'slug' => 'tas', 'image' => 'https://images.unsplash.com/photo-1566150905458-1bf1fc113f0d?w=600&h=600&fit=crop', 'order' => 5],
            ['name' => 'Muslimah', 'slug' => 'muslimah', 'image' => 'https://images.unsplash.com/photo-1613048605431-6057d1b3b40d?w=600&h=600&fit=crop', 'order' => 6],
        ];

        foreach ($roots as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        $children = [
            'pria' => [
                ['name' => 'Kemeja', 'slug' => 'pria-kemeja', 'order' => 1],
                ['name' => 'Celana', 'slug' => 'pria-celana', 'order' => 2],
                ['name' => 'Jaket', 'slug' => 'pria-jaket', 'order' => 3],
            ],
            'wanita' => [
                ['name' => 'Dress', 'slug' => 'wanita-dress', 'order' => 1],
                ['name' => 'Blouse', 'slug' => 'wanita-blouse', 'order' => 2],
                ['name' => 'Rok', 'slug' => 'wanita-rok', 'order' => 3],
            ],
            'sepatu' => [
                ['name' => 'Sneakers', 'slug' => 'sepatu-sneakers', 'order' => 1],
                ['name' => 'Formal', 'slug' => 'sepatu-formal', 'order' => 2],
            ],
        ];

        Category::where('slug', 'celana')->whereNotNull('parent_id')->delete();

        foreach ($children as $parentSlug => $subs) {
            $parent = Category::where('slug', $parentSlug)->first();
            if (! $parent) {
                continue;
            }

            foreach ($subs as $sub) {
                Category::updateOrCreate(
                    ['slug' => $sub['slug']],
                    array_merge($sub, ['parent_id' => $parent->id]),
                );
            }
        }
    }
}
