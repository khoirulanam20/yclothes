<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('order')->get();
        $featuredProducts = Product::where('is_featured', true)->take(8)->get();
        $newProducts = Product::latest()->take(8)->get();
        $flashSaleProducts = Product::whereNotNull('sale_price')->inRandomOrder()->take(4)->get();

        $flashSaleEnd = Setting::where('key', 'flash_sale_ends_at')->value('value');
        $flashSaleEndsAt = $flashSaleEnd ? strtotime($flashSaleEnd) : now()->endOfDay()->timestamp;

        return view('home.index', compact('categories', 'featuredProducts', 'newProducts', 'flashSaleProducts', 'flashSaleEndsAt'));
    }
}
