<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentWebhookLog;
use App\Services\KlikQrisService;
use App\Services\OrderPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KlikQrisController extends Controller
{
    public function payment(Order $order, KlikQrisService $klikQris)
    {
        if ($order->payment_method !== 'klikqris' || $order->payment_status === 'paid') {
            return redirect()->to(order_public_url('order.show', $order));
        }

        $data = $order->payment_gateway_data['klikqris'] ?? null;

        if (! filled($data['signature'] ?? null)) {
            try {
                $result = $klikQris->createTransaction($order);
                $order->updateTrusted([
                    'unique_payment_amount' => $result['total_amount'],
                    'payment_gateway_data' => ['klikqris' => $result],
                ]);
                $data = $result;
            } catch (\Exception $e) {
                report($e);

                return redirect()->to(order_public_url('order.show', $order))
                    ->with('error', 'Gagal memproses pembayaran KlikQRIS. Silakan coba lagi atau hubungi kami.');
            }
        }

        $config = KlikQrisService::resolveConfig();

        return view('order.klikqris', [
            'order' => $order->fresh(),
            'signature' => $data['signature'],
            'qrisUrl' => $data['qris_url'] ?? null,
            'qrisImage' => $data['qris_image'] ?? null,
            'isSandbox' => $config['is_sandbox'],
            'successUrl' => order_public_url('order.success', $order),
            'orderShowUrl' => order_public_url('order.show', $order),
            'verifyUrl' => route('order.klikqris-verify', [
                'order' => $order->order_number,
                'token' => $order->access_token,
            ]),
        ]);
    }

    public function verifyPayment(Order $order, KlikQrisService $klikQris)
    {
        if ($order->payment_method !== 'klikqris') {
            return response()->json(['success' => false]);
        }

        if ($order->payment_status === 'paid') {
            return response()->json([
                'success' => true,
                'redirect' => order_public_url('order.success', $order),
            ]);
        }

        try {
            $statusCheck = $klikQris->checkStatus($order->order_number);

            if (in_array($statusCheck['status'], ['PAID', 'SUCCESS'], true)) {
                app(OrderPaymentService::class)->applyKlikQrisStatus($order, (string) $statusCheck['status']);

                return response()->json([
                    'success' => true,
                    'redirect' => order_public_url('order.success', $order->fresh()),
                ]);
            }
        } catch (\Exception $e) {
            report($e);
        }

        return response()->json(['success' => false]);
    }

    public function notification(Request $request, KlikQrisService $klikQris)
    {
        $payload = $request->json()->all();
        $parsed = $klikQris->parseWebhook($payload);

        if (! $parsed['order_id']) {
            return response('Invalid payload', 400);
        }

        $order = Order::where('order_number', $parsed['order_id'])->first();
        if (! $order) {
            return response('Order not found', 404);
        }

        if (! $klikQris->verifyWebhookSignature($payload)) {
            Log::warning('KlikQRIS notification invalid signature', [
                'order_number' => $order->order_number,
            ]);

            try {
                $statusCheck = $klikQris->checkStatus($order->order_number);
                if (! in_array($statusCheck['status'], ['PAID', 'SUCCESS'], true)) {
                    return response('Invalid signature', 401);
                }
                $parsed['status'] = $statusCheck['status'];
                $parsed['total_amount'] = $statusCheck['total_amount'] ?? $parsed['total_amount'];
            } catch (\Exception $e) {
                report($e);

                return response('Invalid signature', 401);
            }
        }

        $status = strtoupper((string) ($parsed['status'] ?? ''));
        $isSuccess = in_array($status, ['PAID', 'SUCCESS'], true);
        $isDuplicate = $order->payment_status === 'paid' && $isSuccess;

        $expectedAmount = $order->unique_payment_amount ?? $order->grand_total;

        if (
            setting_bool('reject_webhook_amount_mismatch', true)
            && $parsed['total_amount'] !== null
            && (int) $parsed['total_amount'] !== (int) $expectedAmount
        ) {
            Log::warning('KlikQRIS amount mismatch', [
                'order_number' => $order->order_number,
                'expected' => $expectedAmount,
                'received' => $parsed['total_amount'],
            ]);

            return response('Amount mismatch', 400);
        }

        if (setting_bool('log_duplicate_webhooks', true)) {
            PaymentWebhookLog::create([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'provider' => 'klikqris',
                'event_type' => 'notification',
                'transaction_status' => $status,
                'amount' => $parsed['total_amount'],
                'is_duplicate' => $isDuplicate,
                'payload' => $payload,
            ]);
        }

        if ($isDuplicate) {
            return response('OK', 200);
        }

        app(OrderPaymentService::class)->applyKlikQrisStatus($order, $status);

        return response('OK', 200);
    }
}
