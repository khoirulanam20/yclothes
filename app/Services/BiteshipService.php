<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BiteshipService
{
    private const BASE_URL = 'https://api.biteship.com/v1';

    public function isConfigured(): bool
    {
        return setting('shipping_mode', 'manual') === 'biteship'
            && $this->apiKey() !== '';
    }

    public function apiKey(): string
    {
        return trim((string) (setting('biteship_api_key') ?: config('services.courier.api_key', '')));
    }

    /**
     * @return list<array{
     *   courier_code: string,
     *   courier_name: string,
     *   courier_service_code: string,
     *   courier_service_name: string,
     *   cost: int,
     *   etd: ?string
     * }>
     */
    public function getRates(string $destinationPostalCode, int $weightGrams, ?string $courierFilter = null): array
    {
        $originPostal = trim((string) setting('biteship_origin_postal_code', ''));
        if ($originPostal === '' || $destinationPostalCode === '') {
            return [];
        }

        $couriers = $courierFilter
            ? $courierFilter
            : $this->activeCouriersCsv();

        if ($couriers === '') {
            return [];
        }

        $cacheKey = 'biteship.rates.'.md5($originPostal.'|'.$destinationPostalCode.'|'.$weightGrams.'|'.$couriers);

        return Cache::remember($cacheKey, 300, function () use ($originPostal, $destinationPostalCode, $weightGrams, $couriers) {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => $this->apiKey(),
                    'Content-Type' => 'application/json',
                ])
                ->post(self::BASE_URL.'/rates/couriers', [
                    'origin_postal_code' => (int) preg_replace('/\D/', '', $originPostal),
                    'destination_postal_code' => (int) preg_replace('/\D/', '', $destinationPostalCode),
                    'couriers' => $couriers,
                    'items' => [
                        [
                            'name' => 'Order',
                            'value' => 10000,
                            'weight' => max(1, $weightGrams),
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                return [];
            }

            $pricing = $response->json('pricing') ?? $response->json('data.pricing') ?? [];

            if (! is_array($pricing)) {
                return [];
            }

            $rates = [];
            foreach ($pricing as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $rates[] = [
                    'courier_code' => strtolower((string) ($row['courier_code'] ?? '')),
                    'courier_name' => (string) ($row['courier_name'] ?? $row['courier_code'] ?? ''),
                    'courier_service_code' => (string) ($row['courier_service_code'] ?? ''),
                    'courier_service_name' => (string) ($row['courier_service_name'] ?? ''),
                    'cost' => (int) ($row['price'] ?? $row['shipping_fee'] ?? 0),
                    'etd' => isset($row['shipment_duration_range']) ? (string) $row['shipment_duration_range'] : null,
                ];
            }

            return $rates;
        });
    }

    /**
     * @param  list<array{courier_code: string, courier_name: string, courier_service_code: string, courier_service_name: string, cost: int, etd: ?string}>  $rates
     * @return list<array{optionKey: string, courierCode: string, courierName: string, cost: int, etd: ?string, courierServiceCode: string, courierServiceName: string}>
     */
    public function formatRatesForCheckout(array $rates): array
    {
        $active = array_filter(array_map('trim', explode(',', $this->activeCouriersCsv())));
        $options = [];

        foreach ($rates as $rate) {
            if ($rate['courier_code'] === '' || $rate['courier_service_code'] === '') {
                continue;
            }

            if ($active !== [] && ! in_array($rate['courier_code'], $active, true)) {
                continue;
            }

            $options[] = [
                'optionKey' => $rate['courier_code'].'|'.$rate['courier_service_code'],
                'courierCode' => $rate['courier_code'],
                'courierName' => $rate['courier_name'],
                'courierServiceCode' => $rate['courier_service_code'],
                'courierServiceName' => $rate['courier_service_name'],
                'cost' => $rate['cost'],
                'etd' => $rate['etd'],
            ];
        }

        usort($options, fn (array $a, array $b) => $a['cost'] <=> $b['cost']);

        return $options;
    }

    /**
     * @param  list<array{courier_code: string, courier_name: string, courier_service_code: string, courier_service_name: string, cost: int, etd: ?string}>  $rates
     * @return list<array{courierCode: string, courierName: string, cost: int, etd: ?string, courierServiceCode: string, courierServiceName: string}>
     */
    public function groupRatesByCourier(array $rates): array
    {
        $grouped = [];
        foreach ($rates as $rate) {
            $code = $rate['courier_code'];
            if ($code === '') {
                continue;
            }

            if (! isset($grouped[$code])) {
                $grouped[$code] = $rate;

                continue;
            }

            $strategy = setting('biteship_service_strategy', 'cheapest');
            $current = $grouped[$code];

            if ($strategy === 'default_reg') {
                $isReg = str_contains(strtolower($rate['courier_service_code']), 'reg')
                    || str_contains(strtolower($rate['courier_service_name']), 'reg');
                $currentIsReg = str_contains(strtolower($current['courier_service_code']), 'reg')
                    || str_contains(strtolower($current['courier_service_name']), 'reg');

                if ($isReg && ! $currentIsReg) {
                    $grouped[$code] = $rate;
                } elseif ($isReg === $currentIsReg && $rate['cost'] < $current['cost']) {
                    $grouped[$code] = $rate;
                }
            } elseif ($rate['cost'] < $current['cost']) {
                $grouped[$code] = $rate;
            }
        }

        return array_values(array_map(fn (array $rate) => [
            'courierCode' => $rate['courier_code'],
            'courierName' => $rate['courier_name'],
            'cost' => $rate['cost'],
            'etd' => $rate['etd'],
            'courierServiceCode' => $rate['courier_service_code'],
            'courierServiceName' => $rate['courier_service_name'],
        ], $grouped));
    }

    /**
     * @param  array<string, mixed>  $orderData
     * @return array<string, mixed>
     */
    public function createOrder(array $orderData): array
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => $this->apiKey(),
                'Content-Type' => 'application/json',
            ])
            ->post(self::BASE_URL.'/orders', $orderData);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal membuat order Biteship: '.$response->body());
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    public function activeCouriersCsv(): string
    {
        $raw = trim((string) setting('biteship_active_couriers', 'jne,jnt,sicepat'));

        return implode(',', array_filter(array_map('trim', explode(',', $raw))));
    }

    public function isLiveTrackingEnabled(): bool
    {
        return $this->isConfigured()
            && setting('biteship_fulfillment', 'rates_only') === 'live_tracking';
    }
}
