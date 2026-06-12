<?php

namespace Tests\Feature\Api\Pos;

use App\Models\Inventory;
use App\Models\Warehouse;

class WarehouseStockTest extends PosApiTestCase
{
    public function test_stock_is_decremented_only_from_selected_warehouse(): void
    {
        $otherWarehouse = Warehouse::query()->create([
            'name' => 'Outlet Lain',
            'city' => 'Bandung',
            'is_active' => true,
        ]);

        Inventory::query()->updateOrCreate(
            [
                'product_id' => $this->product->id,
                'warehouse_id' => $otherWarehouse->id,
                'product_variant_id' => null,
            ],
            ['stock' => 40, 'low_stock_threshold' => 5],
        );

        $this->createPosOrder(3)->assertCreated();

        $this->assertSame(
            22,
            Inventory::query()
                ->where('product_id', $this->product->id)
                ->where('warehouse_id', $this->warehouse->id)
                ->value('stock'),
        );

        $this->assertSame(
            40,
            Inventory::query()
                ->where('product_id', $this->product->id)
                ->where('warehouse_id', $otherWarehouse->id)
                ->value('stock'),
        );
    }

    public function test_void_order_restores_warehouse_stock(): void
    {
        $response = $this->createPosOrder(2);
        $orderId = $response->json('data.id');

        $this->withHeaders($this->posHeaders())
            ->postJson("/api/pos/orders/{$orderId}/void", ['note' => 'Salah input'])
            ->assertOk()
            ->assertJsonPath('data.orderStatus', 'cancelled');

        $this->assertSame(
            25,
            Inventory::query()
                ->where('product_id', $this->product->id)
                ->where('warehouse_id', $this->warehouse->id)
                ->value('stock'),
        );
    }
}
