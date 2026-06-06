<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentWebhookLog;
use App\Services\MidtransService;
use App\Services\OrderPaymentService;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Notification;

class MidtransController extends Controller
{
    public function notification()
    {
        $config = MidtransService::resolveConfig();
        Config::$serverKey = $config['server_key'];
        Config::$isProduction = $config['is_production'];

        try {
            $notif = new Notification;
        } catch (\Exception $e) {
            Log::warning('Midtrans notification invalid', ['error' => $e->getMessage()]);

            return response('Invalid notification', 400);
        }

        $transactionStatus = $notif->transaction_status;
        $orderId = $notif->order_id;

        $order = Order::where('order_number', $orderId)->first();
        if (! $order) {
            return response('Order not found', 404);
        }

        $isDuplicate = $order->payment_status === 'paid'
            && in_array($transactionStatus, ['settlement', 'capture'], true);

        if (
            setting_bool('reject_webhook_amount_mismatch', true)
            && isset($notif->gross_amount)
            && (int) $notif->gross_amount !== (int) $order->grand_total
        ) {
            Log::warning('Midtrans gross_amount mismatch', [
                'order_number' => $order->order_number,
                'expected' => $order->grand_total,
                'received' => $notif->gross_amount,
            ]);

            return response('Amount mismatch', 400);
        }

        if (setting_bool('log_duplicate_webhooks', true)) {
            PaymentWebhookLog::create([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'provider' => 'midtrans',
                'event_type' => 'notification',
                'transaction_status' => $transactionStatus,
                'amount' => isset($notif->gross_amount) ? (int) $notif->gross_amount : null,
                'is_duplicate' => $isDuplicate,
                'payload' => json_decode(json_encode($notif), true),
            ]);
        }

        if ($isDuplicate) {
            return response('OK', 200);
        }

        app(OrderPaymentService::class)->applyMidtransStatus($order, $transactionStatus);

        return response('OK', 200);
    }
}
