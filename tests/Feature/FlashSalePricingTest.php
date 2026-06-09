<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\FlashSaleService;
use App\Services\HomepageLayoutService;
use App\Services\PromotionEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashSalePricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_flash_sale_applies_percentage_on_top_of_sale_price(): void
    {
        $product = Product::first();
        $product->update([
            'price' => 100000,
            'sale_price' => 80000,
            'sale_price_starts_at' => now()->subDay(),
            'sale_price_ends_at' => now()->addDay(),
        ]);

        $this->saveFlashLayout($product->id, 'percentage', 10);

        $flashPrice = app(FlashSaleService::class)->priceForProduct($product->fresh());
        $this->assertSame(72000, $flashPrice);

        $engine = app(PromotionEngine::class);
        $decorated = $engine->decorateProduct($product->fresh());

        $this->assertSame(72000, $engine->getUnitPrice($decorated));
        $this->assertSame(28, $decorated->getAttribute('display_discount_percentage'));
    }

    public function test_flash_sale_only_shows_total_discount_percentage(): void
    {
        $product = Product::first();
        $product->update(['price' => 100000, 'sale_price' => null]);

        $this->saveFlashLayout($product->id, 'percentage', 10);

        $decorated = app(PromotionEngine::class)->decorateProduct($product->fresh());

        $this->assertSame(90000, $decorated->getAttribute('catalog_unit_price'));
        $this->assertSame(10, $decorated->getAttribute('display_discount_percentage'));
    }

    public function test_no_promo_has_no_display_discount_percentage(): void
    {
        $product = Product::first();
        $product->update(['price' => 100000, 'sale_price' => null]);

        $decorated = app(PromotionEngine::class)->decorateProduct($product->fresh());

        $this->assertNull($decorated->getAttribute('display_discount_percentage'));
    }

    public function test_flash_sale_applies_fixed_discount(): void
    {
        $product = Product::first();
        $product->update(['price' => 100000, 'sale_price' => null]);

        $this->saveFlashLayout($product->id, 'fixed', 25000);

        $flashPrice = app(FlashSaleService::class)->priceForProduct($product->fresh());
        $this->assertSame(75000, $flashPrice);
    }

    private function saveFlashLayout(int $productId, string $discountType, float $discountAmount): void
    {
        app(HomepageLayoutService::class)->saveLayout([
            [
                'id' => 'flash-1',
                'type' => 'flash_sale',
                'enabled' => true,
                'props' => [
                    'title' => 'Flash Sale',
                    'endsAt' => now()->addDay()->format('Y-m-d\TH:i'),
                    'items' => [
                        ['productId' => $productId, 'discountType' => $discountType, 'discountAmount' => $discountAmount],
                    ],
                ],
            ],
        ]);
    }
}
