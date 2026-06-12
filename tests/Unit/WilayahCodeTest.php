<?php

namespace Tests\Unit;

use App\Support\WilayahCode;
use PHPUnit\Framework\TestCase;

class WilayahCodeTest extends TestCase
{
    public function test_normalize_regency_code(): void
    {
        $this->assertSame('33.73', WilayahCode::normalize('3373'));
    }

    public function test_normalize_province_code(): void
    {
        $this->assertSame('33', WilayahCode::normalize('33'));
    }

    public function test_equals_with_different_formats(): void
    {
        $this->assertTrue(WilayahCode::equals('3373', '33.73'));
        $this->assertFalse(WilayahCode::equals('3374', '33.73'));
    }
}
