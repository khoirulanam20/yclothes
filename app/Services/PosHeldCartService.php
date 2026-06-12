<?php

namespace App\Services;

use App\Models\PosHeldCart;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PosHeldCartService
{
    public function __construct(private PosShiftService $shiftService) {}

    /** @return Collection<int, PosHeldCart> */
    public function listForUser(User $user, ?int $warehouseId = null): Collection
    {
        return PosHeldCart::query()
            ->with(['warehouse', 'customer'])
            ->where('user_id', $user->id)
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->orderByDesc('held_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function hold(User $user, array $payload): PosHeldCart
    {
        $warehouse = $this->resolveWarehouse((int) $payload['warehouse_id']);
        $shift = $this->shiftService->currentOpenShift($user);

        if ($shift && $shift->warehouse_id !== $warehouse->id) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Shift aktif berada di gudang lain.',
            ]);
        }

        return PosHeldCart::create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'pos_shift_id' => $shift?->id,
            'label' => $payload['label'] ?? null,
            'customer_name' => $payload['customer_name'] ?? null,
            'customer_phone' => $payload['customer_phone'] ?? null,
            'customer_id' => $payload['customer_id'] ?? null,
            'items' => $payload['items'],
            'coupon_code' => $payload['coupon_code'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'held_at' => now(),
        ])->load(['warehouse', 'customer']);
    }

    /**
     * @return array<string, mixed>
     */
    public function resume(PosHeldCart $heldCart, User $user): array
    {
        $this->authorizeHeldCart($heldCart, $user);
        $payload = $this->serialize($heldCart);
        $heldCart->delete();

        return $payload;
    }

    public function discard(PosHeldCart $heldCart, User $user): void
    {
        $this->authorizeHeldCart($heldCart, $user);
        $heldCart->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(PosHeldCart $heldCart): array
    {
        return [
            'id' => $heldCart->id,
            'label' => $heldCart->label,
            'warehouseId' => $heldCart->warehouse_id,
            'warehouseName' => $heldCart->warehouse?->name,
            'customerId' => $heldCart->customer_id,
            'customerName' => $heldCart->customer_name,
            'customerPhone' => $heldCart->customer_phone,
            'items' => $heldCart->items,
            'couponCode' => $heldCart->coupon_code,
            'notes' => $heldCart->notes,
            'heldAt' => $heldCart->held_at?->toIso8601String(),
        ];
    }

    private function resolveWarehouse(int $warehouseId): Warehouse
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

        return $warehouse;
    }

    private function authorizeHeldCart(PosHeldCart $heldCart, User $user): void
    {
        if ($heldCart->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'held_cart' => 'Hold tidak ditemukan.',
            ]);
        }
    }
}
