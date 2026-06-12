<?php

namespace Tests;

use App\Models\ShippingCost;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        clear_settings_cache();
    }
    /**
     * @return array<string, string>
     */
    protected function checkoutWilayahFields(): array
    {
        return [
            'province_code' => '33',
            'province_name' => 'Jawa Tengah',
            'regency_code' => '33.73',
            'regency_name' => 'Temanggung',
            'district_code' => '33.73.10',
            'district_name' => 'Temanggung',
            'village_code' => '33.73.10.1001',
            'village_name' => 'Banyuurip',
            'postal_code' => '56211',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function checkoutCourierField(?ShippingCost $shipping = null): array
    {
        $cost = $shipping ?? ShippingCost::query()
            ->where('regency_code', '33.73')
            ->where('courier_code', 'jne')
            ->first() ?? ShippingCost::first();

        return [
            'courier_code' => $cost?->courier_code ?? 'jne',
        ];
    }
}
