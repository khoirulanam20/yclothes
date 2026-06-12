<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Controller as BaseController;
use App\Models\User;
use Illuminate\Http\Request;

abstract class Controller extends BaseController
{
    protected function posUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
