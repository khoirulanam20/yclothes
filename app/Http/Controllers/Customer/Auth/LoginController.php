<?php

namespace App\Http\Controllers\Customer\Auth;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return Inertia::render('Guest/Account/Login');
    }

    public function login(Request $request, CartService $cartService): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $cartService->backupToGuest();

        if (Auth::guard('customer')->attempt(
            $credentials + ['is_active' => true],
            $request->boolean('remember')
        )) {
            $request->session()->regenerate();

            $customer = Auth::guard('customer')->user();
            $customer->update(['last_login_at' => now()]);

            $cartService->mergeFromSession('guest_cart');

            return redirect()->intended(route('customer.profile.edit'));
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
