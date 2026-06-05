<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\Review;
use App\Models\Wishlist;
use App\Services\CategoryTreeService;
use App\Services\InventoryService;
use App\Services\PromotionEngine;
use App\Support\ModelSerializer;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService,
        private PromotionEngine $promotionEngine,
        private CategoryTreeService $categoryTree,
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
            ],
        ]);
    }

    public function show($slug)
    {
        $product = Product::with(['category', 'attributeValues.attribute', 'activeVariants'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
        $this->promotionEngine->decorateProduct($product);
        $product->increment('views');

        $relatedProducts = ProductRelation::where('product_id', $product->id)
            ->where('type', 'related')
            ->with('relatedProduct')
            ->get()
            ->pluck('relatedProduct')
            ->filter();

        if ($relatedProducts->isEmpty()) {
            $relatedProducts = Product::where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->inRandomOrder()
                ->take(4)
                ->get();
        }

        $this->promotionEngine->decorateProducts($relatedProducts);

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

        $variantsJson = $product->isConfigurable()
            ? $product->activeVariants->map(function ($variant) use ($product) {
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'name' => $variant->name,
                    'price' => $variant->final_price,
                    'image_url' => $variant->image_url ?: $product->image_url,
                    'size' => $variant->attributes['size'] ?? null,
                    'color' => $variant->attributes['color'] ?? null,
                    'color_hex' => $variant->attributes['color_hex'] ?? null,
                    'stock' => $this->inventoryService->getAvailableStock($product, $variant),
                    'track_stock' => $variant->track_stock || $product->track_stock,
                    'allow_backorder' => $variant->allow_backorder || $product->allow_backorder,
                ];
            })->values()
            : collect();

        $productStock = $this->inventoryService->getAvailableStock($product);

        $categoryPath = $product->category
            ? $this->categoryTree->breadcrumbPath($product->category)
            : [];

        $variants = $variantsJson->map(fn ($v) => [
            'id' => $v['id'],
            'sku' => $v['sku'],
            'price' => $v['price'],
            'imageUrl' => $v['image_url'],
            'size' => $v['size'],
            'color' => $v['color'],
            'colorHex' => $v['color_hex'],
            'stock' => $v['stock'],
            'trackStock' => $v['track_stock'],
        ])->values()->all();

        return Inertia::render('Guest/Products/Show', [
            'product' => ModelSerializer::product($product, true),
            'categoryPath' => $categoryPath,
            'relatedProducts' => ModelSerializer::collection($relatedProducts, [ModelSerializer::class, 'product']),
            'reviews' => ModelSerializer::collection($reviews, [ModelSerializer::class, 'review']),
            'inWishlist' => $inWishlist,
            'productStock' => $productStock,
            'variants' => $variants,
        ]);
    }
}
