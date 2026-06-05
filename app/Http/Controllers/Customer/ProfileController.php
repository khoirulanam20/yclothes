<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Support\ModelSerializer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit()
    {
        return Inertia::render('Guest/Account/Profile', [
            'customer' => ModelSerializer::customer(Auth::guard('customer')->user()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:customers,email,'.$customer->id,
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            if ($customer->avatar) {
                Storage::disk('public')->delete($customer->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } else {
            unset($validated['avatar']);
        }

        if ($validated['email'] !== $customer->email) {
            $validated['email_verified_at'] = null;
        }

        $customer->update($validated);

        if ($customer->wasChanged('email')) {
            $customer->sendEmailVerificationNotification();

            return redirect()->route('customer.verification.notice')
                ->with('success', 'Profil diperbarui. Silakan verifikasi email baru.');
        }

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
