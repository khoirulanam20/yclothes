<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\TaxRate;
use App\Services\TaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_calculates_ppn_on_subtotal_when_tax_excluded(): void
    {
        TaxRate::firstOrCreate(
            ['name' => 'PPN Test'],
            ['rate' => 11, 'type' => 'percentage', 'is_active' => true],
        );

        $product = Product::first();
        $calculator = app(TaxCalculator::class);

        $result = $calculator->calculate([
            [
                'product' => $product,
                'qty' => 2,
                'subtotal' => 200_000,
            ],
        ]);

        $this->assertSame(22_000, $result['tax_amount']);
    }

    public function test_tax_included_does_not_inflate_display_tax(): void
    {
        \App\Models\Setting::updateOrCreate(['key' => 'tax_included'], ['value' => '1']);

        $product = Product::first();
        $calculator = app(TaxCalculator::class);

        $result = $calculator->calculate([
            [
                'product' => $product,
                'qty' => 1,
                'subtotal' => 111_000,
            ],
        ]);

        $this->assertGreaterThan(0, $result['tax_amount']);
        $grand = $calculator->grandTotalWithTax(111_000, $result['tax_amount'], 10_000, 0, true);
        $this->assertSame(121_000, $grand);
    }
}
