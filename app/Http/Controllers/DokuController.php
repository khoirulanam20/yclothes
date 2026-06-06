<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentWebhookLog;
use App\Services\DokuService;
use App\Services\OrderPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DokuController extends Controller
{
    public function notification(Request $request, DokuService $doku)
    {
        if (! $doku->verifyNotificationSignature($request)) {
            Log::warning('DOKU notification invalid signature');

            return response('Invalid signature', 401);
        }

        $payload = $request->json()->all();
        $parsed = $doku->parseNotification($payload);

        if (! $parsed['invoice_number']) {
            return response('Invalid payload', 400);
        }

        $order = Order::where('order_number', $parsed['invoice_number'])->first();
        if (! $order) {
            return response('Order not found', 404);
        }

        $status = strtoupper((string) ($parsed['status'] ?? ''));
        $isSuccess = in_array($status, ['SUCCESS', 'PAID', 'SETTLEMENT'], true);
        $isDuplicate = $order->payment_status === 'paid' && $isSuccess;

        if (
            setting_bool('reject_webhook_amount_mismatch', true)
            && $parsed['amount'] !== null
            && (int) $parsed['amount'] !== (int) $order->grand_total
        ) {
            Log::warning('DOKU amount mismatch', [
                'order_number' => $order->order_number,
                'expected' => $order->grand_total,
                'received' => $parsed['amount'],
            ]);

            return response('Amount mismatch', 400);
        }

        if (setting_bool('log_duplicate_webhooks', true)) {
            PaymentWebhookLog::create([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'provider' => 'doku',
                'event_type' => 'notification',
                'transaction_status' => $status,
                'amount' => $parsed['amount'],
                'is_duplicate' => $isDuplicate,
                'payload' => $payload,
            ]);
        }

        if ($isDuplicate) {
            return response('OK', 200);
        }

        app(OrderPaymentService::class)->applyDokuStatus($order, $status);

        return response('OK', 200);
    }

    public function return(Order $order)
    {
        if ($order->payment_status !== 'paid') {
            $order->refresh();
        }

        return redirect()->to(order_public_url('order.success', $order));
    }
}
