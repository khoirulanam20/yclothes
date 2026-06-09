<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KlikQrisService
{
    private const SANDBOX_BASE = 'https://klikqris.com/api/sandbox';

    private const PRODUCTION_BASE = 'https://klikqris.com/api';

    /** @return array{api_key: ?string, merchant_id: ?string, is_sandbox: bool} */
    public static function resolveConfig(): array
    {
        $apiKey = setting('klikqris_api_key') ?: config('services.klikqris.api_key');

        return [
            'api_key' => $apiKey,
            'merchant_id' => setting('klikqris_merchant_id') ?: config('services.klikqris.merchant_id'),
            'is_sandbox' => self::isSandboxKey($apiKey),
        ];
    }

    public static function hasCredentials(): bool
    {
        $config = self::resolveConfig();

        return filled($config['api_key']) && filled($config['merchant_id']);
    }

    public static function isSandboxKey(?string $apiKey): bool
    {
        return is_string($apiKey) && str_starts_with($apiKey, 'sk_sandbox_');
    }

    public function baseUrl(): string
    {
        return self::resolveConfig()['is_sandbox'] ? self::SANDBOX_BASE : self::PRODUCTION_BASE;
    }

    /** @return array<string, string> */
    public function defaultHeaders(): array
    {
        $config = self::resolveConfig();

        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'x-api-key' => (string) $config['api_key'],
            'id_merchant' => (string) $config['merchant_id'],
        ];
    }

    public static function notificationUrl(): string
    {
        return url(route('klikqris.notification', [], false));
    }

    public function redirectUrl(Order $order): string
    {
        return order_public_url('order.success', $order);
    }

    /** @return array{signature: string, qris_url: ?string, qris_image: ?string, total_amount: int, amount: int, expired_at: ?string, status: ?string} */
    public function createTransaction(Order $order): array
    {
        try {
            return $this->requestCreate($order);
        } catch (\RuntimeException $e) {
            if ($this->shouldRecoverExistingTransaction($e)) {
                Log::info('KlikQRIS create retry via status check', [
                    'order_number' => $order->order_number,
                ]);

                return $this->recoverExistingTransaction($order);
            }

            throw $e;
        }
    }

    /** @return array{signature: string, qris_url: ?string, qris_image: ?string, total_amount: int, amount: int, expired_at: ?string, status: ?string} */
    private function requestCreate(Order $order): array
    {
        $config = self::resolveConfig();

        $response = Http::withHeaders($this->defaultHeaders())
            ->post($this->baseUrl().'/qris/create', [
                'order_id' => $order->order_number,
                'amount' => (int) $order->grand_total,
                'id_merchant' => $config['merchant_id'],
                'keterangan' => 'Pesanan #'.$order->order_number,
                'callback_url' => self::notificationUrl(),
                'redirect_url' => $this->redirectUrl($order),
            ]);

        if (! $response->successful()) {
            Log::error('KlikQRIS create HTTP error', [
                'order_number' => $order->order_number,
                'status' => $response->status(),
                'body' => $response->body(),
                'is_sandbox' => $config['is_sandbox'],
            ]);

            throw new \RuntimeException('KlikQRIS create failed: '.$response->body());
        }

        $json = $response->json();

        if (! ($json['status'] ?? false)) {
            Log::error('KlikQRIS create rejected', [
                'order_number' => $order->order_number,
                'message' => $json['message'] ?? $response->body(),
                'is_sandbox' => $config['is_sandbox'],
            ]);

            throw new \RuntimeException('KlikQRIS create rejected: '.($json['message'] ?? $response->body()));
        }

        return $this->parseCreateResponse($json);
    }

    /** @return array{signature: string, qris_url: ?string, qris_image: ?string, total_amount: int, amount: int, expired_at: ?string, status: ?string} */
    private function recoverExistingTransaction(Order $order): array
    {
        $status = $this->checkStatus($order->order_number);

        if (! filled($status['signature'] ?? null)) {
            throw new \RuntimeException('KlikQRIS existing transaction could not be recovered for '.$order->order_number);
        }

        return [
            'signature' => (string) $status['signature'],
            'qris_url' => null,
            'qris_image' => null,
            'total_amount' => $status['total_amount'] ?? (int) $order->grand_total,
            'amount' => (int) $order->grand_total,
            'expired_at' => null,
            'status' => $status['status'],
        ];
    }

    private function shouldRecoverExistingTransaction(\RuntimeException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'already')
            || str_contains($message, 'exist')
            || str_contains($message, 'duplicate')
            || str_contains($message, 'sudah ada');
    }

    /** @return array{status: ?string, total_amount: ?int, signature: ?string} */
    public function checkStatus(string $orderId): array
    {
        $response = Http::withHeaders($this->defaultHeaders())
            ->get($this->baseUrl().'/qris/status/'.$orderId);

        if (! $response->successful()) {
            throw new \RuntimeException('KlikQRIS status check failed: '.$response->body());
        }

        $data = $response->json('data') ?? [];

        return [
            'status' => isset($data['status']) ? strtoupper((string) $data['status']) : null,
            'total_amount' => $this->parseAmount($data['total_amount'] ?? null),
            'signature' => isset($data['signature']) ? (string) $data['signature'] : null,
        ];
    }

    /** @param  array<string, mixed>  $json */
    public function parseCreateResponse(array $json): array
    {
        $data = $json['data'] ?? [];

        return [
            'signature' => (string) ($data['signature'] ?? ''),
            'qris_url' => isset($data['qris_url']) ? (string) $data['qris_url'] : null,
            'qris_image' => isset($data['qris_image']) ? (string) $data['qris_image'] : null,
            'total_amount' => $this->parseAmount($data['total_amount'] ?? null) ?? (int) ($data['amount'] ?? 0),
            'amount' => $this->parseAmount($data['amount'] ?? null) ?? 0,
            'expired_at' => isset($data['expired_at']) ? (string) $data['expired_at'] : null,
            'status' => isset($data['status']) ? strtoupper((string) $data['status']) : null,
        ];
    }

    /** @param  array<string, mixed>  $payload */
    public function parseWebhook(array $payload): array
    {
        return [
            'order_id' => isset($payload['order_id']) ? (string) $payload['order_id'] : null,
            'status' => isset($payload['status']) ? strtoupper((string) $payload['status']) : null,
            'total_amount' => $this->parseAmount($payload['total_amount'] ?? null),
            'amount' => $this->parseAmount($payload['amount'] ?? null),
            'signature' => isset($payload['signature']) ? (string) $payload['signature'] : null,
        ];
    }

    /** @param  array<string, mixed>  $payload */
    public function verifyWebhookSignature(array $payload): bool
    {
        $signature = $payload['signature'] ?? null;

        if (! filled($signature)) {
            return false;
        }

        $config = self::resolveConfig();
        $apiKey = (string) ($config['api_key'] ?? '');

        if ($apiKey === '') {
            return false;
        }

        if ($config['is_sandbox'] && str_starts_with((string) $signature, 'SANDBOX_SIG_')) {
            return true;
        }

        $orderId = (string) ($payload['order_id'] ?? '');
        $status = strtoupper((string) ($payload['status'] ?? ''));
        $totalAmount = (string) ($this->parseAmount($payload['total_amount'] ?? null) ?? '');

        $candidates = [
            hash_hmac('sha256', $orderId.$status.$totalAmount, $apiKey),
            hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES), $apiKey),
        ];

        foreach ($candidates as $expected) {
            if (hash_equals($expected, (string) $signature)) {
                return true;
            }
        }

        Log::warning('KlikQRIS webhook signature could not be verified', [
            'order_id' => $orderId,
            'is_sandbox' => $config['is_sandbox'],
        ]);

        return $config['is_sandbox'];
    }

    private function parseAmount(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) round($value);
        }

        $normalized = str_replace([',', ' '], '', (string) $value);

        if (! is_numeric($normalized)) {
            return null;
        }

        if (str_contains($normalized, '.')) {
            return (int) round((float) $normalized);
        }

        return (int) $normalized;
    }
}
