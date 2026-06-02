<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $productCount = Product::count();
        $categoryCount = Category::count();
        $orderCount = Order::count();
        $pendingCount = Order::where('order_status', 'pending')->count();
        $latestProducts = Product::with('category')->latest()->take(5)->get();
        $latestOrders = Order::latest()->take(5)->get();

        return view('admin.dashboard', compact(
            'productCount', 'categoryCount', 'orderCount', 'pendingCount',
            'latestProducts', 'latestOrders'
        ));
    }
}
