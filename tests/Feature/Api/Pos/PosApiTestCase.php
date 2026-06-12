<?php

namespace Tests\Feature\Api\Pos;

use App\Models\Inventory;
use App\Models\PaymentBank;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class PosApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $posUser;

    protected Warehouse $warehouse;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->posUser = User::query()->where('is_admin', true)->firstOrFail();
        $this->warehouse = Warehouse::query()->create([
            'name' => 'Outlet POS',
            'address' => 'Jl. Kasir 1',
            'city' => 'Jakarta',
            'is_active' => true,
        ]);
        $this->product = Product::query()->firstOrFail();
        $this->product->update([
            'track_stock' => true,
            'allow_backorder' => false,
        ]);

        Inventory::query()->updateOrCreate(
            [
                'product_id' => $this->product->id,
                'warehouse_id' => $this->warehouse->id,
                'product_variant_id' => null,
            ],
            ['stock' => 25, 'low_stock_threshold' => 5],
        );
    }

    /** @return array<string, string> */
    protected function posHeaders(?User $user = null): array
    {
        $user ??= $this->posUser;
        $token = $user->createToken('pos-test', ['pos'])->plainTextToken;

        return [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ];
    }

    protected function openPosShift(?Warehouse $warehouse = null): void
    {
        $warehouse ??= $this->warehouse;

        $this->withHeaders($this->posHeaders())
            ->postJson('/api/pos/shifts/open', [
                'warehouse_id' => $warehouse->id,
                'opening_cash' => 100000,
            ])
            ->assertCreated();
    }

    /**
     * @param  list<array{method: string, amount: int, payment_bank_id?: int}>  $payments
     */
    protected function createPosOrder(
        int $qty = 1,
        ?int $unitPrice = null,
        array $payments = [],
    ) {
        $this->openPosShift();

        $preview = $this->withHeaders($this->posHeaders())
            ->postJson('/api/pos/cart/preview', [
                'warehouse_id' => $this->warehouse->id,
                'items' => [
                    ['product_id' => $this->product->id, 'qty' => $qty],
                ],
            ])
            ->assertOk()
            ->json('data');

        $grandTotal = (int) $preview['grandTotal'];

        if ($payments === []) {
            $payments = [['method' => 'cash', 'amount' => $grandTotal]];
        }

        return $this->withHeaders($this->posHeaders())
            ->postJson('/api/pos/orders', [
                'warehouse_id' => $this->warehouse->id,
                'customer_name' => 'Walk-in',
                'customer_phone' => '081234567890',
                'items' => [
                    ['product_id' => $this->product->id, 'qty' => $qty],
                ],
                'payments' => $payments,
            ]);
    }

    protected function activeBank(): PaymentBank
    {
        return PaymentBank::query()->where('is_active', true)->firstOrFail();
    }
}
