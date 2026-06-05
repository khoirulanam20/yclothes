<?php

namespace App\Http\Controllers\Customer\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return Inertia::render('Guest/Account/ForgotPassword');
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::broker('customers')->sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
