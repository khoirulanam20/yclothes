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

            return;
        }

        if (! in_array($transactionStatus, ['deny', 'expire', 'cancel'], true)) {
            return;
        }

        if (! setting_bool('auto_cancel_on_payment_fail', true)) {
            return;
        }

        $action = setting('payment_fail_action', 'cancel_order');

        if ($action === 'keep_pending') {
            return;
        }

        if ($action === 'mark_failed') {
            $order->update(['payment_status' => 'failed']);

            return;
        }

        app(OrderWorkflowService::class)->transition(
            $order,
            'cancelled',
            'Pembayaran Midtrans dibatalkan',
            'system',
        );
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

        $fresh = $order->fresh();

        if (in_array($fresh->order_status, ['pending', 'awaiting_verification'], true)) {
            $workflow->transition(
                $fresh,
                'confirmed',
                "Pembayaran dikonfirmasi ({$source})",
                $source === 'admin' ? 'admin' : 'system',
            );
            $fresh = $fresh->fresh();
        }

        if ($fresh->customer_email) {
            Mail::to($fresh->customer_email)->queue(new OrderInvoiceMail($fresh->fresh(['items'])));
        }

        return $fresh;
    }

    public function applyDokuStatus(Order $order, string $status): void
    {
        $status = strtoupper($status);

        if (in_array($status, ['SUCCESS', 'PAID', 'SETTLEMENT'], true)) {
            if ($order->payment_status !== 'paid') {
                $this->markPaid($order, 'doku');
            }

            return;
        }

        if (! in_array($status, ['FAILED', 'EXPIRED', 'CANCELLED', 'CANCEL'], true)) {
            return;
        }

        if (! setting_bool('auto_cancel_on_payment_fail', true)) {
            return;
        }

        $action = setting('payment_fail_action', 'cancel_order');

        if ($action === 'keep_pending') {
            return;
        }

        if ($action === 'mark_failed') {
            $order->update(['payment_status' => 'failed']);

            return;
        }

        app(OrderWorkflowService::class)->transition(
            $order,
            'cancelled',
            'Pembayaran DOKU gagal',
            'system',
        );
    }
}
