<?php

namespace App\Services;

use App\Models\PaymentBank;

class PaymentMethodService
{
    public function isBankTransferEnabled(): bool
    {
        return setting_bool('payment_bank_transfer_enabled', true);
    }

    public function isQrisEnabled(): bool
    {
        return setting_bool('payment_qris_enabled');
    }

    public function isMidtransEnabled(): bool
    {
        return setting_bool('payment_midtrans_enabled');
    }

    public function isDokuEnabled(): bool
    {
        return setting_bool('payment_doku_enabled');
    }

    public function isKlikQrisEnabled(): bool
    {
        return setting_bool('payment_klikqris_enabled');
    }

    public function isCodEnabled(): bool
    {
        return setting_bool('payment_cod_enabled');
    }

    public function isBankTransferAvailable(): bool
    {
        return $this->isBankTransferEnabled()
            && PaymentBank::where('is_active', true)->exists();
    }

    public function isQrisAvailable(): bool
    {
        return $this->isQrisEnabled() && filled(setting('qris_image'));
    }

    public function isMidtransAvailable(): bool
    {
        return $this->isMidtransEnabled() && MidtransService::hasCredentials();
    }

    public function isDokuAvailable(): bool
    {
        return $this->isDokuEnabled() && DokuService::hasCredentials();
    }

    public function isKlikQrisAvailable(): bool
    {
        return $this->isKlikQrisEnabled() && KlikQrisService::hasCredentials();
    }

    public function isCodAvailable(bool $hasPhysicalProducts = true): bool
    {
        return $this->isCodEnabled() && $hasPhysicalProducts;
    }

    public function isCod(string $paymentMethod): bool
    {
        return $paymentMethod === 'cod';
    }

    public function isDeferredPayment(string $paymentMethod): bool
    {
        return $this->isCod($paymentMethod);
    }

    /** @return list<string> */
    public function allowedCheckoutValues(bool $hasPhysicalProducts = true): array
    {
        $methods = [];

        if ($this->isBankTransferAvailable()) {
            foreach (PaymentBank::where('is_active', true)->pluck('id') as $id) {
                $methods[] = 'bank_'.$id;
            }
        }

        if ($this->isQrisAvailable()) {
            $methods[] = 'qris';
        }

        if ($this->isMidtransAvailable()) {
            $methods[] = 'midtrans';
        }

        if ($this->isDokuAvailable()) {
            $methods[] = 'doku';
        }

        if ($this->isKlikQrisAvailable()) {
            $methods[] = 'klikqris';
        }

        if ($this->isCodAvailable($hasPhysicalProducts)) {
            $methods[] = 'cod';
        }

        return $methods;
    }

    /** @return list<array{id: string, label: string, type: string, banks?: list<array<string, mixed>>}> */
    public function availableForCheckout(bool $hasPhysicalProducts = true): array
    {
        $options = [];

        if ($this->isBankTransferAvailable()) {
            $banks = PaymentBank::where('is_active', true)->get()->map(fn (PaymentBank $bank) => [
                'id' => $bank->id,
                'bankName' => $bank->bank_name,
                'accountNumber' => $bank->account_number,
                'accountName' => $bank->account_name,
            ])->values()->all();

            $options[] = [
                'id' => 'bank_transfer',
                'label' => 'Transfer Bank',
                'type' => 'manual',
                'banks' => $banks,
            ];
        }

        if ($this->isQrisAvailable()) {
            $options[] = [
                'id' => 'qris',
                'label' => 'QRIS',
                'type' => 'manual',
            ];
        }

        if ($this->isCodAvailable($hasPhysicalProducts)) {
            $options[] = [
                'id' => 'cod',
                'label' => 'Bayar di Tempat (COD)',
                'type' => 'cod',
            ];
        }

        if ($this->isMidtransAvailable()) {
            $options[] = [
                'id' => 'midtrans',
                'label' => 'Midtrans (Online)',
                'type' => 'gateway',
            ];
        }

        if ($this->isDokuAvailable()) {
            $options[] = [
                'id' => 'doku',
                'label' => 'DOKU (Online)',
                'type' => 'gateway',
            ];
        }

        if ($this->isKlikQrisAvailable()) {
            $options[] = [
                'id' => 'klikqris',
                'label' => 'KlikQRIS (QRIS Online)',
                'type' => 'gateway',
            ];
        }

        return $options;
    }

    /** @return list<array{id: string, label: string, type: string, comingSoon: true}> */
    public function comingSoonForCheckout(bool $hasPhysicalProducts = true): array
    {
        if ($this->isCodEnabled() || ! $hasPhysicalProducts) {
            return [];
        }

        return [[
            'id' => 'cod',
            'label' => 'Bayar di Tempat (COD)',
            'type' => 'cod',
            'comingSoon' => true,
        ]];
    }

    public function qrisSettings(): array
    {
        return [
            'imageUrl' => storage_url(setting('qris_image')),
            'merchantName' => setting('qris_merchant_name'),
            'instructions' => setting('qris_instructions', 'Scan QRIS di bawah, bayar sesuai nominal, lalu konfirmasi pembayaran.'),
        ];
    }

    public function codSettings(): array
    {
        return [
            'instructions' => setting('cod_instructions', 'Bayar tunai saat kurir mengantar pesanan. Pastikan nominal sesuai total pesanan.'),
        ];
    }

    public function usesManualConfirmation(string $paymentMethod): bool
    {
        return in_array($paymentMethod, ['bank_transfer', 'qris'], true);
    }
}
