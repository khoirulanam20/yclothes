<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate sitemap.xml based on configuration settings';

    public function handle(): int
    {
        if (! setting_bool('sitemap_enabled', true)) {
            $this->info('Sitemap disabled in configuration.');

            return self::SUCCESS;
        }

        $baseUrl = rtrim(config('app.url'), '/');
        $urls = [
            ['loc' => $baseUrl.'/', 'priority' => '1.0'],
            ['loc' => $baseUrl.'/products', 'priority' => '0.9'],
            ['loc' => $baseUrl.'/blog', 'priority' => '0.7'],
            ['loc' => $baseUrl.'/faq', 'priority' => '0.6'],
        ];

        if (setting_bool('sitemap_include_products', true)) {
            Product::where('is_active', true)->orderBy('id')->each(function (Product $product) use (&$urls, $baseUrl) {
                $urls[] = [
                    'loc' => $baseUrl.'/products/'.$product->slug,
                    'priority' => '0.8',
                    'lastmod' => $product->updated_at?->toAtomString(),
                ];
            });
        }

        if (setting_bool('sitemap_include_categories', true)) {
            Category::where('is_active', true)->orderBy('id')->each(function (Category $category) use (&$urls, $baseUrl) {
                $urls[] = [
                    'loc' => $baseUrl.'/products?category='.$category->slug,
                    'priority' => '0.7',
                ];
            });
        }

        if (setting_bool('sitemap_include_cms', true)) {
            CmsPage::published()->orderBy('id')->each(function (CmsPage $page) use (&$urls, $baseUrl) {
                $urls[] = [
                    'loc' => $baseUrl.'/page/'.$page->slug,
                    'priority' => '0.6',
                    'lastmod' => $page->updated_at?->toAtomString(),
                ];
            });
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.htmlspecialchars($url['loc'], ENT_XML1)."</loc>\n";
            if (! empty($url['lastmod'])) {
                $xml .= '    <lastmod>'.$url['lastmod']."</lastmod>\n";
            }
            $xml .= '    <priority>'.($url['priority'] ?? '0.5')."</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        File::put(public_path('sitemap.xml'), $xml);
        $this->info('Sitemap generated: '.count($urls).' URLs');

        return self::SUCCESS;
    }
}
