<?php

namespace App\Services;

use App\Models\ShippingCost;
use App\Support\WilayahCode;
use Illuminate\Validation\ValidationException;

class ShippingOptionsService
{
    public function __construct(
        private CartPricingService $cartPricing,
        private BiteshipService $biteship,
    ) {}

    public function shippingMode(): string
    {
        return setting('shipping_mode', 'manual') === 'biteship' ? 'biteship' : 'manual';
    }

    /**
     * @param  array<string, mixed>  $pricing
     * @return list<array{optionKey: string, courierCode: string, courierName: string, cost: int, etd?: ?string, shippingCostId?: int, courierServiceCode?: string, courierServiceName?: string}>
     */
    public function optionsForAddress(string $regencyCode, ?string $postalCode, array $pricing): array
    {
        if ($this->shippingMode() === 'biteship') {
            return $this->biteshipOptions($postalCode ?? '', $pricing);
        }

        return $this->manualOptions($regencyCode, $pricing);
    }

    /**
     * @param  array<string, mixed>  $pricing
     * @return list<array{optionKey: string, courierCode: string, courierName: string, cost: int, etd?: ?string, shippingCostId?: int, courierServiceCode?: string, courierServiceName?: string}>
     */
    private function manualOptions(string $regencyCode, array $pricing): array
    {
        $normalized = WilayahCode::normalize($regencyCode) ?? $regencyCode;

        $costs = ShippingCost::query()
            ->where('is_active', true)
            ->whereNotNull('courier_code')
            ->whereNotNull('regency_code')
            ->get()
            ->filter(fn (ShippingCost $cost) => WilayahCode::equals($cost->regency_code, $normalized));

        return $costs->map(function (ShippingCost $cost) use ($pricing) {
            $amount = $this->cartPricing->calculateShipping($cost, $pricing['total_weight'], $pricing['free_shipping']);

            return [
                'optionKey' => $cost->courier_code.'|manual|'.$cost->id,
                'courierCode' => $cost->courier_code,
                'courierName' => $cost->courier_name ?? $cost->courier_code,
                'cost' => $amount,
                'shippingCostId' => $cost->id,
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $pricing
     * @return list<array{optionKey: string, courierCode: string, courierName: string, cost: int, etd?: ?string, shippingCostId?: int, courierServiceCode?: string, courierServiceName?: string}>
     */
    private function biteshipOptions(string $postalCode, array $pricing): array
    {
        if (! $this->biteship->isConfigured() || $postalCode === '') {
            return [];
        }

        if ($pricing['free_shipping']) {
            return $this->freeShippingCourierOptions();
        }

        $rates = $this->biteship->getRates($postalCode, $pricing['total_weight']);

        return $this->biteship->formatRatesForCheckout($rates);
    }

    /**
     * @return list<array{optionKey: string, courierCode: string, courierName: string, cost: int}>
     */
    private function freeShippingCourierOptions(): array
    {
        $couriers = config('couriers.list', []);
        $active = array_filter(array_map('trim', explode(',', $this->biteship->activeCouriersCsv())));

        return collect($couriers)
            ->filter(fn (array $c) => $active === [] || in_array($c['code'], $active, true))
            ->map(fn (array $c) => [
                'optionKey' => $c['code'].'|standard',
                'courierCode' => $c['code'],
                'courierName' => $c['name'],
                'courierServiceCode' => 'standard',
                'courierServiceName' => 'Standard',
                'cost' => 0,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $address
     * @param  array<string, mixed>  $pricing
     * @return array{
     *   shipping_method: string,
     *   shipping_provider: string,
     *   courier: string,
     *   courier_service: ?string,
     *   courier_service_code: ?string,
     *   shipping_cost: int,
     *   shipping_etd: ?string,
     *   shipping_city: string,
     *   shipping_cost_record: ?ShippingCost
     * }
     */
    public function resolveForCheckout(string $courierCode, array $address, array $pricing, ?string $courierServiceCode = null): array
    {
        $courierCode = strtolower(trim($courierCode));
        $courierServiceCode = $courierServiceCode !== null ? strtolower(trim($courierServiceCode)) : null;

        $options = $this->optionsForAddress(
            (string) ($address['regency_code'] ?? ''),
            (string) ($address['postal_code'] ?? ''),
            $pricing,
        );

        $match = collect($options)->first(function (array $opt) use ($courierCode, $courierServiceCode) {
            if (strtolower($opt['courierCode']) !== $courierCode) {
                return false;
            }

            if ($courierServiceCode === null || $courierServiceCode === '') {
                return true;
            }

            return strtolower((string) ($opt['courierServiceCode'] ?? '')) === $courierServiceCode;
        });

        if (! $match) {
            throw ValidationException::withMessages([
                'courier_code' => 'Layanan pengiriman tidak tersedia untuk alamat ini.',
            ]);
        }

        if ($this->shippingMode() === 'biteship') {
            return [
                'shipping_method' => 'biteship',
                'shipping_provider' => 'biteship',
                'courier' => $match['courierName'],
                'courier_service' => $match['courierServiceName'] ?? null,
                'courier_service_code' => $match['courierServiceCode'] ?? null,
                'shipping_cost' => $match['cost'],
                'shipping_etd' => $match['etd'] ?? null,
                'shipping_city' => (string) ($address['regency_name'] ?? ''),
                'shipping_cost_record' => null,
            ];
        }

        $record = isset($match['shippingCostId'])
            ? ShippingCost::find($match['shippingCostId'])
            : null;

        if (! $record) {
            throw ValidationException::withMessages([
                'courier_code' => 'Tarif ongkir tidak ditemukan.',
            ]);
        }

        $cost = $this->cartPricing->calculateShipping($record, $pricing['total_weight'], $pricing['free_shipping']);

        return [
            'shipping_method' => 'manual',
            'shipping_provider' => 'manual',
            'courier' => $record->courier_name ?? $record->courier_code,
            'courier_service' => null,
            'courier_service_code' => null,
            'shipping_cost' => $cost,
            'shipping_etd' => null,
            'shipping_city' => $record->regency_name ?? $record->city_name,
            'shipping_cost_record' => $record,
        ];
    }
}
