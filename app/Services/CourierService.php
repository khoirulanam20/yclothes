<?php

namespace App\Services;

/**
 * Facade untuk integrasi kurir (Biteship).
 */
class CourierService
{
    public function __construct(private BiteshipService $biteship) {}

    public function isEnabled(): bool
    {
        return $this->biteship->isConfigured();
    }

    /**
     * @return list<array{code: string, name: string, cost: int, etd: string}>
     */
    public function getRates(string $regencyCode, int $weightGrams, ?string $postalCode = null): array
    {
        if (! $this->isEnabled() || ! $postalCode) {
            return [];
        }

        $rates = $this->biteship->getRates($postalCode, $weightGrams);
        $formatted = $this->biteship->formatRatesForCheckout($rates);

        return array_map(fn (array $row) => [
            'code' => $row['courierCode'],
            'name' => $row['courierServiceName']
                ? $row['courierName'].' — '.$row['courierServiceName']
                : $row['courierName'],
            'cost' => $row['cost'],
            'etd' => (string) ($row['etd'] ?? ''),
        ], $formatted);
    }

    public function track(string $courier, string $trackingNumber): ?array
    {
        if (! $this->biteship->isLiveTrackingEnabled()) {
            return null;
        }

        return null;
    }
}
