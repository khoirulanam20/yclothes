<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DokuService
{
    private const SANDBOX_BASE = 'https://api-sandbox.doku.com';

    private const PRODUCTION_BASE = 'https://api.doku.com';

    /** @return array{client_id: ?string, secret_key: ?string, is_production: bool} */
    public static function resolveConfig(): array
    {
        return [
            'client_id' => setting('doku_client_id') ?: config('services.doku.client_id'),
            'secret_key' => setting('doku_secret_key') ?: config('services.doku.secret_key'),
            'is_production' => setting_bool('doku_is_production')
                || filter_var(config('services.doku.is_production', false), FILTER_VALIDATE_BOOLEAN),
        ];
    }

    public static function hasCredentials(): bool
    {
        $config = self::resolveConfig();

        return filled($config['client_id']) && filled($config['secret_key']);
    }

    public static function isActive(): bool
    {
        return self::hasCredentials();
    }

    public function createCheckout(Order $order): string
    {
        $config = self::resolveConfig();
        $baseUrl = $config['is_production'] ? self::PRODUCTION_BASE : self::SANDBOX_BASE;
        $path = '/checkout/v1/payment';
        $requestId = (string) Str::uuid();
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');

        $successUrl = order_public_url('order.doku-return', $order);

        $body = [
            'order' => [
                'amount' => (float) $order->grand_total,
                'invoice_number' => $order->order_number,
                'currency' => 'IDR',
                'callback_url' => $successUrl,
                'callback_url_cancel' => $successUrl,
                'language' => 'ID',
                'auto_redirect' => true,
            ],
            'payment' => [
                'payment_due_date' => max(1, (int) setting('payment_timeout_hours', 24)) * 60,
            ],
            'customer' => [
                'name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
            ],
        ];

        $signature = $this->generateSignature(
            $config['client_id'],
            $config['secret_key'],
            $requestId,
            $timestamp,
            $path,
            $body,
        );

        $response = Http::withHeaders([
            'Client-Id' => $config['client_id'],
            'Request-Id' => $requestId,
            'Request-Timestamp' => $timestamp,
            'Signature' => 'HMACSHA256='.$signature,
            'Content-Type' => 'application/json',
        ])->post($baseUrl.$path, $body);

        if (! $response->successful()) {
            throw new \RuntimeException('DOKU checkout failed: '.$response->body());
        }

        $paymentUrl = $response->json('response.payment.url')
            ?? $response->json('payment.url')
            ?? $response->json('response.url');

        if (! filled($paymentUrl)) {
            throw new \RuntimeException('DOKU checkout response missing payment URL.');
        }

        return (string) $paymentUrl;
    }

    public function verifyNotificationSignature(Request $request): bool
    {
        $config = self::resolveConfig();
        if (! filled($config['secret_key'])) {
            return false;
        }

        $clientId = $request->header('Client-Id');
        $requestId = $request->header('Request-Id');
        $timestamp = $request->header('Request-Timestamp');
        $signatureHeader = $request->header('Signature');

        if (! $clientId || ! $requestId || ! $timestamp || ! $signatureHeader) {
            return false;
        }

        $received = str_starts_with($signatureHeader, 'HMACSHA256=')
            ? substr($signatureHeader, 11)
            : $signatureHeader;

        $target = '/'.ltrim($request->path(), '/');
        $body = $request->getContent();
        $digest = base64_encode(hash('sha256', $body, true));

        $component = "Client-Id:{$clientId}\n"
            ."Request-Id:{$requestId}\n"
            ."Request-Timestamp:{$timestamp}\n"
            ."Request-Target:{$target}\n"
            ."Digest:{$digest}";

        $expected = base64_encode(hash_hmac('sha256', $component, $config['secret_key'], true));

        return hash_equals($expected, $received);
    }

    /** @return array{invoice_number: ?string, status: ?string, amount: ?int} */
    public function parseNotification(array $payload): array
    {
        $order = $payload['order'] ?? [];
        $transaction = $payload['transaction'] ?? [];

        return [
            'invoice_number' => $order['invoice_number'] ?? $transaction['invoice_number'] ?? null,
            'status' => $transaction['status'] ?? $payload['transaction']['status'] ?? null,
            'amount' => isset($order['amount']) ? (int) $order['amount'] : null,
        ];
    }

    /** @param  array<string, mixed>  $body */
    private function generateSignature(
        string $clientId,
        string $secretKey,
        string $requestId,
        string $timestamp,
        string $path,
        array $body,
    ): string {
        $digest = base64_encode(hash('sha256', json_encode($body), true));
        $component = "Client-Id:{$clientId}\n"
            ."Request-Id:{$requestId}\n"
            ."Request-Timestamp:{$timestamp}\n"
            ."Request-Target:{$path}\n"
            ."Digest:{$digest}";

        return base64_encode(hash_hmac('sha256', $component, $secretKey, true));
    }
}
