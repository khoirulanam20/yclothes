<?php

namespace Tests\Feature;

use App\Models\CartRule;
use App\Models\PaymentBank;
use App\Models\Product;
use App\Models\ShippingCost;
use App\Models\TaxRate;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionCheckoutIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_checkout_saves_tax_and_discount_from_coupon(): void
    {
        TaxRate::firstOrCreate(
            ['name' => 'PPN'],
            ['rate' => 11, 'type' => 'percentage', 'is_active' => true],
        );

        CartRule::create([
            'name' => 'Checkout diskon',
            'coupon_code' => 'CHECKOUT10',
            'discount_type' => 'percentage',
            'discount_amount' => 10,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        $product = Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);
        session([CartService::COUPON_SESSION_KEY => 'CHECKOUT10']);

        $shipping = ShippingCost::first();
        $bank = PaymentBank::first();

        $response = $this->post('/checkout/process', array_merge([
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'test@example.com',
            'shipping_address' => 'Jl. Test',
            'shipping_city' => $shipping->id,
            'payment_method' => 'bank_'.$bank->id,
        ], $this->checkoutWilayahFields()));

        $response->assertRedirect();

        $order = \App\Models\Order::where('customer_email', 'test@example.com')->latest()->first();
        $this->assertNotNull($order);
        $this->assertGreaterThan(0, $order->tax_amount);
        $this->assertGreaterThan(0, $order->discount_amount);
        $this->assertSame('CHECKOUT10', $order->coupon_code);
    }
}
