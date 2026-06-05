<?php

namespace Database\Seeders;

use App\Models\NavigationItem;
use Illuminate\Database\Seeder;

class NavigationItemSeeder extends Seeder
{
    public function run(): void
    {
        if (NavigationItem::exists()) {
            return;
        }

        $headerItems = [
            ['label' => 'Beranda', 'url' => '/', 'sort_order' => 1],
            ['label' => 'Produk', 'url' => '/products', 'sort_order' => 2],
            ['label' => 'Tentang Kami', 'url' => '/page/tentang-kami', 'sort_order' => 3],
            ['label' => 'Cara Belanja', 'url' => '/page/cara-belanja', 'sort_order' => 4],
            ['label' => 'Lacak Pesanan', 'url' => '/order/track', 'sort_order' => 5],
        ];

        foreach ($headerItems as $item) {
            NavigationItem::create(array_merge($item, [
                'menu' => 'header',
                'is_active' => true,
            ]));
        }

        $footerItems = [
            ['label' => 'Beranda', 'url' => '/', 'sort_order' => 1],
            ['label' => 'Produk', 'url' => '/products', 'sort_order' => 2],
            ['label' => 'Tentang Kami', 'url' => '/page/tentang-kami', 'sort_order' => 3],
            ['label' => 'Cara Belanja', 'url' => '/page/cara-belanja', 'sort_order' => 4],
            ['label' => 'FAQ', 'url' => '/faq', 'sort_order' => 5],
        ];

        foreach ($footerItems as $item) {
            NavigationItem::create(array_merge($item, [
                'menu' => 'footer',
                'is_active' => true,
            ]));
        }
    }
}
