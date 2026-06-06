<?php

namespace Tests\Feature;

use App\Models\AdminNotification;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentBank;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\Review;
use App\Models\Setting;
use App\Models\ShippingCost;
use App\Models\User;
use App\Services\AdminBadgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_dashboard_shares_admin_badges(): void
    {
        $admin = User::where('is_admin', true)->first();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('adminBadges')
                ->where('adminBadges.orders', fn ($count) => is_int($count))
                ->where('adminBadges.notificationsUnread', fn ($count) => is_int($count))
            );
    }

    public function test_orders_awaiting_action_count_includes_confirmed_and_manual_unpaid(): void
    {
        $service = app(AdminBadgeService::class);

        $order = $this->createOrder();
        $this->assertGreaterThanOrEqual(1, $service->ordersAwaitingActionCount());

        $order->updateTrusted(['order_status' => 'confirmed', 'payment_status' => 'paid']);
        $this->assertGreaterThanOrEqual(1, $service->ordersAwaitingActionCount());

        $order->updateTrusted(['order_status' => 'completed', 'completed_at' => now()]);
        $this->assertEquals(0, $service->ordersAwaitingActionCount());
    }

    public function test_returns_and_reviews_badge_counts(): void
    {
        $service = app(AdminBadgeService::class);
        $order = $this->createOrder();

        ReturnRequest::create([
            'order_id' => $order->id,
            'customer_id' => Customer::first()->id,
            'status' => 'pending_review',
        ]);

        Review::create([
            'product_id' => Product::first()->id,
            'order_id' => $order->id,
            'rating' => 5,
            'review' => 'Bagus',
            'is_approved' => false,
            'created_at' => now(),
        ]);

        $this->assertEquals(1, $service->returnsAwaitingActionCount());
        $this->assertEquals(1, $service->pendingReviewsCount());
    }

    public function test_notification_api_and_mark_read(): void
    {
        $admin = User::where('is_admin', true)->first();

        $notification = AdminNotification::notify(
            'order_created',
            'Pesanan Baru #TEST',
            'Total test',
            ['order_id' => 1],
        );

        $this->actingAs($admin)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Pesanan Baru #TEST']);

        $this->actingAs($admin)
            ->post(route('admin.notifications.read', $notification))
            ->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);

        AdminNotification::notify('return_submitted', 'Retur baru', null, []);
        $this->actingAs($admin)
            ->post(route('admin.notifications.read-all'))
            ->assertOk();

        $this->assertEquals(0, AdminNotification::whereNull('read_at')->count());
    }

    public function test_pending_review_creates_admin_notification(): void
    {
        Setting::updateOrCreate(['key' => 'auto_approve_reviews'], ['value' => '0']);
        clear_settings_cache();

        $order = $this->createOrder();
        $order->updateTrusted(['order_status' => 'completed', 'completed_at' => now()]);
        $item = $order->items()->first();

        $this->post(route('order.reviews.store', $order), [
            'order_item_id' => $item->id,
            'rating' => 5,
            'review' => 'Produk bagus',
        ])->assertRedirect();

        $this->assertDatabaseHas('admin_notifications', [
            'type' => 'review_submitted',
            'title' => 'Ulasan Baru Menunggu Persetujuan',
        ]);
    }

    public function test_review_can_include_images(): void
    {
        Setting::updateOrCreate(['key' => 'auto_approve_reviews'], ['value' => '1']);
        clear_settings_cache();

        $order = $this->createOrder();
        $order->updateTrusted(['order_status' => 'completed', 'completed_at' => now()]);
        $item = $order->items()->first();

        $this->post(route('order.reviews.store', $order), [
            'order_item_id' => $item->id,
            'rating' => 5,
            'review' => 'Produk bagus dengan foto',
            'images' => [
                UploadedFile::fake()->image('review-1.jpg'),
                UploadedFile::fake()->image('review-2.jpg'),
            ],
        ])->assertRedirect();

        $review = Review::where('order_item_id', $item->id)->first();

        $this->assertNotNull($review);
        $this->assertCount(2, $review->images);
        $this->assertCount(2, $review->images_url);
        Storage::disk('public')->assertExists($review->images[0]);
    }

    private function createOrder(): Order
    {
        $product = Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $shipping = ShippingCost::first();
        $bank = PaymentBank::first();

        $this->post('/checkout/process', array_merge([
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'notify-test@example.com',
            'shipping_address' => 'Jl. Test',
            'shipping_city' => $shipping->id,
            'payment_method' => 'bank_'.$bank->id,
        ], $this->checkoutWilayahFields()));

        return Order::where('customer_email', 'notify-test@example.com')->first();
    }
}
