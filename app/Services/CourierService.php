<?php

namespace App\Services;

/**
 * Placeholder untuk integrasi API kurir (RajaOngkir, Biteship, dll).
 */
class CourierService
{
    public function isEnabled(): bool
    {
        return (bool) config('services.courier.enabled', false);
    }

    /**
     * @return list<array{code: string, name: string, cost: int, etd: string}>
     */
    public function getRates(string $regencyCode, int $weightGrams): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        return [];
    }

    public function track(string $courier, string $trackingNumber): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        return null;
    }
}
