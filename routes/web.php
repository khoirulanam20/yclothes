<?php

use App\Http\Controllers\Admin\ActivityLogController as AdminActivityLogController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminTourController;
use App\Http\Controllers\Admin\AdminRoleController as AdminAdminRoleController;
use App\Http\Controllers\Admin\AttributeController as AdminAttributeController;
use App\Http\Controllers\Admin\AttributeFamilyController as AdminAttributeFamilyController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\BlogPostController as AdminBlogPostController;
use App\Http\Controllers\Admin\CartRuleController as AdminCartRuleController;
use App\Http\Controllers\Admin\CatalogRuleController as AdminCatalogRuleController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CmsPageController as AdminCmsPageController;
use App\Http\Controllers\Admin\ConfigurationController as AdminConfigurationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EditorUploadController as AdminEditorUploadController;
use App\Http\Controllers\Admin\FaqCategoryController as AdminFaqCategoryController;
use App\Http\Controllers\Admin\FaqItemController as AdminFaqItemController;
use App\Http\Controllers\Admin\HomepageController as AdminHomepageController;
use App\Http\Controllers\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Admin\LinkTemplateController as AdminLinkTemplateController;
use App\Http\Controllers\Admin\NavigationController as AdminNavigationController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentBankController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductVariantController as AdminProductVariantController;
use App\Http\Controllers\Admin\PromotionPopupController as AdminPromotionPopupController;
use App\Http\Controllers\Admin\ReturnRequestController as AdminReturnRequestController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShippingCostController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Admin\StockMovementController as AdminStockMovementController;
use App\Http\Controllers\Admin\TaxRateController as AdminTaxRateController;
use App\Http\Controllers\Admin\TaxZoneController as AdminTaxZoneController;
use App\Http\Controllers\Admin\WarehouseController as AdminWarehouseController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChatbotProxyController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Customer\AddressController as CustomerAddressController;
use App\Http\Controllers\Customer\Auth\ForgotPasswordController as CustomerForgotPasswordController;
use App\Http\Controllers\Customer\Auth\LoginController as CustomerLoginController;
use App\Http\Controllers\Customer\Auth\RegisterController as CustomerRegisterController;
use App\Http\Controllers\Customer\Auth\ResetPasswordController as CustomerResetPasswordController;
use App\Http\Controllers\Customer\Auth\VerifyEmailController as CustomerVerifyEmailController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Customer\ReturnRequestController as CustomerReturnRequestController;
use App\Http\Controllers\Customer\ReviewController as CustomerReviewController;
use App\Http\Controllers\Customer\WishlistController as CustomerWishlistController;
use App\Http\Controllers\DokuController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KlikQrisController;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentConfirmationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PromotionLandingController;
use App\Http\Controllers\WilayahController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/embed/chatbot/widget.ver', [ChatbotProxyController::class, 'widgetVersion'])
    ->name('embed.chatbot.version');

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::redirect('/produk', '/products', 301);
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/cara-belanja', fn () => redirect()->route('pages.show', ['slug' => 'cara-belanja'], 301))->name('cara-belanja');
Route::get('/tentang-kami', fn () => redirect()->route('pages.show', ['slug' => 'tentang-kami'], 301))->name('about');
Route::get('/page/{slug}', [PageController::class, 'show'])->name('pages.show');
Route::get('/promo/{slug}', [PromotionLandingController::class, 'show'])->name('promo.show');
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/faq', [FaqController::class, 'index'])->name('faq.index');
Route::get('/sitemap.xml', function () {
    $path = public_path('sitemap.xml');
    if (! file_exists($path)) {
        Artisan::call('sitemap:generate');
    }

    return response()->file($path, ['Content-Type' => 'application/xml']);
})->name('sitemap');

Route::prefix('api/wilayah')->name('wilayah.')->group(function () {
    Route::get('/provinces', [WilayahController::class, 'provinces'])->name('provinces');
    Route::get('/regencies/{provinceCode}', [WilayahController::class, 'regencies'])->name('regencies');
    Route::get('/districts/{regencyCode}', [WilayahController::class, 'districts'])->name('districts');
    Route::get('/villages/{districtCode}', [WilayahController::class, 'villages'])->name('villages');
});

