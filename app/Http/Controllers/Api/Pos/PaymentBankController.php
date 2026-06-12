<?php

namespace App\Http\Controllers\Api\Pos;

use App\Models\PaymentBank;
use App\Support\Api\PosApiResponse;
use Illuminate\Http\Request;

class PaymentBankController extends Controller
{
    public function index()
    {
        $banks = PaymentBank::query()
            ->orderBy('bank_name')
            ->get();

        return PosApiResponse::success(
            $banks->map(fn (PaymentBank $bank) => $this->serialize($bank))->values()->all(),
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
            'account_name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $bank = PaymentBank::create([
            'bank_name' => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'account_name' => $validated['account_name'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return PosApiResponse::success($this->serialize($bank), [], 201);
    }

    public function update(Request $request, PaymentBank $paymentBank)
    {
        $validated = $request->validate([
            'bank_name' => ['sometimes', 'required', 'string', 'max:255'],
            'account_number' => ['sometimes', 'required', 'string', 'max:100'],
            'account_name' => ['sometimes', 'required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (array_key_exists('is_active', $validated) || $request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $paymentBank->update($validated);

        return PosApiResponse::success($this->serialize($paymentBank->fresh()));
    }

    public function destroy(PaymentBank $paymentBank)
    {
        $paymentBank->delete();

        return PosApiResponse::success(['deleted' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(PaymentBank $bank): array
    {
        return [
            'id' => $bank->id,
            'bankName' => $bank->bank_name,
            'accountNumber' => $bank->account_number,
            'accountName' => $bank->account_name,
            'isActive' => $bank->is_active,
        ];
    }
}
