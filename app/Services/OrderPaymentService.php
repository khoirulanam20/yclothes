<?php

namespace App\Services;

use App\Mail\OrderInvoiceMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

class OrderPaymentService
{
    public function applyMidtransStatus(Order $order, string $transactionStatus): void
    {
        if ($transactionStatus === 'settlement' || $transactionStatus === 'capture') {
            if ($order->payment_status !== 'paid') {
                $this->markPaid($order, 'midtrans');
            }
        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'], true)) {
            app(OrderWorkflowService::class)->transition(
                $order,
                'cancelled',
                'Pembayaran Midtrans dibatalkan',
                'system',
            );
        }
    }

    public function markPaid(Order $order, string $source = 'admin'): Order
    {
        if ($order->payment_status === 'paid') {
            return $order;
        }

        $workflow = app(OrderWorkflowService::class);

        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_confirmation_status' => 'approved',
        ]);

        $workflow->transition($order->fresh(), 'confirmed', "Pembayaran dikonfirmasi ({$source})", $source === 'admin' ? 'admin' : 'system');

        if ($order->customer_email) {
            Mail::to($order->customer_email)->queue(new OrderInvoiceMail($order->fresh(['items'])));
        }

        return $order->fresh();
    }
}
