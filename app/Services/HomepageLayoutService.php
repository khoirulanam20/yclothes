<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\CartRule;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Slider;
use App\Support\ModelSerializer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class HomepageLayoutService
{
    public const SETTING_KEY = 'homepage_layout';

    /** @var list<string> */
    public const SECTION_TYPES = [
        'hero_slider',
        'flash_sale',
        'category_grid',
        'product_grid',
        'products_by_category',
        'promotion_banner',
        'blog_posts',
        'spacer',
    ];

    public function getLayout(): array
    {
        $raw = setting(self::SETTING_KEY);
        if (! $raw) {
            return $this->defaultLayout();
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return $this->defaultLayout();
        }

        return $this->normalizeLayout($decoded);
    }

    public function saveLayout(array $layout): void
    {
        Setting::updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['value' => json_encode($this->normalizeLayout($layout), JSON_UNESCAPED_UNICODE)],
        );
    }

    /** @return list<array<string, mixed>> */
    public function defaultLayout(): array
    {
        return [
            [
                'id' => 'hero-1',
                'type' => 'hero_slider',
                'enabled' => true,
                'props' => [],
            ],
            [
                'id' => 'flash-1',
                'type' => 'flash_sale',
                'enabled' => true,
                'props' => [
                    'title' => 'Flash Sale',
                    'showCountdown' => true,
                    'endsAt' => now()->endOfDay()->format('Y-m-d\TH:i'),
                    'actionLabel' => 'Lihat Semua →',
                    'actionHref' => '/products?flash_sale=1',
                    'items' => [],
                    'metaTitle' => null,
                    'metaDescription' => null,
                ],
            ],
            [
                'id' => 'featured-1',
                'type' => 'product_grid',
                'enabled' => true,
                'props' => [
                    'title' => 'Produk Unggulan',
                    'source' => 'featured',
                    'limit' => 8,
                    'layout' => 'grid',
                    'actionLabel' => 'Lihat Semua →',
                    'actionHref' => '/products',
                    'productIds' => [],
                ],
            ],
            [
                'id' => 'new-1',
                'type' => 'product_grid',
                'enabled' => true,
                'props' => [
                    'title' => 'Produk Terbaru',
                    'source' => 'latest',
                    'limit' => 8,
                    'layout' => 'grid',
                    'actionLabel' => 'Lihat Semua →',
                    'actionHref' => '/products',
                    'productIds' => [],
                ],
            ],
            [
                'id' => 'blog-1',
                'type' => 'blog_posts',
                'enabled' => true,
                'props' => [
                    'title' => 'Blog Terbaru',
                    'limit' => 3,
                    'actionLabel' => 'Lihat Semua →',
                    'actionHref' => '/blog',
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function resolveSections(PromotionEngine $promotionEngine): array
    {
        $resolved = [];

        foreach ($this->getLayout() as $section) {
            if (! ($section['enabled'] ?? true)) {
                continue;
            }

            $payload = $this->resolveSection($section, $promotionEngine);
            if ($payload !== null) {
                $resolved[] = $payload;
            }
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>|null
     */
    private function resolveSection(array $section, PromotionEngine $promotionEngine): ?array
    {
        $type = $section['type'] ?? '';
        $props = is_array($section['props'] ?? null) ? $section['props'] : [];

        return match ($type) {
            'hero_slider' => $this->resolveHeroSlider($section, $props),
            'flash_sale' => $this->resolveFlashSale($section, $props, $promotionEngine),
            'category_grid' => $this->resolveCategoryGrid($section, $props),
            'product_grid' => $this->resolveProductGrid($section, $props, $promotionEngine),
            'products_by_category' => $this->resolveProductsByCategory($section, $props, $promotionEngine),
            'promotion_banner' => $this->resolvePromotionBanner($section, $props),
            'blog_posts' => $this->resolveBlogPosts($section, $props),
            'spacer' => [
                'id' => $section['id'],
                'type' => 'spacer',
                'props' => [
                    'height' => (int) ($props['height'] ?? 32),
                ],
            ],
            default => null,
        };
    }

    /** @param  array<string, mixed>  $section
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>|null
     */
    private function resolveHeroSlider(array $section, array $props): ?array
    {
        $sliders = Slider::active()->orderBy('sort_order')->get();
        if ($sliders->isEmpty()) {
            return null;
        }

        return [
            'id' => $section['id'],
            'type' => 'hero_slider',
            'props' => $props,
            'sliders' => ModelSerializer::collection($sliders, [ModelSerializer::class, 'slider']),
        ];
    }

    /** @param  array<string, mixed>  $section
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>|null
     */
    private function resolveFlashSale(array $section, array $props, PromotionEngine $promotionEngine): ?array
    {
        $flashSaleService = app(FlashSaleService::class);
        $items = $flashSaleService->itemsFromProps($props);

        if ($items === []) {
            return null;
        }

        if (! $flashSaleService->isWithinPeriod($props)) {
            return null;
        }

        $productIds = array_map(fn ($item) => (int) $item['productId'], $items);
        $products = Product::where('is_active', true)
            ->whereIn('id', $productIds)
            ->get()
            ->sortBy(fn ($p) => array_search($p->id, $productIds, true))
            ->values();

        $promotionEngine->decorateProducts($products);
        if ($products->isEmpty()) {
            return null;
        }

        return [
            'id' => $section['id'],
            'type' => 'flash_sale',
            'props' => array_merge($props, [
                'showCountdown' => (bool) ($props['showCountdown'] ?? true),
                'actionHref' => $props['actionHref'] ?? '/products?flash_sale=1',
            ]),
            'products' => ModelSerializer::collection($products, [ModelSerializer::class, 'product']),
            'flashSaleEndsAt' => $flashSaleService->endsAtTimestamp($props),
        ];
    }

    /** @param  array<string, mixed>  $section
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>|null
     */
    private function resolveCategoryGrid(array $section, array $props): ?array
    {
        $categoryIds = array_values(array_filter(
            array_map('intval', is_array($props['categoryIds'] ?? null) ? $props['categoryIds'] : []),
            fn (int $id) => $id > 0,
        ));

        if ($categoryIds !== []) {
            $categories = Category::whereIn('id', $categoryIds)
                ->get()
                ->sortBy(fn ($category) => array_search($category->id, $categoryIds, true))
                ->values();
        } else {
            $categories = Category::whereNull('parent_id')
                ->orderBy('order')
                ->get();
        }

        if ($categories->isEmpty()) {
            return null;
        }

        $count = $categories->count();
        $rows = max(1, (int) ($props['rows'] ?? 1));
        $gridColumns = $this->categoryGridColumns($count, $rows);

        return [
            'id' => $section['id'],
            'type' => 'category_grid',
            'props' => array_merge([
                'title' => 'Kategori',
                'showImages' => true,
                'rows' => $rows,
                'gridColumns' => $gridColumns,
            ], $props, [
                'rows' => $rows,
                'gridColumns' => $gridColumns,
            ]),
            'categories' => ModelSerializer::collection($categories, [ModelSerializer::class, 'category']),
        ];
    }

    public function categoryGridColumns(int $count, int $rows = 1): int
    {
        if ($count <= 0) {
            return 1;
        }

        $rows = max(1, $rows);

        return (int) ceil($count / $rows);
    }

    /** @param  array<string, mixed>  $section
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>|null
     */
    private function resolveProductGrid(array $section, array $props, PromotionEngine $promotionEngine): ?array
    {
        $limit = max(1, (int) ($props['limit'] ?? 8));
        $source = $props['source'] ?? 'latest';
        $query = Product::where('is_active', true);

        $products = match ($source) {
            'featured' => $query->where('is_featured', true)->latest()->take($limit)->get(),
            'sale' => $query->whereNotNull('sale_price')->latest()->take($limit)->get(),
            'badge' => $query->where('badge_preset', $props['badgePreset'] ?? 'none')
                ->when(
                    ($props['badgePreset'] ?? '') === 'custom' && ! empty($props['badgeLabel']),
                    fn ($q) => $q->where('badge', $props['badgeLabel']),
                )
                ->latest()
                ->take($limit)
                ->get(),
            'manual' => $query->whereIn('id', $props['productIds'] ?? [])->take($limit)->get(),
            default => $query->latest()->take($limit)->get(),
        };

        $promotionEngine->decorateProducts($products);
        if ($products->isEmpty()) {
            return null;
        }

        return [
            'id' => $section['id'],
            'type' => 'product_grid',
            'props' => array_merge([
                'title' => 'Produk',
                'source' => $source,
                'limit' => $limit,
                'layout' => 'grid',
            ], $props),
            'products' => ModelSerializer::collection($products, [ModelSerializer::class, 'product']),
        ];
    }

    /** @param  array<string, mixed>  $section
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>|null
     */
    private function resolveProductsByCategory(array $section, array $props, PromotionEngine $promotionEngine): ?array
    {
        $categoryId = (int) ($props['categoryId'] ?? 0);
        if (! $categoryId) {
            return null;
        }

        $category = Category::find($categoryId);
        if (! $category) {
            return null;
        }

        $limit = max(1, (int) ($props['limit'] ?? 8));
        $products = Product::where('is_active', true)
            ->where('category_id', $categoryId)
            ->latest()
            ->take($limit)
            ->get();

        $promotionEngine->decorateProducts($products);
        if ($products->isEmpty()) {
            return null;
        }

        return [
            'id' => $section['id'],
            'type' => 'products_by_category',
            'props' => array_merge([
                'title' => $category->name,
                'categoryId' => $categoryId,
                'limit' => $limit,
                'layout' => 'grid',
            ], $props),
            'category' => ModelSerializer::category($category),
            'products' => ModelSerializer::collection($products, [ModelSerializer::class, 'product']),
        ];
    }

    /** @param  array<string, mixed>  $section
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>|null
     */
    private function resolvePromotionBanner(array $section, array $props): ?array
    {
        // #region agent log
        $debugLogPath = base_path('.cursor/debug-227592.log');
        @file_put_contents($debugLogPath, json_encode([
            'sessionId' => '227592',
            'hypothesisId' => 'H1-H3',
            'location' => 'HomepageLayoutService.php:resolvePromotionBanner',
            'message' => 'resolve promotion_banner',
            'data' => [
                'sectionId' => $section['id'] ?? null,
                'enabled' => $section['enabled'] ?? true,
                'title' => $props['title'] ?? null,
                'imagePath' => $props['imagePath'] ?? null,
                'imageUrlProp' => $props['imageUrl'] ?? null,
            ],
            'timestamp' => (int) (microtime(true) * 1000),
        ])."\n", FILE_APPEND);
        // #endregion

        if (empty($props['title']) && empty($props['imagePath'])) {
            // #region agent log
            @file_put_contents($debugLogPath, json_encode([
                'sessionId' => '227592',
                'hypothesisId' => 'H2',
                'location' => 'HomepageLayoutService.php:resolvePromotionBanner',
                'message' => 'promotion_banner skipped (empty title and imagePath)',
                'data' => ['sectionId' => $section['id'] ?? null],
                'timestamp' => (int) (microtime(true) * 1000),
            ])."\n", FILE_APPEND);
            // #endregion

            return null;
        }

        $imageUrl = null;
        if (! empty($props['imagePath'])) {
            $imageUrl = storage_url($props['imagePath']);
        }

        return [
            'id' => $section['id'],
            'type' => 'promotion_banner',
            'props' => array_merge([
                'title' => '',
                'subtitle' => '',
                'ctaLabel' => '',
                'ctaHref' => '',
                'imageAlt' => '',
                'metaTitle' => null,
                'metaDescription' => null,
            ], $props, [
                'imageUrl' => $imageUrl,
            ]),
        ];
    }

    /** @param  array<string, mixed>  $section
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>|null
     */
    private function resolveBlogPosts(array $section, array $props): ?array
    {
        $limit = max(1, (int) ($props['limit'] ?? 3));
        $posts = BlogPost::published()->latest('published_at')->take($limit)->get();
        if ($posts->isEmpty()) {
            return null;
        }

        return [
            'id' => $section['id'],
            'type' => 'blog_posts',
            'props' => array_merge([
                'title' => 'Blog Terbaru',
                'limit' => $limit,
            ], $props),
            'posts' => ModelSerializer::collection($posts, [ModelSerializer::class, 'blogPost']),
        ];
    }

    /** @param  array<int, array<string, mixed>>  $layout
     * @return list<array<string, mixed>>
     */
    public function normalizeLayout(array $layout): array
    {
        $normalized = [];

        foreach ($layout as $section) {
            if (! is_array($section)) {
                continue;
            }

            $type = $section['type'] ?? null;
            if (! in_array($type, self::SECTION_TYPES, true)) {
                continue;
            }

            $props = is_array($section['props'] ?? null) ? $section['props'] : [];

            if ($type === 'spacer') {
                $allowedHeights = [16, 24, 32, 48, 64, 96];
                $height = (int) ($props['height'] ?? 32);
                $props['height'] = in_array($height, $allowedHeights, true) ? $height : 32;
            }

            if ($type === 'category_grid') {
                if (isset($props['categoryIds']) && is_array($props['categoryIds'])) {
                    $props['categoryIds'] = array_values(array_unique(array_map(
                        'intval',
                        array_filter($props['categoryIds']),
                    )));
                }

                $props['rows'] = max(1, (int) ($props['rows'] ?? 1));
            }

            if ($type === 'flash_sale') {
                if (empty($props['endsAt'])) {
                    $props['endsAt'] = now()->endOfDay()->format('Y-m-d\TH:i');
                }

                $props['actionHref'] = $props['actionHref'] ?? '/products?flash_sale=1';

                if (isset($props['items']) && is_array($props['items'])) {
                    $props['items'] = array_values(array_map(
                        fn (array $item) => [
                            'productId' => (int) ($item['productId'] ?? 0),
                            'discountType' => in_array($item['discountType'] ?? '', ['percentage', 'fixed'], true)
                                ? $item['discountType']
                                : 'percentage',
                            'discountAmount' => (float) ($item['discountAmount'] ?? 0),
                        ],
                        array_filter($props['items'], fn ($item) => is_array($item) && ! empty($item['productId'])),
                    ));
                }
            }

            $normalized[] = [
                'id' => $section['id'] ?? Str::uuid()->toString(),
                'type' => $type,
                'enabled' => (bool) ($section['enabled'] ?? true),
                'props' => $props,
            ];
        }

        return $normalized;
    }

    /** @return list<array<string, string>> */
    public function sectionTypeOptions(): array
    {
        return [
            ['value' => 'hero_slider', 'label' => 'Slider Hero'],
            ['value' => 'flash_sale', 'label' => 'Flash Sale'],
            ['value' => 'category_grid', 'label' => 'Grid Kategori'],
            ['value' => 'product_grid', 'label' => 'Grid Produk'],
            ['value' => 'products_by_category', 'label' => 'Produk per Kategori'],
            ['value' => 'promotion_banner', 'label' => 'Banner Promosi'],
            ['value' => 'blog_posts', 'label' => 'Blog Terbaru'],
            ['value' => 'spacer', 'label' => 'Spacer'],
        ];
    }

    /** @return list<string> IDs section yang diperbarui */
    public function syncPromotionBannerFromCartRule(CartRule $cartRule): array
    {
        $layout = $this->getLayout();
        $updatedIds = [];
        $hasBannerSection = false;

        foreach ($layout as &$section) {
            if (($section['type'] ?? '') !== 'promotion_banner') {
                continue;
            }

            $hasBannerSection = true;

            $imagePath = '';
            $imageUrl = '';
            if (! empty($cartRule->banner_image)) {
                $imagePath = $this->copyBannerToHomepage($cartRule->banner_image);
                $imageUrl = storage_url($imagePath);
            }

            $section['props'] = array_merge($section['props'] ?? [], [
                'title' => $cartRule->name,
                'subtitle' => $cartRule->description ?? '',
                'ctaLabel' => 'Lihat Promo',
                'ctaHref' => $cartRule->slug ? '/promo/'.$cartRule->slug : '',
                'imagePath' => $imagePath,
                'imageUrl' => $imageUrl,
                'imageAlt' => $cartRule->name,
                'metaTitle' => $cartRule->meta_title ?? '',
                'metaDescription' => $cartRule->meta_description ?? '',
            ]);

            $updatedIds[] = $section['id'];
        }
        unset($section);

        if (! $hasBannerSection) {
            throw new RuntimeException(
                'Tidak ada section Banner Promosi di Homepage Builder. Tambahkan section tersebut terlebih dahulu di Konfigurasi Konten → Halaman Utama.',
            );
        }

        $this->saveLayout($layout);

        // #region agent log
        $debugLogPath = base_path('.cursor/debug-227592.log');
        @file_put_contents($debugLogPath, json_encode([
            'sessionId' => '227592',
            'hypothesisId' => 'H3-H4',
            'location' => 'HomepageLayoutService.php:syncPromotionBannerFromCartRule',
            'message' => 'banner sync completed',
            'data' => [
                'cartRuleId' => $cartRule->id,
                'updatedSectionIds' => $updatedIds,
                'bannerImage' => $cartRule->banner_image,
            ],
            'timestamp' => (int) (microtime(true) * 1000),
        ])."\n", FILE_APPEND);
        // #endregion

        return $updatedIds;
    }

    private function copyBannerToHomepage(string $sourcePath): string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($sourcePath)) {
            throw new RuntimeException('File banner promosi tidak ditemukan di storage.');
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg';
        $destPath = 'homepage-banners/'.Str::uuid().'.'.$extension;
        $disk->copy($sourcePath, $destPath);

        return $destPath;
    }
}
