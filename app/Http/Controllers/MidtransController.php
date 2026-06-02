<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderPaymentService;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Notification;

class MidtransController extends Controller
{
    public function notification()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');

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

        if (isset($notif->gross_amount) && (int) $notif->gross_amount !== (int) $order->grand_total) {
            Log::warning('Midtrans gross_amount mismatch', [
                'order_number' => $order->order_number,
                'expected' => $order->grand_total,
                'received' => $notif->gross_amount,
            ]);

            return response('Amount mismatch', 400);
        }

        app(OrderPaymentService::class)->applyMidtransStatus($order, $transactionStatus);

        return response('OK', 200);
    }
}
