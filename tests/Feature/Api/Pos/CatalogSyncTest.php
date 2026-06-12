<?php

namespace Tests\Feature\Api\Pos;

class CatalogSyncTest extends PosApiTestCase
{
    public function test_can_sync_catalog_for_warehouse(): void
    {
        $response = $this->withHeaders($this->posHeaders())
            ->getJson('/api/pos/catalog/sync?warehouse_id='.$this->warehouse->id);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['products' => [['id', 'name', 'sku', 'finalPrice', 'stock', 'variants']]],
                'meta' => ['currentPage', 'total', 'syncedAt'],
            ]);
    }

    public function test_can_list_categories(): void
    {
        $this->withHeaders($this->posHeaders())
            ->getJson('/api/pos/categories')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }
}
