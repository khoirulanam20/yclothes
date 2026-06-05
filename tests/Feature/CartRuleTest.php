<?php

namespace Tests\Feature;

use App\Models\CartRule;
use App\Models\Product;
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

        $response = $this->post('/cart/coupon', ['coupon_code' => 'HEMAT50']);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('HEMAT50', session(CartService::COUPON_SESSION_KEY));
    }
}
