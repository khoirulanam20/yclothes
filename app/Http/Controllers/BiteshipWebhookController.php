<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BiteshipWebhookController extends Controller
{
    public function __construct(private OrderWorkflowService $orderWorkflow) {}

    public function handle(Request $request)
    {
        $webhookKey = config('services.biteship.webhook_key');
        if ($webhookKey) {
            $signature = $request->header('X-Biteship-Signature', '');
            $expected = hash_hmac('sha256', $request->getContent(), $webhookKey);
            if (! hash_equals($expected, $signature)) {
                Log::warning('Biteship webhook signature mismatch');

                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $payload = $request->all();

        Log::info('Biteship webhook received', ['payload' => $payload]);

        $orderId = data_get($payload, 'order_id')
            ?? data_get($payload, 'courier.tracking_id')
            ?? data_get($payload, 'reference_id');

        $order = null;
        if ($orderId) {
            $order = Order::query()
                ->where('biteship_order_id', (string) $orderId)
                ->orWhere('order_number', (string) data_get($payload, 'reference_id'))
                ->first();
        }

        DB::table('biteship_webhook_logs')->insert([
            'order_id' => $order?->id,
            'event_type' => (string) data_get($payload, 'status', 'unknown'),
            'payload' => json_encode($payload),
            'status' => 'received',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (! $order) {
            return response()->json(['success' => true]);
        }

        $status = strtolower((string) data_get($payload, 'status', ''));
        $trackingNumber = data_get($payload, 'courier.waybill_id')
            ?? data_get($payload, 'courier.tracking_id')
            ?? data_get($payload, 'tracking_number');

        $updates = array_filter([
            'tracking_number' => $trackingNumber,
        ], fn ($v) => $v !== null && $v !== '');

        if ($updates !== []) {
            $order->updateTrusted($updates);
        }

        if (in_array($status, ['picked', 'dropping_off', 'shipped', 'on_delivery'], true) && $order->order_status === 'processed') {
            $this->orderWorkflow->transition(
                $order->fresh(),
                'shipped',
                'Status diperbarui dari Biteship',
                'system',
                null,
                true,
                [
                    'courier' => $order->courier,
                    'courier_service' => $order->courier_service,
                    'tracking_number' => $trackingNumber ?? $order->tracking_number,
                ],
            );
        }

        if (in_array($status, ['delivered', 'finished'], true) && in_array($order->order_status, ['shipped', 'processed'], true)) {
            $this->orderWorkflow->transition(
                $order->fresh(),
                'delivered',
                'Paket sampai (Biteship)',
                'system',
            );
        }

        return response()->json(['success' => true]);
    }
}
