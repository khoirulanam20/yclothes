<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\Setting;
use App\Services\CmsLayoutService;
use Illuminate\Database\Seeder;

class CmsPageSeeder extends Seeder
{
    public function run(): void
    {
        $layoutService = app(CmsLayoutService::class);

        $aboutContent = Setting::where('key', 'about_content')->value('value');
        $caraContent = Setting::where('key', 'cara_belanja_content')->value('value');

        $pages = [
            [
                'slug' => 'tentang-kami',
                'title' => 'Tentang Kami',
                'content' => $aboutContent ?: '<p>Konten tentang kami.</p>',
                'banner_image' => Setting::where('key', 'about_banner')->value('value'),
                'meta_title' => 'Tentang Kami',
                'meta_description' => 'Pelajari lebih lanjut tentang YClothes.',
            ],
            [
                'slug' => 'cara-belanja',
                'title' => 'Cara Belanja',
                'content' => $caraContent ?: '<p>Panduan belanja.</p>',
                'banner_image' => Setting::where('key', 'cara_belanja_banner')->value('value'),
                'meta_title' => 'Cara Belanja',
                'meta_description' => 'Panduan lengkap berbelanja di YClothes.',
            ],
        ];

        foreach ($pages as $data) {
            $page = CmsPage::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['status' => 'published']),
            );

            $layoutService->migrateLegacyIfNeeded($page);
        }
    }
}
