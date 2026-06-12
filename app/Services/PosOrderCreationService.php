<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentBank;
use App\Models\PosOrderPayment;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosOrderCreationService
{
    public function __construct(
        private PosShiftService $shiftService,
        private PosPricingService $pricingService,
        private InventoryService $inventoryService,
        private PromotionEngine $promotionEngine,
        private OrderWorkflowService $orderWorkflow,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $user): Order
    {
        $warehouse = Warehouse::query()
            ->where('id', $validated['warehouse_id'])
            ->where('is_active', true)
            ->first();

        if (! $warehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Gudang tidak ditemukan atau tidak aktif.',
            ]);
        }

        $shift = $this->shiftService->requireOpenShiftForWarehouse($user, $warehouse->id);

        $customer = null;
        if (! empty($validated['customer_id'])) {
            $customer = Customer::query()->find($validated['customer_id']);
            if (! $customer) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Pelanggan tidak ditemukan.',
                ]);
            }
        }

        $customerEmail = $customer?->email ?? ($validated['customer_email'] ?? 'pos@walk-in.local');
        $pricing = $this->pricingService->build(
            $validated['items'],
            $warehouse->id,
            $validated['coupon_code'] ?? null,
            $customer?->id,
            $customerEmail,
        );

        $payments = $validated['payments'];
        $paymentTotal = array_sum(array_column($payments, 'amount'));

        if ($paymentTotal !== $pricing['grand_total']) {
            throw ValidationException::withMessages([
                'payments' => 'Total pembayaran harus sama dengan grand total.',
            ]);
        }

        $paymentMethod = $this->resolvePaymentMethod($payments);
        $this->validatePayments($payments);

        $orderItems = $this->buildOrderItems($pricing);

        try {
            $order = DB::transaction(function () use (
                $validated,
                $user,
                $warehouse,
                $shift,
                $customer,
                $customerEmail,
                $pricing,
                $payments,
                $paymentMethod,
                $orderItems,
            ) {
                $this->inventoryService->assertStockAvailableWithLock(
                    $pricing['line_items'],
                    $warehouse->id,
                );

                $order = Order::createTrusted([
                    'order_number' => generate_order_number(),
                    'order_source' => 'pos',
                    'warehouse_id' => $warehouse->id,
                    'pos_shift_id' => $shift->id,
                    'created_by_user_id' => $user->id,
                    'customer_id' => $customer?->id,
                    'customer_name' => $validated['customer_name'] ?? $customer?->name ?? 'Walk-in',
                    'customer_phone' => $validated['customer_phone'] ?? $customer?->phone ?? '-',
                    'customer_email' => $customerEmail,
                    'shipping_address' => trim('Penjualan Toko — '.$warehouse->name.($warehouse->address ? ', '.$warehouse->address : '')),
                    'shipping_city' => $warehouse->city ?? 'Toko',
                    'shipping_cost' => 0,
                    'shipping_method' => 'pos_pickup',
                    'shipping_provider' => 'pos',
                    'total_price' => $pricing['subtotal'],
                    'tax_amount' => $pricing['tax_amount'],
                    'discount_amount' => $pricing['discount_amount'],
                    'coupon_code' => $pricing['coupon_code'],
                    'grand_total' => $pricing['grand_total'],
                    'payment_method' => $paymentMethod,
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'payment_confirmation_status' => 'approved',
                    'order_status' => 'confirmed',
                    'notes' => $validated['notes'] ?? null,
                ]);

                $order->items()->createMany($orderItems);

                foreach ($payments as $payment) {
                    PosOrderPayment::create([
                        'order_id' => $order->id,
                        'method' => $payment['method'],
                        'amount' => (int) $payment['amount'],
                        'payment_bank_id' => $payment['payment_bank_id'] ?? null,
                        'reference' => $payment['reference'] ?? null,
                    ]);
                }

                $this->inventoryService->reserveForOrder($order->fresh(), 'Penjualan POS');

                $this->orderWorkflow->recordInitialStatus($order->fresh());

                $this->orderWorkflow->transition(
                    $order->fresh(),
                    'completed',
                    'Transaksi POS selesai',
                    'admin',
                    $user->id,
                    false,
                );

                $this->promotionEngine->recordCouponUsage(
                    $pricing['cart_rule'],
                    $customer?->id,
                    $customerEmail,
                );

                $this->logActivity($user, 'pos.order.create', $order);

                return $order->fresh(['items', 'posPayments', 'warehouse', 'createdByUser']);
            });
        } catch (InsufficientStockException $e) {
            throw ValidationException::withMessages([
                'items' => $e->getMessage(),
            ]);
        }

        return $order;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createFromOfflineSync(
        User $user,
        Warehouse $warehouse,
        ?int $shiftId,
        array $payload,
    ): Order {
        $pricing = $this->pricingService->build(
            $payload['items'],
            $warehouse->id,
            null,
            $payload['customer_id'] ?? null,
            $payload['customer_email'] ?? 'pos@walk-in.local',
        );

        $payments = $payload['payments'];
        $paymentTotal = array_sum(array_column($payments, 'amount'));

        if ($paymentTotal !== $pricing['grand_total']) {
            throw ValidationException::withMessages([
                'payments' => 'Total pembayaran tidak sesuai grand total.',
            ]);
        }

        $paymentMethod = $this->resolvePaymentMethod($payments);
        $this->validatePayments($payments);
        $orderItems = $this->buildOrderItems($pricing);

        $order = DB::transaction(function () use (
            $user,
            $warehouse,
            $shiftId,
            $payload,
            $pricing,
            $payments,
            $paymentMethod,
            $orderItems,
        ) {
            $this->inventoryService->assertStockAvailableWithLock(
                $pricing['line_items'],
                $warehouse->id,
            );

            $createdAt = ! empty($payload['created_at'])
                ? Carbon::parse($payload['created_at'])
                : now();

            $order = Order::createTrusted([
                'order_number' => generate_order_number(),
                'order_source' => 'pos',
                'client_reference' => $payload['client_reference'],
                'synced_from_offline' => true,
                'warehouse_id' => $warehouse->id,
                'pos_shift_id' => $shiftId,
                'created_by_user_id' => $user->id,
                'customer_id' => $payload['customer_id'] ?? null,
                'customer_name' => $payload['customer_name'] ?? 'Walk-in',
                'customer_phone' => $payload['customer_phone'] ?? '-',
                'customer_email' => $payload['customer_email'] ?? 'pos@walk-in.local',
                'shipping_address' => trim('Penjualan Toko (Offline) — '.$warehouse->name),
                'shipping_city' => $warehouse->city ?? 'Toko',
                'shipping_cost' => 0,
                'shipping_method' => 'pos_pickup',
                'shipping_provider' => 'pos',
                'total_price' => $pricing['subtotal'],
                'tax_amount' => $pricing['tax_amount'],
                'discount_amount' => $pricing['discount_amount'],
                'grand_total' => $pricing['grand_total'],
                'payment_method' => $paymentMethod,
                'payment_status' => 'paid',
                'paid_at' => $createdAt,
                'payment_confirmation_status' => 'approved',
                'order_status' => 'confirmed',
                'notes' => $payload['notes'] ?? null,
                'created_at' => $createdAt,
                'updated_at' => now(),
            ]);

            $order->items()->createMany($orderItems);

            foreach ($payments as $payment) {
                PosOrderPayment::create([
                    'order_id' => $order->id,
                    'method' => $payment['method'],
                    'amount' => (int) $payment['amount'],
                    'payment_bank_id' => $payment['payment_bank_id'] ?? null,
                    'reference' => $payment['reference'] ?? null,
                ]);
            }

            $this->inventoryService->reserveForOrder($order->fresh(), 'Sinkronisasi POS offline');
            $this->orderWorkflow->recordInitialStatus($order->fresh());
            $this->orderWorkflow->transition(
                $order->fresh(),
                'completed',
                'Sinkronisasi transaksi offline',
                'admin',
                $user->id,
                false,
            );

            $this->logActivity($user, 'pos.order.sync', $order);

            return $order->fresh(['items', 'posPayments', 'warehouse', 'createdByUser']);
        });

        return $order;
    }

    public function void(Order $order, User $user, ?string $note = null): Order
    {
        if (! $order->isPos()) {
            throw ValidationException::withMessages([
                'order' => 'Hanya pesanan POS yang dapat dibatalkan.',
            ]);
        }

        if ($order->order_status === 'cancelled') {
            throw ValidationException::withMessages([
                'order' => 'Pesanan sudah dibatalkan.',
            ]);
        }

        $this->orderWorkflow->transition(
            $order,
            'cancelled',
            $note ?? 'Transaksi POS dibatalkan',
            'admin',
            $user->id,
            false,
        );

        $this->logActivity($user, 'pos.order.void', $order->fresh());

        return $order->fresh(['items', 'posPayments', 'warehouse', 'createdByUser']);
    }

    /**
     * @param  list<array{method: string, amount: int, payment_bank_id?: int|null, reference?: string|null}>  $payments
     */
    private function resolvePaymentMethod(array $payments): string
    {
        if (count($payments) > 1) {
            return 'pos_split';
        }

        return match ($payments[0]['method']) {
            'cash' => 'pos_cash',
            'transfer' => 'pos_transfer',
            default => 'pos_split',
        };
    }

    /**
     * @param  list<array{method: string, amount: int, payment_bank_id?: int|null, reference?: string|null}>  $payments
     */
    private function validatePayments(array $payments): void
    {
        foreach ($payments as $index => $payment) {
            if (! in_array($payment['method'], ['cash', 'transfer'], true)) {
                throw ValidationException::withMessages([
                    "payments.{$index}.method" => 'Metode pembayaran tidak valid.',
                ]);
            }

            if ($payment['method'] === 'transfer') {
                $bankId = $payment['payment_bank_id'] ?? null;
                if (! $bankId || ! PaymentBank::query()->where('id', $bankId)->where('is_active', true)->exists()) {
                    throw ValidationException::withMessages([
                        "payments.{$index}.payment_bank_id" => 'Rekening bank tidak valid.',
                    ]);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $pricing
     * @return list<array<string, mixed>>
     */
    private function buildOrderItems(array $pricing): array
    {
        $items = [];

        foreach ($pricing['items'] as $row) {
            $product = $row['product'];
            $items[] = [
                'product_id' => $product->id,
                'product_variant_id' => $row['variant']?->id,
                'sku' => $row['sku'],
                'product_name' => $row['product_name'] ?? $product->name,
                'product_price' => $row['unit_price'],
                'qty' => $row['qty'],
                'subtotal' => $row['subtotal'],
                'size' => $row['size'],
                'color' => $row['color'],
            ];
        }

        return $items;
    }

    private function logActivity(User $user, string $action, Order $order): void
    {
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'properties' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'grand_total' => $order->grand_total,
            ],
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
