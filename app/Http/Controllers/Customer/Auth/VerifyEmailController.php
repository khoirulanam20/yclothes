<?php

namespace App\Http\Controllers\Customer\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerEmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VerifyEmailController extends Controller
{
    public function notice()
    {
        return Inertia::render('Guest/Account/VerifyEmail');
    }

    public function verify(CustomerEmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route('customer.profile.edit')
            ->with('success', 'Email berhasil diverifikasi.');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user('customer')->hasVerifiedEmail()) {
            return redirect()->route('customer.profile.edit');
        }

        $request->user('customer')->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
