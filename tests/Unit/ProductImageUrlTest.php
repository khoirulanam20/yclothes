<?php

namespace Tests\Unit;

use App\Models\Product;
use Tests\TestCase;

class ProductImageUrlTest extends TestCase
{
    public function test_image_url_falls_back_to_first_gallery_image(): void
    {
        $product = new Product([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'image' => '',
            'images' => ['products/gallery/first.jpg', 'products/gallery/second.jpg'],
        ]);

        $this->assertStringContainsString('products/gallery/first.jpg', $product->image_url);
    }

    public function test_image_url_prefers_primary_image_over_gallery(): void
    {
        $product = new Product([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'image' => 'products/main.jpg',
            'images' => ['products/gallery/first.jpg'],
        ]);

        $this->assertStringContainsString('products/main.jpg', $product->image_url);
    }
}
