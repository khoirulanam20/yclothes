<?php

namespace App\Services;

use App\Enums\BadgePreset;
use App\Models\CartRule;
use App\Models\CatalogRule;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Product;

class LinkTemplateService
{
    public function __construct(private CategoryTreeService $categoryTree) {}

    /** @return list<array{label: string, options: list<array{id: string, label: string, url: string}>}> */
    public function groups(): array
    {
        $groups = [
            [
                'label' => 'Halaman Umum',
                'options' => [
                    $this->option('static', '/', 'Beranda'),
                    $this->option('static', '/products', 'Katalog Produk'),
                    $this->option('static', '/blog', 'Blog'),
                    $this->option('static', '/faq', 'FAQ'),
                    $this->option('static', '/cart', 'Keranjang'),
                    $this->option('static', '/order/track', 'Lacak Pesanan'),
                ],
            ],
            [
                'label' => 'Filter Produk',
                'options' => array_merge(
                    [
                        $this->option('flash_sale', '', 'Flash Sale', '/products?flash_sale=1'),
                        $this->option('featured', '', 'Produk Unggulan', '/products?featured=1'),
                        $this->option('on_sale', '', 'Produk Sale', '/products?on_sale=1'),
                    ],
                    collect(BadgePreset::cases())
                        ->reject(fn (BadgePreset $preset) => in_array($preset, [BadgePreset::None, BadgePreset::Custom], true))
                        ->map(fn (BadgePreset $preset) => $this->option(
                            'badge',
                            $preset->value,
                            'Produk Badge '.$preset->label(),
                            '/products?badge='.$preset->value,
                        ))
                        ->values()
                        ->all(),
                    $this->customBadgeOptions(),
                ),
            ],
        ];

        $roots = Category::tree();
        $categories = $this->categoryTree->flattenForSelect($roots);
        if ($categories !== []) {
            $groups[] = [
                'label' => 'Kategori',
                'options' => array_map(
                    fn (array $category) => $this->option(
                        'category',
                        $category['slug'],
                        str_repeat('— ', $category['depth']).$category['name'],
                        '/products?category='.$category['slug'],
                    ),
                    $categories,
                ),
            ];
        }

        $cmsPages = CmsPage::query()
            ->where('status', 'published')
            ->orderBy('title')
            ->get(['title', 'slug']);

        if ($cmsPages->isNotEmpty()) {
            $groups[] = [
                'label' => 'Halaman CMS',
                'options' => $cmsPages
                    ->map(fn (CmsPage $page) => $this->option(
                        'cms',
                        $page->slug,
                        $page->title,
                        '/page/'.$page->slug,
                    ))
                    ->values()
                    ->all(),
            ];
        }

        $promoOptions = [];
        CartRule::query()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderBy('name')
            ->get(['name', 'slug'])
            ->each(function (CartRule $rule) use (&$promoOptions) {
                $promoOptions[] = $this->option(
                    'promo_cart',
                    $rule->slug,
                    $rule->name.' (Aturan Keranjang)',
                    '/promo/'.$rule->slug,
                );
            });

        CatalogRule::query()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderBy('name')
            ->get(['name', 'slug'])
            ->each(function (CatalogRule $rule) use (&$promoOptions) {
                $promoOptions[] = $this->option(
                    'promo_catalog',
                    $rule->slug,
                    $rule->name.' (Aturan Katalog)',
                    '/promo/'.$rule->slug,
                );
            });

        if ($promoOptions !== []) {
            $groups[] = [
                'label' => 'Landing Promo',
                'options' => $promoOptions,
            ];
        }

        return $groups;
    }

    /** @return list<array{id: string, label: string, url: string}> */
    private function customBadgeOptions(): array
    {
        return Product::query()
            ->where('is_active', true)
            ->where('badge_preset', BadgePreset::Custom)
            ->whereNotNull('badge')
            ->where('badge', '!=', '')
            ->distinct()
            ->orderBy('badge')
            ->pluck('badge')
            ->map(fn (string $label) => $this->option(
                'badge_label',
                $label,
                'Produk Badge '.$label,
                '/products?badge_label='.rawurlencode($label),
            ))
            ->values()
            ->all();
    }

    /** @return array{id: string, label: string, url: string} */
    private function option(string $type, string $param, string $label, ?string $url = null): array
    {
        $url ??= match ($type) {
            'static' => $param,
            'badge' => '/products?badge='.$param,
            'badge_label' => '/products?badge_label='.rawurlencode($param),
            'category' => '/products?category='.$param,
            'cms' => '/page/'.$param,
            'promo_cart', 'promo_catalog' => '/promo/'.$param,
            default => '/',
        };

        return [
            'id' => $type.':'.$param,
            'label' => $label,
            'url' => $url,
        ];
    }
}
