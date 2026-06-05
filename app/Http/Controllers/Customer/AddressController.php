<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Support\ModelSerializer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AddressController extends Controller
{
    public function index()
    {
        $addresses = Auth::guard('customer')->user()->addresses()->latest()->get();

        return Inertia::render('Guest/Account/Addresses', [
            'addresses' => ModelSerializer::collection($addresses, [ModelSerializer::class, 'customerAddress']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Guest/Account/AddressForm', [
            'address' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        $validated = $this->validateAddress($request);

        if ($request->boolean('is_default') || $customer->addresses()->count() === 0) {
            $customer->addresses()->update(['is_default' => false]);
            $validated['is_default'] = true;
        }

        $validated['city'] = $validated['regency_name'];
        $validated['province'] = $validated['province_name'];

        $customer->addresses()->create($validated);

        return redirect()->route('customer.addresses.index')->with('success', 'Alamat berhasil ditambahkan.');
    }

    public function edit(CustomerAddress $address)
    {
        $this->authorizeAddress($address);

        return Inertia::render('Guest/Account/AddressForm', [
            'address' => ModelSerializer::customerAddressCheckout($address),
        ]);
    }

    public function update(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->authorizeAddress($address);
        $validated = $this->validateAddress($request);

        if ($request->boolean('is_default')) {
            Auth::guard('customer')->user()->addresses()->update(['is_default' => false]);
            $validated['is_default'] = true;
        } else {
            $validated['is_default'] = $address->is_default;
        }

        $validated['city'] = $validated['regency_name'];
        $validated['province'] = $validated['province_name'];

        $address->update($validated);

        return redirect()->route('customer.addresses.index')->with('success', 'Alamat berhasil diperbarui.');
    }

    public function destroy(CustomerAddress $address): RedirectResponse
    {
        $this->authorizeAddress($address);
        $address->delete();

        return redirect()->route('customer.addresses.index')->with('success', 'Alamat berhasil dihapus.');
    }

    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'label' => 'required|string|max:50',
            'recipient_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'street_address' => 'required|string|max:1000',
            'province_code' => 'required|string|max:10',
            'province_name' => 'required|string|max:100',
            'regency_code' => 'required|string|max:10',
            'regency_name' => 'required|string|max:100',
            'district_code' => 'required|string|max:10',
            'district_name' => 'required|string|max:100',
            'village_code' => 'nullable|string|max:20',
            'village_name' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'type' => 'required|in:shipping,billing,both',
            'is_default' => 'nullable|boolean',
        ]);
    }

    private function authorizeAddress(CustomerAddress $address): void
    {
        abort_unless($address->customer_id === Auth::guard('customer')->id(), 403);
    }
}
