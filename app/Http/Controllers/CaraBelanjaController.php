<?php

namespace App\Http\Controllers;

class CaraBelanjaController extends Controller
{
    public function __invoke()
    {
        return view('cara-belanja.index');
    }
}
