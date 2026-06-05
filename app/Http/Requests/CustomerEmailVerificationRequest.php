<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Auth\EmailVerificationRequest as BaseEmailVerificationRequest;
use Illuminate\Support\Facades\Auth;

class CustomerEmailVerificationRequest extends BaseEmailVerificationRequest
{
    public function user($guard = null)
    {
        return Auth::guard('customer')->user();
    }
}
