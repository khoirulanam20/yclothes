<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        $query = Product::with('category');

        if ($search = request('search')) {
            $driver = $query->getConnection()->getDriverName();
            if ($driver === 'mysql') {
                $query->whereFullText(['name', 'description'], $search);
            } else {
                $query->where('name', 'like', "%{$search}%");
            }
        }

        if ($categorySlug = request('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
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
        $categories = Category::orderBy('order')->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function show($slug)
    {
        $product = Product::with('category')->where('slug', $slug)->firstOrFail();
        $product->increment('views');
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->inRandomOrder()
            ->take(4)
            ->get();

        return view('products.show', compact('product', 'relatedProducts'));
    }
}
