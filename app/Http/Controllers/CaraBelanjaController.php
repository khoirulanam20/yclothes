<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class CaraBelanjaController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('pages.show', ['slug' => 'cara-belanja'], 301);
    }
}
