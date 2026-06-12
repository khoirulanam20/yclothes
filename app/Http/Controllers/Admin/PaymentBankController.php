<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentBank;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentBankController extends Controller
{
    public function index()
    {
        $banks = PaymentBank::latest()->paginate(15);

        return Inertia::render('Admin/PaymentBanks/Index', [
            'banks' => ModelSerializer::paginated($banks, [ModelSerializer::class, 'paymentBank']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/PaymentBanks/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:100',
            'account_name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        PaymentBank::create($validated);

        return redirect()->route('admin.payment-banks.index')->with('success', 'Rekening berhasil ditambahkan');
    }

    public function edit(PaymentBank $paymentBank)
    {
        return Inertia::render('Admin/PaymentBanks/Form', [
            'bank' => ModelSerializer::paymentBank($paymentBank),
        ]);
    }

    public function update(Request $request, PaymentBank $paymentBank)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:100',
            'account_name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $paymentBank->update($validated);

        return redirect()->route('admin.payment-banks.index')->with('success', 'Rekening berhasil diubah');
    }

    public function destroy(PaymentBank $paymentBank)
    {
        $paymentBank->delete();

        return redirect()->route('admin.payment-banks.index')->with('success', 'Rekening berhasil dihapus');
    }
}
