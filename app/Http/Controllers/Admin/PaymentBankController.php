<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentBank;
use Illuminate\Http\Request;

class PaymentBankController extends Controller
{
    public function index()
    {
        $banks = PaymentBank::latest()->get();
        return view('admin.payment-banks.index', compact('banks'));
    }

    public function create()
    {
        return view('admin.payment-banks.form', ['bank' => new PaymentBank]);
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
        return view('admin.payment-banks.form', ['bank' => $paymentBank]);
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
