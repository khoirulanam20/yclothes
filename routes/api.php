<?php

use App\Http\Controllers\Api\Pos\AuthController;
use App\Http\Controllers\Api\Pos\BootstrapController;
use App\Http\Controllers\Api\Pos\CartPreviewController;
use App\Http\Controllers\Api\Pos\CatalogSyncController;
use App\Http\Controllers\Api\Pos\CategoryController;
use App\Http\Controllers\Api\Pos\CustomerController;
use App\Http\Controllers\Api\Pos\HeldCartController;
use App\Http\Controllers\Api\Pos\OfflineSyncController;
use App\Http\Controllers\Api\Pos\OrderController;
use App\Http\Controllers\Api\Pos\ProductController;
use App\Http\Controllers\Api\Pos\ReportController;
use App\Http\Controllers\Api\Pos\ShiftController;
use App\Http\Controllers\Api\Pos\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix('pos')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('api.pos.login');

    Route::middleware(['auth:sanctum', 'admin', 'permission:pos.access'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.pos.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('api.pos.me');
        Route::patch('/me', [AuthController::class, 'update'])->name('api.pos.me.update');

        Route::get('/bootstrap', BootstrapController::class)->name('api.pos.bootstrap');
        Route::get('/warehouses', [WarehouseController::class, 'index'])->name('api.pos.warehouses.index');
        Route::get('/categories', [CategoryController::class, 'index'])->name('api.pos.categories.index');
        Route::get('/catalog/sync', CatalogSyncController::class)->name('api.pos.catalog.sync');

        Route::get('/shifts/current', [ShiftController::class, 'current'])->name('api.pos.shifts.current');
        Route::get('/shifts/history', [ShiftController::class, 'history'])->name('api.pos.shifts.history');
        Route::get('/shifts/{shift}/summary', [ShiftController::class, 'summary'])->name('api.pos.shifts.summary');
        Route::get('/reports/summary', [ReportController::class, 'summary'])->name('api.pos.reports.summary');

        Route::get('/products', [ProductController::class, 'index'])->name('api.pos.products.index');
        Route::get('/products/by-sku/{sku}', [ProductController::class, 'bySku'])->name('api.pos.products.by-sku');
        Route::get('/products/{product}', [ProductController::class, 'show'])->name('api.pos.products.show');

        Route::post('/cart/preview', CartPreviewController::class)->name('api.pos.cart.preview');

        Route::get('/customers', [CustomerController::class, 'index'])->name('api.pos.customers.index');
        Route::get('/customers/search', [CustomerController::class, 'search'])->name('api.pos.customers.search');

        Route::get('/held-carts', [HeldCartController::class, 'index'])->name('api.pos.held-carts.index');

        Route::get('/orders', [OrderController::class, 'index'])->name('api.pos.orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('api.pos.orders.show');
        Route::get('/orders/{order}/receipt', [OrderController::class, 'receipt'])->name('api.pos.orders.receipt');

        Route::middleware('permission:pos.sell')->group(function () {
            Route::post('/shifts/open', [ShiftController::class, 'open'])
                ->middleware('throttle:60,1')
                ->name('api.pos.shifts.open');
            Route::post('/shifts/close', [ShiftController::class, 'close'])
                ->middleware('throttle:60,1')
                ->name('api.pos.shifts.close');
            Route::post('/customers/quick', [CustomerController::class, 'quick'])
                ->name('api.pos.customers.quick');
            Route::post('/orders', [OrderController::class, 'store'])
                ->middleware('throttle:60,1')
                ->name('api.pos.orders.store');
            Route::post('/held-carts', [HeldCartController::class, 'store'])
                ->name('api.pos.held-carts.store');
            Route::post('/held-carts/{heldCart}/resume', [HeldCartController::class, 'resume'])
                ->name('api.pos.held-carts.resume');
            Route::delete('/held-carts/{heldCart}', [HeldCartController::class, 'destroy'])
                ->name('api.pos.held-carts.destroy');
            Route::post('/orders/sync', OfflineSyncController::class)
                ->middleware('throttle:60,1')
                ->name('api.pos.orders.sync');
        });

        Route::post('/orders/{order}/void', [OrderController::class, 'void'])
            ->middleware(['permission:pos.manage', 'throttle:60,1'])
            ->name('api.pos.orders.void');
    });
});
