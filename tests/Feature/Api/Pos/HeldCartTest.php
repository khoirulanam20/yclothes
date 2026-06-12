<?php

namespace Tests\Feature\Api\Pos;

class HeldCartTest extends PosApiTestCase
{
    public function test_can_hold_and_resume_cart(): void
    {
        $this->openPosShift();

        $create = $this->withHeaders($this->posHeaders())
            ->postJson('/api/pos/held-carts', [
                'warehouse_id' => $this->warehouse->id,
                'label' => 'Pelanggan A',
                'items' => [
                    ['product_id' => $this->product->id, 'qty' => 2],
                ],
            ]);

        $create->assertCreated()->assertJsonPath('data.label', 'Pelanggan A');
        $heldId = $create->json('data.id');

        $this->withHeaders($this->posHeaders())
            ->getJson('/api/pos/held-carts?warehouse_id='.$this->warehouse->id)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $resume = $this->withHeaders($this->posHeaders())
            ->postJson("/api/pos/held-carts/{$heldId}/resume");

        $resume->assertOk()
            ->assertJsonPath('data.items.0.qty', 2);

        $this->withHeaders($this->posHeaders())
            ->getJson('/api/pos/held-carts')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
