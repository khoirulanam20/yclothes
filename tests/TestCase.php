<?php

namespace Tests;

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
            'regency_code' => '3373',
            'regency_name' => 'Temanggung',
            'district_code' => '3373010',
            'district_name' => 'Temanggung',
            'village_code' => '3373010001',
            'village_name' => 'Banyuurip',
            'postal_code' => '56211',
        ];
    }
}
