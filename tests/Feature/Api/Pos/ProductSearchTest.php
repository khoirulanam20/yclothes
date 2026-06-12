<?php

namespace Tests\Feature\Api\Pos;

use App\Enums\ProductType;
use App\Models\ProductVariant;

class ProductSearchTest extends PosApiTestCase
{
    public function test_can_search_products_with_warehouse_stock(): void
    {
        $response = $this->withHeaders($this->posHeaders())
            ->getJson('/api/pos/products?warehouse_id='.$this->warehouse->id.'&q='.$this->product->name);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'sku', 'finalPrice', 'stock']],
                'meta' => ['currentPage', 'total'],
            ]);
    }

    public function test_can_lookup_product_by_sku(): void
    {
        $this->product->update(['sku' => 'POS-SKU-001']);

        $this->withHeaders($this->posHeaders())
            ->getJson('/api/pos/products/by-sku/POS-SKU-001?warehouse_id='.$this->warehouse->id)
            ->assertOk()
            ->assertJsonPath('data.matchType', 'product')
            ->assertJsonPath('data.product.sku', 'POS-SKU-001');
    }

    public function test_can_lookup_variant_by_sku(): void
    {
        $this->product->update(['type' => ProductType::Configurable]);

        ProductVariant::query()->create([
            'parent_product_id' => $this->product->id,
            'sku' => 'POS-VAR-001',
            'name' => 'M / Hitam',
            'attributes' => ['size' => 'M', 'color' => 'Hitam'],
            'is_active' => true,
        ]);

        $this->withHeaders($this->posHeaders())
            ->getJson('/api/pos/products/by-sku/POS-VAR-001')
            ->assertOk()
            ->assertJsonPath('data.matchType', 'variant')
            ->assertJsonPath('data.variant.sku', 'POS-VAR-001');
    }
}
