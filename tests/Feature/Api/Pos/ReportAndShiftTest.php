<?php

namespace Tests\Feature\Api\Pos;

use App\Models\PosShift;
use Illuminate\Support\Facades\Hash;

class ReportAndShiftTest extends PosApiTestCase
{
    public function test_open_shift_accepts_opening_notes(): void
    {
        $response = $this->withHeaders($this->posHeaders())
            ->postJson('/api/pos/shifts/open', [
                'warehouse_id' => $this->warehouse->id,
                'opening_cash' => 150000,
                'opening_notes' => 'Shift pagi',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.shift.openingCash', 150000);

        $this->assertDatabaseHas('pos_shifts', [
            'user_id' => $this->posUser->id,
            'opening_notes' => 'Shift pagi',
        ]);
    }

    public function test_shift_history_returns_closed_shifts(): void
    {
        $this->createPosOrder()->assertCreated();

        $shift = PosShift::query()->where('user_id', $this->posUser->id)->firstOrFail();

        $this->withHeaders($this->posHeaders())
            ->postJson('/api/pos/shifts/close', [
                'closing_cash' => 200000,
                'notes' => 'Tutup shift',
            ])
            ->assertOk();

        $response = $this->withHeaders($this->posHeaders())
            ->getJson('/api/pos/shifts/history');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $shift->id)
            ->assertJsonPath('data.0.notes', 'Tutup shift')
            ->assertJsonStructure(['data' => [['totalSales', 'paymentsByMethod']]]);
    }

    public function test_reports_summary_returns_aggregates(): void
    {
        $this->createPosOrder()->assertCreated();

        $today = now()->toDateString();

        $response = $this->withHeaders($this->posHeaders())
            ->getJson('/api/pos/reports/summary?from='.$today.'&to='.$today);

        $response->assertOk()
            ->assertJsonPath('data.orders', 1)
            ->assertJsonStructure([
                'data' => [
                    'orders',
                    'averageOrderValue',
                    'cashPayments',
                    'transferPayments',
                    'sparkline',
                ],
            ]);
    }

    public function test_reports_export_returns_summary_and_order_list(): void
    {
        $this->createPosOrder()->assertCreated();

        $today = now()->toDateString();

        $response = $this->withHeaders($this->posHeaders())
            ->getJson('/api/pos/reports/export?from='.$today.'&to='.$today);

        $response->assertOk()
            ->assertJsonPath('data.orders', 1)
            ->assertJsonPath('data.from', $today)
            ->assertJsonPath('data.to', $today)
            ->assertJsonCount(1, 'data.orderList')
            ->assertJsonStructure([
                'data' => [
                    'orders',
                    'averageOrderValue',
                    'cashPayments',
                    'transferPayments',
                    'sparkline',
                    'from',
                    'to',
                    'orderList' => [['orderNumber', 'grandTotal', 'createdAt']],
                ],
            ]);
    }

    public function test_user_can_update_profile(): void
    {
        $response = $this->withHeaders($this->posHeaders())
            ->patchJson('/api/pos/me', [
                'name' => 'Kasir POS',
                'email' => 'kasir@yclothes.test',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.user.name', 'Kasir POS')
            ->assertJsonPath('data.user.email', 'kasir@yclothes.test');
    }

    public function test_user_can_update_password_with_current_password(): void
    {
        $this->posUser->update(['password' => Hash::make('admin123')]);

        $this->withHeaders($this->posHeaders())
            ->patchJson('/api/pos/me', [
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
                'current_password' => 'admin123',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('newpassword123', $this->posUser->fresh()->password));
    }
}