Route::prefix('account')->name('customer.')->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('/register', [CustomerRegisterController::class, 'showRegistrationForm'])->name('register');
        Route::post('/register', [CustomerRegisterController::class, 'register'])->middleware('throttle:5,1');
        Route::get('/login', [CustomerLoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [CustomerLoginController::class, 'login'])->middleware('throttle:5,1');
        Route::get('/forgot-password', [CustomerForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
        Route::post('/forgot-password', [CustomerForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('throttle:5,1');
        Route::get('/reset-password/{token}', [CustomerResetPasswordController::class, 'showResetForm'])->name('password.reset');
        Route::post('/reset-password', [CustomerResetPasswordController::class, 'reset'])->name('password.update')->middleware('throttle:5,1');
    });

    Route::middleware('auth:customer')->group(function () {
        Route::post('/logout', [CustomerLoginController::class, 'logout'])->name('logout');
        Route::get('/verify-email', [CustomerVerifyEmailController::class, 'notice'])->name('verification.notice');
        Route::get('/verify-email/{id}/{hash}', [CustomerVerifyEmailController::class, 'verify'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');
        Route::post('/email/verification-notification', [CustomerVerifyEmailController::class, 'send'])
            ->middleware('throttle:6,1')
            ->name('verification.send');
        Route::get('/profile', [CustomerProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [CustomerProfileController::class, 'update'])->name('profile.update');
        Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/confirm-received', [CustomerOrderController::class, 'confirmReceived'])->name('orders.confirm-received');
        Route::post('/orders/{order}/confirm-payment', [CustomerOrderController::class, 'confirmPayment'])->name('orders.confirm-payment');
        Route::post('/orders/{order}/reviews', [CustomerOrderController::class, 'storeReview'])->name('orders.reviews.store');
        Route::get('/returns', [CustomerReturnRequestController::class, 'index'])->name('returns.index');
        Route::get('/orders/{order}/returns/create', [CustomerReturnRequestController::class, 'create'])->name('returns.create');
        Route::post('/orders/{order}/returns', [CustomerReturnRequestController::class, 'store'])->name('returns.store');
        Route::get('/returns/{returnRequest}', [CustomerReturnRequestController::class, 'show'])->name('returns.show');
        Route::post('/returns/{returnRequest}/shipment', [CustomerReturnRequestController::class, 'submitShipment'])->name('returns.shipment');
        Route::resource('addresses', CustomerAddressController::class)->except(['show']);
        Route::get('/wishlist', [CustomerWishlistController::class, 'index'])->name('wishlist.index');
        Route::post('/wishlist/toggle', [CustomerWishlistController::class, 'toggle'])->name('wishlist.toggle');
        Route::get('/reviews/create/{product}', [CustomerReviewController::class, 'create'])->name('reviews.create');
        Route::post('/reviews/{product}', [CustomerReviewController::class, 'store'])->name('reviews.store');
    });
});

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/checkout-selection', [CartController::class, 'setCheckoutSelection'])->name('cart.checkout-selection');
    Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon');
    Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index')->middleware('customer.verified');
    Route::post('/checkout/shipping-cost', [CheckoutController::class, 'shippingCost'])->name('checkout.shipping-cost');
    Route::post('/checkout/process', [CheckoutController::class, 'process'])->name('checkout.process')->middleware('customer.verified');
});

Route::get('/order/track', [OrderController::class, 'track'])->name('order.track');
Route::post('/order/track', [OrderController::class, 'search'])->name('order.search')->middleware('throttle:10,1');

Route::middleware(['order.access'])->group(function () {
    Route::get('/order/success/{order:order_number}', [OrderController::class, 'success'])->name('order.success');
    Route::get('/order/{order:order_number}/klikqris-payment', [KlikQrisController::class, 'payment'])
        ->name('order.klikqris-payment');
    Route::post('/order/klikqris-verify/{order:order_number}', [KlikQrisController::class, 'verifyPayment'])
        ->name('order.klikqris-verify')
        ->middleware('throttle:30,1');
    Route::get('/order/{order:order_number}/confirm-payment', [PaymentConfirmationController::class, 'create'])->name('order.confirm-payment');
    Route::post('/order/{order:order_number}/confirm-payment', [PaymentConfirmationController::class, 'store'])->name('order.confirm-payment.store');
    Route::post('/order/{order:order_number}/confirm-received', [OrderController::class, 'confirmReceived'])->name('order.confirm-received');
    Route::get('/order/{order:order_number}/returns/create', [OrderController::class, 'createReturn'])->name('order.returns.create');
    Route::post('/order/{order:order_number}/returns', [OrderController::class, 'storeReturn'])->name('order.returns.store');
    Route::post('/order/{order:order_number}/reviews', [OrderController::class, 'storeReview'])->name('order.reviews.store');
    Route::post('/order/payment-finish/{order:order_number}', [CheckoutController::class, 'paymentFinish'])
        ->name('order.payment-finish')
        ->middleware('throttle:30,1');
    Route::get('/order/doku-return/{order:order_number}', [DokuController::class, 'return'])
        ->name('order.doku-return');
    Route::get('/order/{order:order_number}', [OrderController::class, 'show'])->name('order.show');
});

Route::post('/midtrans/notification', [MidtransController::class, 'notification'])
    ->name('midtrans.notification')
    ->middleware('throttle:120,1');

Route::post('/doku/notification', [DokuController::class, 'notification'])
    ->name('doku.notification')
    ->middleware('throttle:120,1');

Route::post('/klikqris/notification', [KlikQrisController::class, 'notification'])
    ->name('klikqris.notification')
    ->middleware('throttle:120,1');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'admin', 'admin.activity'])->group(function () {
        Route::get('/link-templates', [AdminLinkTemplateController::class, 'index'])->name('link-templates.index');

        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [AdminNotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [AdminNotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('/tours/{tourKey}/complete', [AdminTourController::class, 'complete'])->name('tours.complete');

        Route::middleware('permission:settings.manage')->group(function () {
            Route::get('/configuration', [AdminConfigurationController::class, 'index'])->name('configuration.index');
            Route::get('/configuration/search', [AdminConfigurationController::class, 'search'])->name('configuration.search');
            Route::post('/configuration/test-email', [AdminConfigurationController::class, 'testEmail'])->name('configuration.test-email');
            Route::get('/configuration/{slug}', [AdminConfigurationController::class, 'edit'])->where('slug', '.*')->name('configuration.edit');
            Route::post('/configuration/{slug}', [AdminConfigurationController::class, 'update'])->where('slug', '.*')->name('configuration.update');

            Route::get('/settings', [SettingController::class, 'edit'])->name('settings');
            Route::post('/settings', [SettingController::class, 'update']);
            Route::redirect('/theme', '/admin/configuration/general/design')->name('theme.edit');
            Route::post('/theme', fn () => redirect('/admin/configuration/general/design'));
            Route::redirect('/integrations', '/admin/configuration/general/seo')->name('integrations.edit');
            Route::post('/integrations', fn () => redirect('/admin/configuration/general/seo'));
            Route::redirect('/appearance', '/admin/configuration/general/tracking')->name('appearance');
            Route::post('/appearance', fn () => redirect('/admin/configuration/general/tracking'));
            Route::redirect('/promo-bar', '/admin/configuration/general/header_offer')->name('promo-bar.edit');
            Route::post('/promo-bar', fn () => redirect('/admin/configuration/general/header_offer'));
            Route::resource('payment-banks', PaymentBankController::class);
            Route::resource('shipping-costs', ShippingCostController::class);
            Route::resource('tax-rates', AdminTaxRateController::class);
            Route::resource('tax-zones', AdminTaxZoneController::class);
        });

        Route::middleware('permission:cms.manage,settings.manage')->group(function () {
            Route::get('/homepage', [AdminHomepageController::class, 'edit'])->name('homepage.edit');
            Route::put('/homepage', [AdminHomepageController::class, 'update'])->name('homepage.update');
            Route::get('/homepage/products/search', [AdminHomepageController::class, 'searchProducts'])->name('homepage.products.search');
            Route::post('/homepage/banner-image', [AdminHomepageController::class, 'uploadBannerImage'])->name('homepage.banner-image');
            Route::post('/homepage/sliders', [AdminHomepageController::class, 'storeSlider'])->name('homepage.sliders.store');
            Route::put('/homepage/sliders/{slider}', [AdminHomepageController::class, 'updateSlider'])->name('homepage.sliders.update');
            Route::delete('/homepage/sliders/{slider}', [AdminHomepageController::class, 'destroySlider'])->name('homepage.sliders.destroy');
        });

        Route::middleware('permission:orders.view,orders.manage')->group(function () {
            Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders');
            Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        });

        Route::middleware('permission:orders.manage')->group(function () {
            Route::post('/orders/{order}/payment', [AdminOrderController::class, 'payment'])->name('orders.payment');
            Route::post('/orders/{order}/status', [AdminOrderController::class, 'status'])->name('orders.status');
            Route::post('/orders/{order}/ship', [AdminOrderController::class, 'ship'])->name('orders.ship');
            Route::get('/orders/{order}/invoice', [AdminOrderController::class, 'invoice'])->name('orders.invoice');
            Route::post('/payment-confirmations/{paymentConfirmation}/approve', [AdminOrderController::class, 'approvePaymentConfirmation'])->name('payment-confirmations.approve');
            Route::post('/payment-confirmations/{paymentConfirmation}/reject', [AdminOrderController::class, 'rejectPaymentConfirmation'])->name('payment-confirmations.reject');
            Route::get('/returns', [AdminReturnRequestController::class, 'index'])->name('returns.index');
            Route::get('/returns/policy', [AdminReturnRequestController::class, 'policy'])->name('returns.policy');
            Route::post('/returns/policy', [AdminReturnRequestController::class, 'updatePolicy'])->name('returns.policy.update');
            Route::get('/returns/{returnRequest}', [AdminReturnRequestController::class, 'show'])->name('returns.show');
            Route::post('/returns/{returnRequest}/approve', [AdminReturnRequestController::class, 'approve'])->name('returns.approve');
            Route::post('/returns/{returnRequest}/reject', [AdminReturnRequestController::class, 'reject'])->name('returns.reject');
            Route::post('/returns/{returnRequest}/received', [AdminReturnRequestController::class, 'confirmReceived'])->name('returns.received');
            Route::post('/returns/{returnRequest}/resolve', [AdminReturnRequestController::class, 'resolve'])->name('returns.resolve');
            Route::post('/returns/{returnRequest}/ship-replacement', [AdminReturnRequestController::class, 'shipReplacement'])->name('returns.ship-replacement');
        });

        Route::middleware('permission:products.view,products.manage')->group(function () {
            Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
        });

        Route::middleware('permission:products.manage')->group(function () {
            Route::resource('categories', AdminCategoryController::class);
            Route::resource('attribute-families', AdminAttributeFamilyController::class);
            Route::resource('attributes', AdminAttributeController::class);
            Route::get('products/search', [AdminProductController::class, 'search'])->name('products.search');
            Route::resource('products', AdminProductController::class);
            Route::post('products/{product}/duplicate', [AdminProductController::class, 'duplicate'])->name('products.duplicate');
            Route::put('products/{product}/variants', [AdminProductVariantController::class, 'update'])->name('products.variants.update');
            Route::post('/reviews/{review}/approve', [AdminReviewController::class, 'approve'])->name('reviews.approve');
            Route::delete('/reviews/{review}/reject', [AdminReviewController::class, 'reject'])->name('reviews.reject');
        });

        Route::middleware('permission:inventory.manage')->group(function () {
            Route::resource('warehouses', AdminWarehouseController::class);
            Route::resource('inventories', AdminInventoryController::class);
            Route::get('/stock-movements', [AdminStockMovementController::class, 'index'])->name('stock-movements.index');
            Route::get('/stock-movements/adjustment', [AdminStockMovementController::class, 'createAdjustment'])->name('stock-movements.adjustment');
            Route::post('/stock-movements/adjustment', [AdminStockMovementController::class, 'storeAdjustment']);
            Route::get('/stock-movements/transfer', [AdminStockMovementController::class, 'createTransfer'])->name('stock-movements.transfer');
            Route::post('/stock-movements/transfer', [AdminStockMovementController::class, 'storeTransfer']);
        });

        Route::middleware('permission:promotions.manage')->group(function () {
            Route::post('cart-rules/{cart_rule}/sync-homepage', [AdminCartRuleController::class, 'syncHomepage'])->name('cart-rules.sync-homepage');
            Route::post('cart-rules/{cart_rule}/sync-popup', [AdminCartRuleController::class, 'syncPopup'])->name('cart-rules.sync-popup');
            Route::resource('cart-rules', AdminCartRuleController::class);
            Route::resource('catalog-rules', AdminCatalogRuleController::class);
            Route::resource('promotion-popups', AdminPromotionPopupController::class)->except(['show']);
        });

        Route::middleware('permission:cms.manage')->group(function () {
            Route::post('editor/upload-image', [AdminEditorUploadController::class, 'uploadImage'])->name('editor.upload-image');
            Route::get('cms-pages/builder/new', [AdminCmsPageController::class, 'newBuilder'])->name('cms-pages.builder.new');
            Route::post('cms-pages/builder', [AdminCmsPageController::class, 'storeBuilder'])->name('cms-pages.builder.store');
            Route::get('cms-pages/{cms_page}/builder', [AdminCmsPageController::class, 'builder'])->name('cms-pages.builder');
            Route::put('cms-pages/{cms_page}/builder', [AdminCmsPageController::class, 'saveBuilder'])->name('cms-pages.builder.save');
            Route::get('cms-pages/{cms_page}/preview', [AdminCmsPageController::class, 'preview'])->name('cms-pages.preview');
            Route::resource('cms-pages', AdminCmsPageController::class)->only(['index', 'destroy']);
            Route::resource('blog-posts', AdminBlogPostController::class);
            Route::redirect('/sliders', '/admin/homepage');
            Route::get('/sliders/{any}', fn () => redirect('/admin/homepage'))->where('any', '.*');
            Route::resource('navigation', AdminNavigationController::class)->except(['show']);
            Route::resource('faq-categories', AdminFaqCategoryController::class)->except(['show']);
            Route::resource('faq-categories.items', AdminFaqItemController::class)->except(['show']);
        });

        Route::middleware('permission:staff.manage')->group(function () {
            Route::resource('roles', AdminAdminRoleController::class)->except(['show']);
            Route::resource('staff', AdminStaffController::class)->except(['show']);
            Route::get('/activity-logs', [AdminActivityLogController::class, 'index'])->name('activity-logs.index');
        });
    });
});
