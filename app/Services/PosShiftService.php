<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PosOrderPayment;
use App\Models\PosShift;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Validation\ValidationException;

class PosShiftService
{
    public function currentOpenShift(User $user): ?PosShift
    {
        return PosShift::query()
            ->with('warehouse')
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    public function openShift(User $user, int $warehouseId, int $openingCash = 0, ?string $openingNotes = null): PosShift
    {
        $warehouse = Warehouse::query()
            ->where('id', $warehouseId)
            ->where('is_active', true)
            ->first();

        if (! $warehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Gudang tidak ditemukan atau tidak aktif.',
            ]);
        }

        $existing = $this->currentOpenShift($user);
        if ($existing) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Anda masih memiliki shift aktif. Tutup shift terlebih dahulu.',
            ]);
        }

        return PosShift::create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'opened_at' => now(),
            'opening_cash' => max(0, $openingCash),
            'opening_notes' => $openingNotes,
            'status' => 'open',
        ])->load('warehouse');
    }

    /**
     * @param  array{closing_cash?: int|null, notes?: string|null}  $payload
     */
    public function closeShift(User $user, array $payload): PosShift
    {
        $shift = $this->currentOpenShift($user);

        if (! $shift) {
            throw ValidationException::withMessages([
                'shift' => 'Tidak ada shift aktif.',
            ]);
        }

        $shift->update([
            'closed_at' => now(),
            'closing_cash' => isset($payload['closing_cash']) ? max(0, (int) $payload['closing_cash']) : null,
            'notes' => $payload['notes'] ?? null,
            'status' => 'closed',
        ]);

        return $shift->fresh(['warehouse']);
    }

    public function requireOpenShiftForWarehouse(User $user, int $warehouseId): PosShift
    {
        $shift = $this->currentOpenShift($user);

        if (! $shift || $shift->warehouse_id !== $warehouseId) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Buka shift di gudang ini sebelum membuat transaksi.',
            ]);
        }

        return $shift;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(PosShift $shift): array
    {
        $orders = Order::query()
            ->where('pos_shift_id', $shift->id)
            ->where('order_source', 'pos')
            ->where('order_status', '!=', 'cancelled')
            ->get(['id', 'grand_total', 'payment_method']);

        $payments = PosOrderPayment::query()
            ->whereIn('order_id', $orders->pluck('id'))
            ->get();

        $byMethod = $payments
            ->groupBy('method')
            ->map(fn ($rows) => (int) $rows->sum('amount'))
            ->all();

        return [
            'shift' => [
                'id' => $shift->id,
                'status' => $shift->status,
                'warehouseId' => $shift->warehouse_id,
                'warehouseName' => $shift->warehouse?->name,
                'openedAt' => $shift->opened_at?->toIso8601String(),
                'closedAt' => $shift->closed_at?->toIso8601String(),
                'openingCash' => (int) $shift->opening_cash,
                'openingNotes' => $shift->opening_notes,
                'closingCash' => $shift->closing_cash !== null ? (int) $shift->closing_cash : null,
                'notes' => $shift->notes,
            ],
            'orderCount' => $orders->count(),
            'totalSales' => (int) $orders->sum('grand_total'),
            'paymentsByMethod' => $byMethod,
        ];
    }

    public function serializeShift(?PosShift $shift): ?array
    {
        if (! $shift) {
            return null;
        }

        return [
            'id' => $shift->id,
            'warehouseId' => $shift->warehouse_id,
            'warehouseName' => $shift->warehouse?->name,
            'openedAt' => $shift->opened_at?->toIso8601String(),
            'openingCash' => (int) $shift->opening_cash,
            'openingNotes' => $shift->opening_notes,
            'status' => $shift->status,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function history(User $user, int $limit = 50): array
    {
        return PosShift::query()
            ->where('user_id', $user->id)
            ->where('status', 'closed')
            ->latest('closed_at')
            ->limit($limit)
            ->get()
            ->map(function (PosShift $shift) {
                $summary = $this->summary($shift->load('warehouse'));

                return [
                    'id' => $shift->id,
                    'openedAt' => $shift->opened_at?->toIso8601String(),
                    'closedAt' => $shift->closed_at?->toIso8601String(),
                    'openingCash' => (int) $shift->opening_cash,
                    'totalSales' => $summary['totalSales'],
                    'orderCount' => $summary['orderCount'],
                    'notes' => $shift->notes,
                    'openingNotes' => $shift->opening_notes,
                    'paymentsByMethod' => $summary['paymentsByMethod'],
                ];
            })
            ->values()
            ->all();
    }
}
