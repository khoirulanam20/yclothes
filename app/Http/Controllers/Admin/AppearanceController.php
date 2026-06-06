<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AppearanceController extends Controller
{
    public function edit(): RedirectResponse
    {
        return redirect()->route('admin.integrations.edit');
    }

    public function update(Request $request): RedirectResponse
    {
        return redirect()->route('admin.integrations.edit');
    }
}
