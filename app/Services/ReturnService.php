<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnPolicy;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\ReturnRequestMedia;
use App\Models\ReturnShipment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReturnService
{
    public function __construct(private OrderWorkflowService $orderWorkflow) {}

    public function canReturnItem(Order $order, OrderItem $item): bool
    {
        return $this->getReturnableQty($order, $item) > 0;
    }

    public function getReturnableQty(Order $order, OrderItem $item): int
    {
        if (! in_array($order->order_status, ['delivered', 'completed', 'return'], true)) {
            return 0;
        }

        $product = $item->product;
        if (! $product || ! $product->is_returnable) {
            return 0;
        }

        $windowDays = $product->return_window_days ?? ReturnPolicy::current()->default_return_window_days;
        $reference = $order->completed_at ?? $order->delivered_at ?? $order->updated_at;

        if (! $reference || ! $reference->copy()->addDays($windowDays)->isFuture()) {
            return 0;
        }

        $returnedQty = ReturnRequestItem::query()
            ->where('order_item_id', $item->id)
            ->whereHas('returnRequest', fn ($query) => $query
                ->where('order_id', $order->id)
                ->whereNotIn('status', ['rejected']))
            ->sum('qty');

        return max(0, $item->qty - (int) $returnedQty);
    }

    /**
     * @param  list<array{order_item_id: int, qty: int, reason: string, description?: string}>  $items
     * @param  list<UploadedFile>  $mediaFiles
     */
    public function submit(
        Order $order,
        int $customerId,
        array $items,
        array $mediaFiles = [],
    ): ReturnRequest {
        foreach ($items as $row) {
            $orderItem = $order->items()->where('id', $row['order_item_id'])->firstOrFail();
            $returnableQty = $this->getReturnableQty($order, $orderItem);

            if ($returnableQty <= 0) {
                throw new InvalidArgumentException("Item {$orderItem->product_name} tidak dapat diretur.");
            }

            if ($row['qty'] > $returnableQty) {
                throw new InvalidArgumentException("Jumlah retur {$orderItem->product_name} melebihi sisa yang bisa diretur ({$returnableQty}).");
            }
        }

        return DB::transaction(function () use ($order, $customerId, $items, $mediaFiles) {
            $request = ReturnRequest::create([
                'order_id' => $order->id,
                'customer_id' => $customerId,
                'status' => 'pending_review',
            ]);

            foreach ($items as $row) {
                ReturnRequestItem::create([
                    'return_request_id' => $request->id,
                    'order_item_id' => $row['order_item_id'],
                    'qty' => $row['qty'],
                    'reason' => $row['reason'],
                    'description' => $row['description'] ?? null,
                ]);
            }

            foreach ($mediaFiles as $file) {
                $path = $file->store('return-proofs', 'public');
                ReturnRequestMedia::create([
                    'return_request_id' => $request->id,
                    'path' => $path,
                    'type' => str_starts_with($file->getMimeType() ?? '', 'video/') ? 'video' : 'image',
                ]);
            }

            AdminNotification::notify(
                'return_submitted',
                'Pengajuan Retur '.$request->request_number,
                'Pesanan #'.$order->order_number,
                ['return_request_id' => $request->id, 'order_id' => $order->id],
            );

            $this->syncOrderReturnStatus($order->fresh());

            return $request->load(['items.orderItem', 'media']);
        });
    }

    public function syncOrderReturnStatus(Order $order): void
    {
        $order = $order->fresh();
        $hasActiveReturn = $this->hasActiveReturn($order);

        if ($hasActiveReturn && in_array($order->order_status, ['completed', 'delivered'], true)) {
            $this->orderWorkflow->transition(
                $order,
                'return',
                'Ada pengajuan retur aktif',
                'system',
                null,
                false,
            );

            return;
        }

        if (! $hasActiveReturn && $order->order_status === 'return') {
            $this->orderWorkflow->transition(
                $order,
                'completed',
                'Semua retur selesai',
                'system',
                null,
                false,
            );
        }
    }

    public function hasActiveReturn(Order $order): bool
    {
        return ReturnRequest::query()
            ->where('order_id', $order->id)
            ->whereNotIn('status', ['rejected', 'completed'])
            ->exists();
    }

    public function approve(ReturnRequest $request, ?string $adminNote = null): ReturnRequest
    {
        if ($request->status !== 'pending_review') {
            throw new InvalidArgumentException('Retur tidak dapat disetujui pada status saat ini.');
        }

        $request->update([
            'status' => 'awaiting_return_shipment',
            'admin_note' => $adminNote,
            'approved_at' => now(),
        ]);

        return $request->fresh();
    }

    public function reject(ReturnRequest $request, string $adminNote): ReturnRequest
    {
        if ($request->status !== 'pending_review') {
            throw new InvalidArgumentException('Retur tidak dapat ditolak pada status saat ini.');
        }

        $request->update([
            'status' => 'rejected',
            'admin_note' => $adminNote,
        ]);

        $this->syncOrderReturnStatus($request->order);

        return $request->fresh();
    }

    public function submitReturnShipment(ReturnRequest $request, string $courier, string $trackingNumber): ReturnShipment
    {
        if ($request->status !== 'awaiting_return_shipment') {
            throw new InvalidArgumentException('Retur tidak menunggu pengiriman barang.');
        }

        $shipment = ReturnShipment::updateOrCreate(
            ['return_request_id' => $request->id],
            [
                'courier' => $courier,
                'tracking_number' => $trackingNumber,
                'shipped_at' => now(),
            ],
        );

        $request->update(['status' => 'return_in_transit']);

        return $shipment;
    }

    public function confirmReceived(ReturnRequest $request): ReturnRequest
    {
        if ($request->status !== 'return_in_transit') {
            throw new InvalidArgumentException('Retur belum dalam perjalanan.');
        }

        $request->shipment?->update(['received_at' => now()]);
        $request->update(['status' => 'received']);

        return $request->fresh();
    }

    public function resolve(ReturnRequest $request, string $resolutionType, ?string $adminNote = null): ReturnRequest
    {
        if ($request->status !== 'received') {
            throw new InvalidArgumentException('Retur belum siap diselesaikan.');
        }

        if (! in_array($resolutionType, ['refund', 'replacement'], true)) {
            throw new InvalidArgumentException('Resolusi tidak valid.');
        }

        if ($resolutionType === 'refund') {
            return $this->resolveRefund($request, $adminNote);
        }

        return $this->resolveReplacement($request, $adminNote);
    }

    public function shipReplacement(
        ReturnRequest $request,
        string $courier,
        ?string $courierService,
        string $trackingNumber,
        ?int $adminId = null,
    ): Order {
        if ($request->status !== 'replacing' || ! $request->replacement_order_id) {
            throw new InvalidArgumentException('Pesanan pengganti belum tersedia.');
        }

        $order = $request->replacementOrder()->firstOrFail();

        if ($order->order_status !== 'processed') {
            throw new InvalidArgumentException('Barang pengganti sudah dikirim atau belum siap dikirim.');
        }

        $this->orderWorkflow->transition(
            $order,
            'shipped',
            'Barang pengganti dikirim',
            'admin',
            $adminId,
            true,
            array_filter([
                'courier' => $courier,
                'courier_service' => $courierService,
                'tracking_number' => $trackingNumber,
            ], fn ($value) => $value !== null && $value !== ''),
        );

        return $order->fresh();
    }

    public function completeReplacementReturn(ReturnRequest $request): ReturnRequest
    {
        if ($request->status !== 'replacing') {
            return $request;
        }

        $request->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $request->load('order');
        $this->syncOrderReturnStatus($request->order);

        return $request->fresh();
    }

    private function resolveRefund(ReturnRequest $request, ?string $adminNote): ReturnRequest
    {
        $request->update([
            'status' => 'refunding',
            'resolution_type' => 'refund',
            'admin_note' => $adminNote ?? $request->admin_note,
        ]);

        $order = $request->order;
        $refundAmount = $request->items->sum(fn ($i) => ($i->orderItem?->product_price ?? 0) * $i->qty);
        $order->updateTrusted([
            'refund_status' => 'partial',
            'refunded_amount' => min($order->refunded_amount + $refundAmount, $order->grand_total),
        ]);

        $request->update(['status' => 'completed', 'completed_at' => now()]);

        $this->syncOrderReturnStatus($request->order);

        return $request->fresh();
    }

    private function resolveReplacement(ReturnRequest $request, ?string $adminNote): ReturnRequest
    {
        return DB::transaction(function () use ($request, $adminNote) {
            $replacementOrder = $this->createReplacementOrder($request);

            $request->update([
                'status' => 'replacing',
                'resolution_type' => 'replacement',
                'replacement_order_id' => $replacementOrder->id,
                'admin_note' => $adminNote ?? $request->admin_note,
            ]);

            return $request->fresh(['replacementOrder']);
        });
    }

    private function createReplacementOrder(ReturnRequest $request): Order
    {
        $request->load('items.orderItem', 'order');
        $original = $request->order;

        $order = Order::createTrusted([
            'order_number' => generate_order_number(),
            'customer_id' => $original->customer_id,
            'customer_name' => $original->customer_name,
            'customer_phone' => $original->customer_phone,
            'customer_email' => $original->customer_email,
            'shipping_address' => $original->shipping_address,
            'province_code' => $original->province_code,
            'province_name' => $original->province_name,
            'regency_code' => $original->regency_code,
            'regency_name' => $original->regency_name,
            'district_code' => $original->district_code,
            'district_name' => $original->district_name,
            'village_code' => $original->village_code,
            'village_name' => $original->village_name,
            'postal_code' => $original->postal_code,
            'shipping_city' => $original->shipping_city,
            'shipping_cost' => 0,
            'shipping_method' => 'replacement',
            'total_price' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'grand_total' => 0,
            'payment_method' => 'replacement',
            'payment_status' => 'paid',
            'payment_confirmation_status' => 'approved',
            'paid_at' => now(),
            'order_status' => 'processed',
            'is_replacement' => true,
            'source_return_request_id' => $request->id,
            'notes' => 'Pesanan pengganti untuk retur '.$request->request_number,
        ]);

        foreach ($request->items as $returnItem) {
            $orderItem = $returnItem->orderItem;
            if (! $orderItem) {
                continue;
            }

            $order->items()->create([
                'product_id' => $orderItem->product_id,
                'product_variant_id' => $orderItem->product_variant_id,
                'sku' => $orderItem->sku,
                'product_name' => $orderItem->product_name,
                'product_price' => 0,
                'qty' => $returnItem->qty,
                'subtotal' => 0,
                'size' => $orderItem->size,
                'color' => $orderItem->color,
            ]);
        }

        $this->orderWorkflow->recordInitialStatus($order);
        $this->orderWorkflow->transition(
            $order,
            'processed',
            'Pesanan pengganti retur dibuat',
            'system',
        );

        grant_order_access($order);

        return $order->fresh(['items']);
    }
}
