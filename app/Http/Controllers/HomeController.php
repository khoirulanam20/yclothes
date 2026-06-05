<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Slider;
use App\Services\PromotionEngine;
use App\Support\ModelSerializer;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __construct(private PromotionEngine $promotionEngine) {}

    public function index()
    {
        $featuredProducts = Product::where('is_featured', true)->take(8)->get();
        $newProducts = Product::latest()->take(8)->get();
        $flashSaleProducts = Product::whereNotNull('sale_price')->inRandomOrder()->take(4)->get();

        $this->promotionEngine->decorateProducts($featuredProducts);
        $this->promotionEngine->decorateProducts($newProducts);
        $this->promotionEngine->decorateProducts($flashSaleProducts);

        $flashSaleEnd = Setting::where('key', 'flash_sale_ends_at')->value('value');
        $flashSaleEndsAt = $flashSaleEnd ? strtotime($flashSaleEnd) : now()->endOfDay()->timestamp;

        $sliders = Slider::active()->orderBy('sort_order')->get();
        $latestPosts = BlogPost::published()->latest('published_at')->take(3)->get();

        return Inertia::render('Guest/Home', [
            'sliders' => ModelSerializer::collection($sliders, [ModelSerializer::class, 'slider']),
            'featuredProducts' => ModelSerializer::collection($featuredProducts, [ModelSerializer::class, 'product']),
            'newProducts' => ModelSerializer::collection($newProducts, [ModelSerializer::class, 'product']),
            'flashSaleProducts' => ModelSerializer::collection($flashSaleProducts, [ModelSerializer::class, 'product']),
            'flashSaleEndsAt' => $flashSaleEndsAt,
            'latestPosts' => ModelSerializer::collection($latestPosts, [ModelSerializer::class, 'blogPost']),
        ]);
    }
}
