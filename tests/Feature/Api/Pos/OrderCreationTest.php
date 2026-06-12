<?php

namespace Tests\Feature\Api\Pos;

use App\Models\Inventory;
use App\Models\Order;

class OrderCreationTest extends PosApiTestCase
{
    public function test_can_create_pos_order_after_opening_shift(): void
    {
        $response = $this->createPosOrder(2);

        $response->assertCreated()
            ->assertJsonPath('data.orderStatus', 'completed')
            ->assertJsonPath('data.paymentStatus', 'paid');

        $order = Order::query()->where('order_number', $response->json('data.orderNumber'))->first();
        $this->assertNotNull($order);
        $this->assertSame('pos', $order->order_source);
        $this->assertSame($this->warehouse->id, $order->warehouse_id);
        $this->assertTrue($order->inventory_decremented);

        $inventory = Inventory::query()
            ->where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->whereNull('product_variant_id')
            ->first();

        $this->assertSame(23, $inventory->stock);
    }

    public function test_can_create_order_with_line_discount_percent(): void
    {
        $this->openPosShift();

        $preview = $this->withHeaders($this->posHeaders())
            ->postJson('/api/pos/cart/preview', [
                'warehouse_id' => $this->warehouse->id,
                'items' => [
                    ['product_id' => $this->product->id, 'qty' => 2, 'discount_percent' => 50],
                ],
            ])
            ->assertOk()
            ->json('data');

        $this->assertGreaterThan(0, $preview['discountAmount']);

        $this->withHeaders($this->posHeaders())
            ->postJson('/api/pos/orders', [
                'warehouse_id' => $this->warehouse->id,
                'items' => [
                    ['product_id' => $this->product->id, 'qty' => 2, 'discount_percent' => 50],
                ],
                'payments' => [
                    ['method' => 'cash', 'amount' => $preview['grandTotal']],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.grandTotal', $preview['grandTotal']);
    }

    public function test_cannot_create_order_without_open_shift(): void
    {
        $preview = $this->withHeaders($this->posHeaders())
            ->postJson('/api/pos/cart/preview', [
                'warehouse_id' => $this->warehouse->id,
                'items' => [
                    ['product_id' => $this->product->id, 'qty' => 1],
                ],
            ])
            ->json('data');

        $this->withHeaders($this->posHeaders())
            ->postJson('/api/pos/orders', [
                'warehouse_id' => $this->warehouse->id,
                'items' => [
                    ['product_id' => $this->product->id, 'qty' => 1],
                ],
                'payments' => [
                    ['method' => 'cash', 'amount' => $preview['grandTotal']],
                ],
            ])
            ->assertUnprocessable();
    }
}
