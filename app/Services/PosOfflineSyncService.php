<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Validation\ValidationException;

class PosOfflineSyncService
{
    private const MAX_BATCH = 50;

    public function __construct(
        private PosOrderCreationService $orderCreationService,
        private PosShiftService $shiftService,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $orders
     * @return list<array<string, mixed>>
     */
    public function syncBatch(User $user, array $orders): array
    {
        if (count($orders) > self::MAX_BATCH) {
            throw ValidationException::withMessages([
                'orders' => 'Maksimal '.self::MAX_BATCH.' pesanan per sinkronisasi.',
            ]);
        }

        $results = [];

        foreach ($orders as $index => $payload) {
            $results[] = $this->syncOne($user, $payload, $index);
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function syncOne(User $user, array $payload, int $index): array
    {
        $clientReference = (string) ($payload['client_reference'] ?? '');

        if ($clientReference === '') {
            return $this->failedResult($clientReference, 'client_reference wajib diisi.');
        }

        $existing = Order::query()->where('client_reference', $clientReference)->first();
        if ($existing) {
            return [
                'client_reference' => $clientReference,
                'status' => 'duplicate',
                'order_id' => $existing->id,
                'order_number' => $existing->order_number,
            ];
        }

        if (! empty($payload['coupon_code'])) {
            return $this->failedResult($clientReference, 'Kupon tidak didukung pada sinkronisasi offline.');
        }

        $warehouse = Warehouse::query()
            ->where('id', $payload['warehouse_id'] ?? 0)
            ->where('is_active', true)
            ->first();

        if (! $warehouse) {
            return $this->failedResult($clientReference, 'Gudang tidak valid.');
        }

        $shift = $this->shiftService->currentOpenShift($user);
        $shiftId = ($shift && $shift->warehouse_id === $warehouse->id) ? $shift->id : null;

        try {
            $order = $this->orderCreationService->createFromOfflineSync(
                $user,
                $warehouse,
                $shiftId,
                $payload,
            );

            return [
                'client_reference' => $clientReference,
                'status' => 'created',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ];
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Validasi gagal.';

            return $this->failedResult($clientReference, $message);
        } catch (InsufficientStockException $e) {
            return $this->failedResult($clientReference, $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return $this->failedResult($clientReference, 'Gagal menyinkronkan pesanan.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function failedResult(string $clientReference, string $error): array
    {
        return [
            'client_reference' => $clientReference,
            'status' => 'failed',
            'error' => $error,
        ];
    }
}
