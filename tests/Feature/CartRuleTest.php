<?php

namespace Tests\Feature;

use App\Models\CartRule;
use App\Models\CartRuleUsage;
use App\Models\Category;
use App\Models\Customer;
use App\Models\PaymentBank;
use App\Models\Product;
use App\Models\ShippingCost;
use App\Models\User;
use App\Services\CartService;
use App\Services\PromotionEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartRuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_coupon_applies_percentage_discount(): void
    {
        CartRule::create([
            'name' => 'Diskon 10%',
            'coupon_code' => 'SAVE10',
            'discount_type' => 'percentage',
            'discount_amount' => 10,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        $product = Product::first();
        $lineItems = [['product' => $product, 'qty' => 2]];
        $subtotal = $product->final_price * 2;

        $result = app(PromotionEngine::class)->applyToCart($lineItems, $subtotal, 'SAVE10');

        $this->assertSame((int) round($subtotal * 0.1), $result['discount_amount']);
        $this->assertNotNull($result['cart_rule']);
    }

    public function test_coupon_apply_works_with_mixed_case_code_in_database(): void
    {
        CartRule::create([
            'name' => 'Kupon Lower',
            'coupon_code' => 'kupon',
            'discount_type' => 'percentage',
            'discount_amount' => 10,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        $product = Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $response = $this->post('/cart/coupon', ['coupon_code' => 'KUPON', 'redirect' => 'cart']);

        $response->assertSessionHas('success');
        $this->assertSame('KUPON', session(CartService::COUPON_SESSION_KEY));
    }

    public function test_cart_page_accepts_valid_coupon(): void
    {
        CartRule::create([
            'name' => 'Kupon Test',
            'coupon_code' => 'HEMAT50',
            'discount_type' => 'fixed',
            'discount_amount' => 50_000,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        $product = Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $response = $this->post('/cart/coupon', ['coupon_code' => 'HEMAT50', 'redirect' => 'cart']);

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('success');
        $this->assertSame('HEMAT50', session(CartService::COUPON_SESSION_KEY));
    }

    public function test_admin_can_save_cart_rule_with_categories(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();
        $category = Category::where('slug', 'pria-kemeja')->first();

        $this->actingAs($admin)->post(route('admin.cart-rules.store'), [
            'name' => 'Kupon Kategori',
            'coupon_code' => 'KATPRIA',
            'discount_type' => 'percentage',
            'discount_amount' => 10,
            'category_ids' => [$category->id],
            'uses_per_coupon' => 0,
            'uses_per_customer' => 0,
            'start_date' => now()->subDay()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'is_active' => true,
        ])->assertRedirect(route('admin.cart-rules.index'));

        $rule = CartRule::where('coupon_code', 'KATPRIA')->first();
        $this->assertNotNull($rule);
        $this->assertSame([$category->id], $rule->category_ids);
    }

    public function test_coupon_rejected_when_cart_has_no_matching_category(): void
    {
        $wanitaCategory = Category::where('slug', 'wanita-dress')->first();
        $priaCategory = Category::where('slug', 'pria-kemeja')->first();

        CartRule::create([
            'name' => 'Kupon Wanita',
            'coupon_code' => 'WANITA10',
            'discount_type' => 'percentage',
            'discount_amount' => 10,
            'category_ids' => [$wanitaCategory->id],
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        $product = Product::where('category_id', $priaCategory->id)->first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $response = $this->post('/cart/coupon', [
            'coupon_code' => 'WANITA10',
            'redirect' => 'cart',
        ]);

        $response->assertSessionHas('error');
        $this->assertNull(session(CartService::COUPON_SESSION_KEY));
    }

    public function test_coupon_applies_when_cart_matches_category(): void
    {
        $wanitaCategory = Category::where('slug', 'wanita-dress')->first();

        CartRule::create([
            'name' => 'Kupon Wanita',
            'coupon_code' => 'WANITAOK',
            'discount_type' => 'percentage',
            'discount_amount' => 10,
            'category_ids' => [$wanitaCategory->id],
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        $product = Product::where('category_id', $wanitaCategory->id)->first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $response = $this->post('/cart/coupon', [
            'coupon_code' => 'WANITAOK',
            'redirect' => 'cart',
        ]);

        $response->assertSessionHas('success');
        $this->assertSame('WANITAOK', session(CartService::COUPON_SESSION_KEY));
    }

    public function test_logged_in_customer_respects_per_customer_limit(): void
    {
        $customer = Customer::factory()->create();

        $rule = CartRule::create([
            'name' => 'Kupon 2x',
            'coupon_code' => 'TWICE',
            'discount_type' => 'fixed',
            'discount_amount' => 10_000,
            'uses_per_customer' => 2,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        CartRuleUsage::create([
            'cart_rule_id' => $rule->id,
            'customer_id' => $customer->id,
            'times_used' => 2,
        ]);

        $error = app(PromotionEngine::class)->validateCoupon('TWICE', $customer->id);

        $this->assertSame('Anda sudah menggunakan kupon ini.', $error);
    }

    public function test_guest_coupon_usage_tracked_by_email(): void
    {
        $rule = CartRule::create([
            'name' => 'Kupon Email',
            'coupon_code' => 'EMAIL1',
            'discount_type' => 'fixed',
            'discount_amount' => 10_000,
            'uses_per_customer' => 1,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        app(PromotionEngine::class)->recordCouponUsage($rule, null, 'Guest@Example.com');

        $this->assertDatabaseHas('cart_rule_usages', [
            'cart_rule_id' => $rule->id,
            'customer_id' => null,
            'customer_email' => 'guest@example.com',
            'times_used' => 1,
        ]);

        $error = app(PromotionEngine::class)->validateCoupon('EMAIL1', null, 'guest@example.com');
        $this->assertSame('Anda sudah menggunakan kupon ini.', $error);
        $this->assertNull(app(PromotionEngine::class)->validateCoupon('EMAIL1', null, 'other@example.com'));
    }

    public function test_admin_can_save_free_shipping_cart_rule_with_coupon(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();

        $this->actingAs($admin)->post(route('admin.cart-rules.store'), [
            'name' => 'Gratis Ongkir Natal',
            'coupon_code' => 'ONGKIR0',
            'discount_type' => 'free_shipping',
            'uses_per_coupon' => 100,
            'uses_per_customer' => 1,
            'start_date' => now()->subDay()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'is_active' => true,
        ])->assertRedirect(route('admin.cart-rules.index'));

        $rule = CartRule::where('coupon_code', 'ONGKIR0')->first();
        $this->assertNotNull($rule);
        $this->assertSame('free_shipping', $rule->discount_type);
        $this->assertSame(0, (int) $rule->discount_amount);
    }

    public function test_free_shipping_coupon_zeroes_shipping_cost(): void
    {
        CartRule::create([
            'name' => 'Kupon Gratis Ongkir',
            'coupon_code' => 'FREESHIP',
            'discount_type' => 'free_shipping',
            'discount_amount' => 0,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        $product = Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);
        session([CartService::COUPON_SESSION_KEY => 'FREESHIP']);

        $pricing = app(\App\Services\CartPricingService::class)->build();
        $this->assertTrue($pricing['free_shipping']);
        $this->assertSame(0, $pricing['discount_amount']);

        $response = $this->postJson('/checkout/shipping-options', [
            'regency_code' => '33.73',
            'postal_code' => '56211',
        ]);

        $response->assertOk()
            ->assertJsonPath('freeShipping', true)
            ->assertJsonPath('options.0.cost', 0);
    }

    public function test_settings_free_shipping_applies_above_threshold(): void
    {
        \App\Models\Setting::updateOrCreate(['key' => 'free_shipping_enabled'], ['value' => '1']);
        \App\Models\Setting::updateOrCreate(['key' => 'free_shipping_min_order'], ['value' => '500000']);
        clear_settings_cache();

        $product = Product::first();
        $lineItems = [['product' => $product, 'qty' => 1]];
        $subtotalBelow = $product->final_price;
        $subtotalAbove = 600_000;

        $engine = app(PromotionEngine::class);
        $this->assertFalse($engine->applyToCart($lineItems, $subtotalBelow)['free_shipping']);
        $this->assertTrue($engine->applyToCart($lineItems, $subtotalAbove)['free_shipping']);
    }

    public function test_settings_free_shipping_always_when_min_order_zero(): void
    {
        \App\Models\Setting::updateOrCreate(['key' => 'free_shipping_enabled'], ['value' => '1']);
        \App\Models\Setting::updateOrCreate(['key' => 'free_shipping_min_order'], ['value' => '0']);
        clear_settings_cache();

        $product = Product::first();
        $result = app(PromotionEngine::class)->applyToCart(
            [['product' => $product, 'qty' => 1]],
            $product->final_price,
        );

        $this->assertTrue($result['free_shipping']);
    }

    public function test_coupon_rejected_when_min_qty_not_met(): void
    {
        CartRule::create([
            'name' => 'Kupon 3 Item',
            'coupon_code' => 'QTY3',
            'discount_type' => 'percentage',
            'discount_amount' => 10,
            'min_qty' => 3,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        $product = Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $response = $this->post('/cart/coupon', [
            'coupon_code' => 'QTY3',
            'redirect' => 'cart',
        ]);

        $response->assertSessionHas('error');
        $this->assertNull(session(CartService::COUPON_SESSION_KEY));
    }

    public function test_coupon_rejected_when_min_order_amount_not_met(): void
    {
        CartRule::create([
            'name' => 'Kupon Min Belanja',
            'coupon_code' => 'BELI300',
            'discount_type' => 'percentage',
            'discount_amount' => 10,
            'min_order_amount' => 300_000,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        $product = Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $response = $this->post('/cart/coupon', [
            'coupon_code' => 'BELI300',
            'redirect' => 'cart',
        ]);

        $response->assertSessionHas('error');
        $this->assertNull(session(CartService::COUPON_SESSION_KEY));
    }

    public function test_stale_coupon_session_cleared_when_cart_no_longer_qualifies(): void
    {
        CartRule::create([
            'name' => 'Kupon 3 Item',
            'coupon_code' => 'STALE3',
            'discount_type' => 'free_shipping',
            'discount_amount' => 0,
            'min_qty' => 3,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        $product = Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 3]);
        session([CartService::COUPON_SESSION_KEY => 'STALE3']);

        $pricing = app(\App\Services\CartPricingService::class)->build();
        $this->assertTrue($pricing['free_shipping']);
        $this->assertSame('STALE3', session(CartService::COUPON_SESSION_KEY));

        $this->postJson('/cart/update', ['key' => array_key_first(app(\App\Services\CartService::class)->get()), 'qty' => 1]);

        $pricing = app(\App\Services\CartPricingService::class)->build();
        $this->assertNull(session(CartService::COUPON_SESSION_KEY));
        $this->assertNull($pricing['coupon_code']);
        $this->assertFalse($pricing['free_shipping']);
    }

    public function test_checkout_blocks_guest_when_email_exceeds_per_customer_limit(): void
    {
        $rule = CartRule::create([
            'name' => 'Kupon Checkout',
            'coupon_code' => 'CHECK1',
            'discount_type' => 'fixed',
            'discount_amount' => 5_000,
            'uses_per_customer' => 1,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        CartRuleUsage::create([
            'cart_rule_id' => $rule->id,
            'customer_email' => 'repeat@example.com',
            'times_used' => 1,
        ]);

        $product = Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);
        session([CartService::COUPON_SESSION_KEY => 'CHECK1']);

        $shipping = ShippingCost::first();
        $bank = PaymentBank::first();

        $response = $this->post('/checkout/process', array_merge([
            'customer_name' => 'Repeat Guest',
            'customer_phone' => '08123456789',
            'customer_email' => 'repeat@example.com',
            'shipping_address' => 'Jl. Test',
            'courier_code' => $shipping->courier_code ?? 'jne',
            'payment_method' => 'bank_'.$bank->id,
        ], $this->checkoutWilayahFields()));

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('orders', 0);
    }
}
