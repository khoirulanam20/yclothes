<?php

namespace App\Services;

use App\Models\Order;

class OrderPaymentService
{
    public function __construct(private InventoryService $inventoryService) {}

    public function applyMidtransStatus(Order $order, string $transactionStatus): void
    {
        if ($transactionStatus === 'settlement' || $transactionStatus === 'capture') {
            if ($order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'paid',
                    'order_status' => 'confirmed',
                    'paid_at' => now(),
                ]);

                $this->inventoryService->decrementOnPaid($order->fresh());
            }
        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'], true)) {
            $order->update(['order_status' => 'cancelled']);
        }
    }
}
