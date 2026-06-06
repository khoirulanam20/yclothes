<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Services\AdminBadgeService;
use App\Services\InventoryService;
use App\Support\ModelSerializer;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(InventoryService $inventoryService, AdminBadgeService $badgeService)
    {
        $latestProducts = Product::with('category')->latest()->take(5)->get();
        $latestOrders = Order::latest()->take(5)->get();
        $lowStockItems = $inventoryService->lowStockItems()->load(['product', 'warehouse']);
        $recentActivities = ActivityLog::with('user')->latest('created_at')->take(5)->get();

        return Inertia::render('Admin/Dashboard', [
            'productCount' => Product::count(),
            'categoryCount' => Category::count(),
            'orderCount' => Order::count(),
            'pendingCount' => $badgeService->ordersAwaitingActionCount(),
            'latestProducts' => ModelSerializer::collection($latestProducts, fn ($p) => ModelSerializer::product($p)),
            'latestOrders' => ModelSerializer::collection($latestOrders, fn ($o) => ModelSerializer::order($o)),
            'lowStockItems' => ModelSerializer::collection($lowStockItems, [ModelSerializer::class, 'lowStockItem']),
            'recentActivities' => ModelSerializer::collection($recentActivities, [ModelSerializer::class, 'activityLog']),
        ]);
    }
}
