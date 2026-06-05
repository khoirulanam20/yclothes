<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all()->keyBy('slug');

        $products = [
            // PRIA
            [
                'category_id' => $categories['pria-kemeja']->id,
                'name' => 'Kemeja Oxford Premium',
                'description' => 'Kemeja oxford berbahan katun premium. Nyaman dipakai sehari-hari dengan potongan slim fit yang elegan.',
                'price' => 199000,
                'sale_price' => 149000,
                'image' => 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?w=600&h=750&fit=crop',
                'badge' => 'SALE',
                'weight' => 250,
                'sizes' => ['S', 'M', 'L', 'XL'],
                'colors' => [
                    ['hex' => '#FFFFFF', 'name' => 'Putih'],
                    ['hex' => '#2C3947', 'name' => 'Navy'],
                    ['hex' => '#547A95', 'name' => 'Biru'],
                ],
            ],
            [
                'category_id' => $categories['pria-jaket']->id,
                'name' => 'Jaket Denim Classic',
                'description' => 'Jaket denim klasik dengan desain timeless. Cocok untuk gaya kasual sehari-hari.',
                'price' => 350000,
                'sale_price' => null,
                'image' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=600&h=750&fit=crop',
                'badge' => null,
                'weight' => 600,
                'sizes' => ['M', 'L', 'XL'],
                'colors' => [
                    ['hex' => '#2C3947', 'name' => 'Navy'],
                    ['hex' => '#547A95', 'name' => 'Biru'],
                ],
            ],
            [
                'category_id' => $categories['pria-kemeja']->id,
                'name' => 'Kaos Putih Polos Premium',
                'description' => 'Kaos putih polos dengan bahan cotton combed 30s. Lembut dan adem.',
                'price' => 89000,
                'sale_price' => null,
                'image' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=600&h=750&fit=crop',
                'badge' => 'NEW',
                'weight' => 150,
                'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
                'colors' => [
                    ['hex' => '#FFFFFF', 'name' => 'Putih'],
                    ['hex' => '#000000', 'name' => 'Hitam'],
                    ['hex' => '#E8EDF2', 'name' => 'Abu-abu'],
                ],
            ],
            [
                'category_id' => $categories['pria-celana']->id,
                'name' => 'Celana Chino Slim Fit',
                'description' => 'Celana chino slim fit cocok untuk gaya kasual maupun semi-formal.',
                'price' => 179000,
                'sale_price' => 159000,
                'image' => 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=600&h=750&fit=crop',
                'badge' => 'SALE',
                'weight' => 300,
                'sizes' => ['M', 'L', 'XL'],
                'colors' => [
                    ['hex' => '#2C3947', 'name' => 'Navy'],
                    ['hex' => '#C2A56D', 'name' => 'Coklat'],
                    ['hex' => '#547A95', 'name' => 'Biru'],
                ],
            ],
            // WANITA
            [
                'category_id' => $categories['wanita-dress']->id,
                'name' => 'Dress Elegan Midaxi',
                'description' => 'Dress midaxi dengan potongan A-line. Cocok untuk acara formal maupun casual.',
                'price' => 279000,
                'sale_price' => null,
                'image' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=600&h=750&fit=crop',
                'badge' => null,
                'weight' => 350,
                'sizes' => ['S', 'M', 'L'],
                'colors' => [
                    ['hex' => '#C2A56D', 'name' => 'Gold'],
                    ['hex' => '#2C3947', 'name' => 'Navy'],
                    ['hex' => '#547A95', 'name' => 'Biru'],
                ],
            ],
            [
                'category_id' => $categories['wanita-blouse']->id,
                'name' => 'Blouse Wanita Formal',
                'description' => 'Blouse wanita dengan bahan sifon lembut. Cocok untuk ke kantor atau acara formal.',
                'price' => 159000,
                'sale_price' => 129000,
                'image' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=600&h=750&fit=crop',
                'badge' => 'SALE',
                'weight' => 200,
                'sizes' => ['S', 'M', 'L', 'XL'],
                'colors' => [
                    ['hex' => '#FFFFFF', 'name' => 'Putih'],
                    ['hex' => '#547A95', 'name' => 'Biru'],
                    ['hex' => '#E8EDF2', 'name' => 'Abu-abu'],
                ],
            ],
            [
                'category_id' => $categories['wanita-rok']->id,
                'name' => 'Rok Midi A-Line',
                'description' => 'Rok midi A-line yang elegan. Nyaman dipakai untuk berbagai acara.',
                'price' => 139000,
                'sale_price' => null,
                'image' => 'https://images.unsplash.com/photo-1512436991641-6745cdb1723f?w=600&h=750&fit=crop',
                'badge' => null,
                'weight' => 250,
                'sizes' => ['M', 'L'],
                'colors' => [
                    ['hex' => '#2C3947', 'name' => 'Navy'],
                    ['hex' => '#C2A56D', 'name' => 'Coklat'],
                ],
            ],
            [
                'category_id' => $categories['wanita-blouse']->id,
                'name' => 'Cardigan Rajut Premium',
                'description' => 'Cardigan rajut dengan bahan lembut. Hangat dan stylish.',
                'price' => 199000,
                'sale_price' => 179000,
                'image' => 'https://images.unsplash.com/photo-1614252369475-531eba835eb1?w=600&h=750&fit=crop',
                'badge' => 'NEW',
                'weight' => 300,
                'sizes' => ['S', 'M', 'L', 'XL'],
                'colors' => [
                    ['hex' => '#C2A56D', 'name' => 'Coklat'],
                    ['hex' => '#547A95', 'name' => 'Biru'],
                    ['hex' => '#E8EDF2', 'name' => 'Abu-abu'],
                ],
            ],
            // AKSESORIS
            [
                'category_id' => $categories['aksesoris']->id,
                'name' => 'Jam Tangan Pria Klasik',
                'description' => 'Jam tangan dengan desain klasik, strap kulit asli, water resistant.',
                'price' => 450000,
                'sale_price' => null,
                'image' => 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=600&h=750&fit=crop',
                'badge' => null,
                'weight' => 100,
                'sizes' => [],
                'colors' => [
                    ['hex' => '#2C3947', 'name' => 'Coklat Tua'],
                    ['hex' => '#C2A56D', 'name' => 'Coklat'],
                ],
            ],
            [
                'category_id' => $categories['aksesoris']->id,
                'name' => 'Kacamata Hitam Premium',
                'description' => 'Kacamata hitam dengan frame metal kokoh. UV protection 100%.',
                'price' => 225000,
                'sale_price' => 199000,
                'image' => 'https://images.unsplash.com/photo-1577803645773-f96470509666?w=600&h=750&fit=crop',
                'badge' => 'SALE',
                'weight' => 80,
                'sizes' => [],
                'colors' => [
                    ['hex' => '#000000', 'name' => 'Hitam'],
                    ['hex' => '#C2A56D', 'name' => 'Coklat'],
                ],
            ],
            // SEPATU
            [
                'category_id' => $categories['sepatu-sneakers']->id,
                'name' => 'Sneakers Casual White',
                'description' => 'Sneakers putih klasik yang cocok dipadukan dengan outfit apa pun.',
                'price' => 329000,
                'sale_price' => null,
                'image' => 'https://images.unsplash.com/photo-1485968579580-b6d095142e6e?w=600&h=750&fit=crop',
                'badge' => 'BEST',
                'weight' => 500,
                'sizes' => ['39', '40', '41', '42', '43'],
                'colors' => [
                    ['hex' => '#FFFFFF', 'name' => 'Putih'],
                    ['hex' => '#000000', 'name' => 'Hitam'],
                ],
            ],
            [
                'category_id' => $categories['sepatu-formal']->id,
                'name' => 'Sepatu Formal Kulit',
                'description' => 'Sepatu formal berbahan kulit asli. Nyaman dipakai seharian.',
                'price' => 499000,
                'sale_price' => 429000,
                'image' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=600&h=750&fit=crop',
                'badge' => 'SALE',
                'weight' => 600,
                'sizes' => ['39', '40', '41', '42'],
                'colors' => [
                    ['hex' => '#2C3947', 'name' => 'Navy'],
                    ['hex' => '#000000', 'name' => 'Hitam'],
                ],
            ],
            // TAS
            [
                'category_id' => $categories['tas']->id,
                'name' => 'Tas Kulit Wanita',
                'description' => 'Tas kulit asli dengan desain elegan. Kompartemen luas untuk kebutuhan sehari-hari.',
                'price' => 550000,
                'sale_price' => null,
                'image' => 'https://images.unsplash.com/photo-1566150905458-1bf1fc113f0d?w=600&h=750&fit=crop',
                'badge' => null,
                'weight' => 500,
                'sizes' => [],
                'colors' => [
                    ['hex' => '#2C3947', 'name' => 'Navy'],
                    ['hex' => '#C2A56D', 'name' => 'Coklat'],
                    ['hex' => '#000000', 'name' => 'Hitam'],
                ],
            ],
            [
                'category_id' => $categories['tas']->id,
                'name' => 'Ransel Pria Premium',
                'description' => 'Ransel premium dengan bahan kanvas tebal. Muat laptop 15 inci.',
                'price' => 289000,
                'sale_price' => 259000,
                'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=600&h=750&fit=crop',
                'badge' => 'SALE',
                'weight' => 400,
                'sizes' => [],
                'colors' => [
                    ['hex' => '#2C3947', 'name' => 'Navy'],
                    ['hex' => '#547A95', 'name' => 'Biru'],
                ],
            ],
            // MUSLIMAH
            [
                'category_id' => $categories['muslimah']->id,
                'name' => 'Gamis Elegan Sifon',
                'description' => 'Gamis sifon dengan payet halus. Cocok untuk acara formal dan kondangan.',
                'price' => 349000,
                'sale_price' => null,
                'image' => 'https://images.unsplash.com/photo-1613048605431-6057d1b3b40d?w=600&h=750&fit=crop',
                'badge' => null,
                'weight' => 400,
                'sizes' => ['S', 'M', 'L', 'XL'],
                'colors' => [
                    ['hex' => '#C2A56D', 'name' => 'Gold'],
                    ['hex' => '#547A95', 'name' => 'Biru'],
                    ['hex' => '#2C3947', 'name' => 'Navy'],
                ],
            ],
            [
                'category_id' => $categories['muslimah']->id,
                'name' => 'Hijab Pashmina Ceruty',
                'description' => 'Hijab pashmina bahan ceruty premium. Lembut dan tidak mudah kusut.',
                'price' => 59000,
                'sale_price' => 45000,
                'image' => 'https://images.unsplash.com/photo-1649819697236-7e269d8c7bc3?w=600&h=750&fit=crop',
                'badge' => 'SALE',
                'weight' => 100,
                'sizes' => [],
                'colors' => [
                    ['hex' => '#C2A56D', 'name' => 'Gold'],
                    ['hex' => '#547A95', 'name' => 'Biru'],
                    ['hex' => '#E8EDF2', 'name' => 'Abu-abu'],
                    ['hex' => '#FFFFFF', 'name' => 'Putih'],
                ],
            ],
        ];

        foreach ($products as $data) {
            Product::updateOrCreate(['slug' => Str::slug($data['name'])], $data);
        }
    }
}
