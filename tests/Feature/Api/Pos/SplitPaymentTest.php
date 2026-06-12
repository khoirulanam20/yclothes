<?php

namespace Tests\Feature\Api\Pos;

use App\Models\Order;
use App\Models\PosOrderPayment;

class SplitPaymentTest extends PosApiTestCase
{
    public function test_can_create_order_with_split_payment(): void
    {
        $this->openPosShift();

        $preview = $this->withHeaders($this->posHeaders())
            ->postJson('/api/pos/cart/preview', [
                'warehouse_id' => $this->warehouse->id,
                'items' => [
                    ['product_id' => $this->product->id, 'qty' => 1],
                ],
            ])
            ->json('data');

        $grandTotal = (int) $preview['grandTotal'];
        $cashAmount = (int) floor($grandTotal / 2);
        $transferAmount = $grandTotal - $cashAmount;
        $bank = $this->activeBank();

        $response = $this->withHeaders($this->posHeaders())
            ->postJson('/api/pos/orders', [
                'warehouse_id' => $this->warehouse->id,
                'items' => [
                    ['product_id' => $this->product->id, 'qty' => 1],
                ],
                'payments' => [
                    ['method' => 'cash', 'amount' => $cashAmount],
                    [
                        'method' => 'transfer',
                        'amount' => $transferAmount,
                        'payment_bank_id' => $bank->id,
                        'reference' => 'TRX-123',
                    ],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.paymentMethod', 'pos_split')
            ->assertJsonCount(2, 'data.payments');

        $order = Order::query()->findOrFail($response->json('data.id'));
        $this->assertSame('pos_split', $order->payment_method);
        $this->assertSame(2, PosOrderPayment::query()->where('order_id', $order->id)->count());
    }

    public function test_rejects_payment_total_mismatch(): void
    {
        $this->openPosShift();

        $this->withHeaders($this->posHeaders())
            ->postJson('/api/pos/orders', [
                'warehouse_id' => $this->warehouse->id,
                'items' => [
                    ['product_id' => $this->product->id, 'qty' => 1],
                ],
                'payments' => [
                    ['method' => 'cash', 'amount' => 1000],
                ],
            ])
            ->assertUnprocessable();
    }
}
