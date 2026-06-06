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

        $unitPrice = app(PromotionEngine::class)->getUnitPrice($product->fresh());
        $this->assertSame(72000, $unitPrice);
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
