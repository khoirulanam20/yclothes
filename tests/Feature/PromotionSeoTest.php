<?php

namespace Tests\Feature;

use App\Models\CartRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_promotion_landing_page_with_meta_tags(): void
    {
        CartRule::create([
            'name' => 'Promo Natal',
            'description' => 'Diskon natal spesial',
            'slug' => 'promo-natal',
            'meta_title' => 'Promo Natal 2026',
            'meta_description' => 'Hemat besar di natal',
            'discount_type' => 'percentage',
            'discount_amount' => 20,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        $this->get('/promo/promo-natal')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Promotions/Show')
                ->where('promotion.metaTitle', 'Promo Natal 2026')
                ->where('promotion.name', 'Promo Natal')
            );
    }

    public function test_inactive_promotion_returns_404(): void
    {
        CartRule::create([
            'name' => 'Expired Promo',
            'slug' => 'expired-promo',
            'discount_type' => 'fixed',
            'discount_amount' => 10000,
            'start_date' => now()->subMonth(),
            'end_date' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->get('/promo/expired-promo')->assertNotFound();
    }
}
