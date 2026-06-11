<?php

namespace App\Http\Controllers;

use App\Enums\BadgePreset;
use App\Models\Attribute;
use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\Wishlist;
use App\Services\CategoryTreeService;
use App\Services\FlashSaleService;
use App\Services\InventoryService;
use App\Services\PromotionEngine;
use App\Services\ProductRelationService;
use App\Support\ModelSerializer;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService,
        private PromotionEngine $promotionEngine,
        private CategoryTreeService $categoryTree,
        private FlashSaleService $flashSaleService,
        private ProductRelationService $relationService,
    ) {}
    public function index()
    {
        $query = Product::with('category')->where('is_active', true);

        if ($search = request('search')) {
            $driver = $query->getConnection()->getDriverName();
            if ($driver === 'mysql') {
                $query->whereFullText(['name', 'description'], $search);
            } else {
                $query->where('name', 'like', "%{$search}%");
            }
        }

        if ($categorySlug = request('category')) {
            $category = Category::where('slug', $categorySlug)->first();
            if ($category) {
                $categoryIds = $this->categoryTree->expandIds([$category->id]);
                $query->whereIn('category_id', $categoryIds);
            }
        }

        $filterableAttributes = Attribute::where('is_filterable', true)
            ->with('options')
            ->orderBy('sort_order')
            ->get();

        foreach ($filterableAttributes as $attribute) {
            $param = 'attr_'.$attribute->code;
            if (! $value = request($param)) {
                continue;
            }

            $query->whereHas('attributeValues', function ($q) use ($attribute, $value) {
                $q->where('attribute_id', $attribute->id)
                    ->where(function ($inner) use ($value) {
                        $inner->where('value', $value)
                            ->orWhere('value', 'like', '%"'.$value.'"%');
                    });
            });
        }

        if ($minPrice = request('min_price')) {
            $query->whereRaw('COALESCE(sale_price, price) >= ?', [(int) $minPrice]);
        }

        if ($maxPrice = request('max_price')) {
            $query->whereRaw('COALESCE(sale_price, price) <= ?', [(int) $maxPrice]);
        }

        $flashSaleFilter = request()->boolean('flash_sale');
        if ($flashSaleFilter) {
            $flashIds = $this->flashSaleService->activeProductIds();
            $query->whereIn('id', $flashIds !== [] ? $flashIds : [0]);
        }

        if (request()->boolean('featured')) {
            $query->where('is_featured', true);
        }

        if (request()->boolean('on_sale')) {
            $query->whereNotNull('sale_price');
        }

        $badgeLabelFilter = $this->normalizeBadgeLabelFilter(request('badge_label'));
        $badgeFilter = request('badge');

        if ($badgeLabelFilter) {
            $query->where('badge_preset', BadgePreset::Custom)
                ->where('badge', $badgeLabelFilter);
        } elseif ($badgeFilter && ($badgePreset = BadgePreset::tryFrom($badgeFilter))) {
            $query->where('badge_preset', $badgePreset);
        }

        if ($sort = request('sort')) {
            $query = match ($sort) {
                'price_asc' => $query->orderBy('price'),
                'price_desc' => $query->orderBy('price', 'desc'),
                default => $query->latest(),
            };
        } else {
            $query->latest();
        }

        $products = $query->paginate(12);
        $this->promotionEngine->decorateProducts($products);

        if (setting('out_of_stock_behavior', 'show_label') === 'hide') {
            $products->setCollection(
                $products->getCollection()->filter(
                    fn (Product $product) => ! $this->inventoryService->shouldHideFromCatalog($product),
                )->values(),
            );
        }
        $roots = Category::tree();
        $activeCategory = null;

        if ($categorySlug = request('category')) {
            $category = Category::where('slug', $categorySlug)->first();
            if ($category) {
                $activeCategory = [
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'breadcrumbPath' => $this->categoryTree->breadcrumbPath($category),
                ];
            }
        }

        return Inertia::render('Guest/Products/Index', [
            'products' => ModelSerializer::paginated($products, [ModelSerializer::class, 'product']),
            'categories' => $this->categoryTree->serializeTree($roots),
            'activeCategory' => $activeCategory,
            'filters' => [
                'search' => request('search'),
                'category' => request('category'),
                'sort' => request('sort'),
                'min_price' => request('min_price'),
                'max_price' => request('max_price'),
                'flash_sale' => $flashSaleFilter ? '1' : null,
                'featured' => request()->boolean('featured') ? '1' : null,
                'on_sale' => request()->boolean('on_sale') ? '1' : null,
                'badge' => $badgeFilter && BadgePreset::tryFrom($badgeFilter) ? $badgeFilter : null,
                'badge_label' => $badgeLabelFilter ?: null,
            ],
            'pageTitle' => $this->resolveProductsPageTitle($flashSaleFilter, $badgeFilter, $badgeLabelFilter),
        ]);
    }

    private function normalizeBadgeLabelFilter(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $label = trim($value);
        if ($label === '' || strlen($label) > 50) {
            return null;
        }

        return $label;
    }

    private function resolveProductsPageTitle(bool $flashSaleFilter, ?string $badgeFilter, ?string $badgeLabelFilter): ?string
    {
        if ($flashSaleFilter) {
            return 'Flash Sale';
        }

        if (request()->boolean('featured')) {
            return 'Produk Unggulan';
        }

        if (request()->boolean('on_sale')) {
            return 'Produk Sale';
        }

        if ($badgeLabelFilter) {
            return 'Produk Badge '.$badgeLabelFilter;
        }

        $badgePreset = $badgeFilter ? BadgePreset::tryFrom($badgeFilter) : null;
        if ($badgePreset) {
            return 'Produk Badge '.$badgePreset->label();
        }

        return null;
    }

    public function show($slug)
    {
        $product = Product::with(['category', 'attributeValues.attribute', 'activeVariants'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
        $this->promotionEngine->decorateProduct($product);
        $product->increment('views');

        $relatedProducts = $this->relationService->resolveForStorefront(
            $product,
            ProductRelationService::TYPE_RELATED,
            4,
        );

        if ($relatedProducts->isEmpty()) {
            $relatedProducts = Product::where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->where('is_active', true)
                ->inRandomOrder()
                ->take(4)
                ->get();
        }

        $upSellProducts = $this->relationService->resolveForStorefront(
            $product,
            ProductRelationService::TYPE_UP_SELL,
            4,
        );

        $this->promotionEngine->decorateProducts($relatedProducts);
        $this->promotionEngine->decorateProducts($upSellProducts);

        $reviews = Review::with('customer')
            ->where('product_id', $product->id)
            ->where('is_approved', true)
            ->latest('created_at')
            ->take(10)
            ->get();

        $inWishlist = false;
        if ($customer = Auth::guard('customer')->user()) {
            $inWishlist = Wishlist::where('customer_id', $customer->id)
                ->where('product_id', $product->id)
                ->exists();
        }

        $variants = $product->isConfigurable()
            ? $product->activeVariants->map(function ($variant) use ($product) {
                return array_merge(ModelSerializer::variant($variant, $product), [
                    'size' => $variant->attributes['size'] ?? null,
                    'color' => $variant->attributes['color'] ?? null,
                    'colorHex' => $variant->attributes['color_hex'] ?? null,
                    'trackStock' => $variant->track_stock || $product->track_stock,
                ]);
            })->values()->all()
            : [];

        $productStock = $this->inventoryService->getAvailableStock($product);
        $isPurchasable = $this->inventoryService->canOrder($product, null, 1);
        $isOutOfStock = $this->inventoryService->isOutOfStock($product);

        $categoryPath = $product->category
            ? $this->categoryTree->breadcrumbPath($product->category)
            : [];

        return Inertia::render('Guest/Products/Show', [
            'product' => ModelSerializer::product($product, true),
            'categoryPath' => $categoryPath,
            'relatedProducts' => ModelSerializer::collection($relatedProducts, [ModelSerializer::class, 'product']),
            'upSellProducts' => ModelSerializer::collection($upSellProducts, [ModelSerializer::class, 'product']),
            'reviews' => ModelSerializer::collection($reviews, [ModelSerializer::class, 'review']),
            'inWishlist' => $inWishlist,
            'productStock' => $productStock,
            'isPurchasable' => $isPurchasable,
            'isOutOfStock' => $isOutOfStock,
            'variants' => $variants,
        ]);
    }
}
