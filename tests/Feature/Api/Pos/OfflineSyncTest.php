<?php

namespace Tests\Feature\Api\Pos;

use App\Models\Inventory;
use App\Models\Order;
use Illuminate\Support\Str;

class OfflineSyncTest extends PosApiTestCase
{
    public function test_can_sync_offline_order_batch(): void
    {
        $this->product->update(['track_stock' => true, 'allow_backorder' => false]);
        Inventory::query()->updateOrCreate(
            [
                'product_id' => $this->product->id,
                'warehouse_id' => $this->warehouse->id,
                'product_variant_id' => null,
            ],
            ['stock' => 10, 'low_stock_threshold' => 2],
        );

        $preview = $this->withHeaders($this->posHeaders())
            ->postJson('/api/pos/cart/preview', [
                'warehouse_id' => $this->warehouse->id,
                'items' => [['product_id' => $this->product->id, 'qty' => 1]],
            ])
            ->json('data');

        $clientRef = (string) Str::uuid();

        $response = $this->withHeaders($this->posHeaders())
            ->postJson('/api/pos/orders/sync', [
                'orders' => [[
                    'client_reference' => $clientRef,
                    'warehouse_id' => $this->warehouse->id,
                    'customer_name' => 'Offline Walk-in',
                    'items' => [['product_id' => $this->product->id, 'qty' => 1]],
                    'payments' => [['method' => 'cash', 'amount' => $preview['grandTotal']]],
                ]],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.results.0.status', 'created');

        $order = Order::query()->where('client_reference', $clientRef)->first();
        $this->assertNotNull($order);
        $this->assertTrue($order->synced_from_offline);
    }

    public function test_duplicate_client_reference_returns_duplicate_status(): void
    {
        $clientRef = (string) Str::uuid();

        $preview = $this->withHeaders($this->posHeaders())
            ->postJson('/api/pos/cart/preview', [
                'warehouse_id' => $this->warehouse->id,
                'items' => [['product_id' => $this->product->id, 'qty' => 1]],
            ])
            ->json('data');

        $payload = [
            'orders' => [[
                'client_reference' => $clientRef,
                'warehouse_id' => $this->warehouse->id,
                'items' => [['product_id' => $this->product->id, 'qty' => 1]],
                'payments' => [['method' => 'cash', 'amount' => $preview['grandTotal']]],
            ]],
        ];

        $this->withHeaders($this->posHeaders())->postJson('/api/pos/orders/sync', $payload)->assertOk();

        $second = $this->withHeaders($this->posHeaders())
            ->postJson('/api/pos/orders/sync', $payload);

        $second->assertOk()->assertJsonPath('data.results.0.status', 'duplicate');
    }
}
