<?php

namespace App\Http\Controllers\Api\Pos;

use App\Models\PaymentBank;
use App\Models\Warehouse;
use App\Support\Api\PosApiResponse;

class BootstrapController extends Controller
{
    public function __invoke()
    {
        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'address', 'city']);

        $banks = PaymentBank::query()
            ->where('is_active', true)
            ->orderBy('bank_name')
            ->get(['id', 'bank_name', 'account_number', 'account_name']);

        return PosApiResponse::success([
            'store' => [
                'name' => setting('store_name', config('app.name')),
                'address' => setting('store_address'),
                'phone' => setting('store_phone'),
                'email' => setting('store_email'),
            ],
            'tax' => [
                'included' => setting_bool('tax_included'),
                'enabled' => setting_bool('tax_enabled'),
            ],
            'warehouses' => $warehouses->map(fn (Warehouse $warehouse) => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'address' => $warehouse->address,
                'city' => $warehouse->city,
            ])->values()->all(),
            'paymentBanks' => $banks->map(fn (PaymentBank $bank) => [
                'id' => $bank->id,
                'bankName' => $bank->bank_name,
                'accountNumber' => $bank->account_number,
                'accountName' => $bank->account_name,
            ])->values()->all(),
        ]);
    }
}
