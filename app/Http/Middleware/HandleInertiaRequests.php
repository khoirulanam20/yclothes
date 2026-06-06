<?php

namespace App\Http\Middleware;

use App\Support\InertiaData;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => InertiaData::auth(),
            'flash' => InertiaData::flash(),
            'cartCount' => InertiaData::cartCount(),
            'theme' => InertiaData::theme(),
            'navigation' => InertiaData::navigation(),
            'categories' => InertiaData::categories(),
            'promotionPopup' => InertiaData::promotionPopup(),
            'gdpr' => InertiaData::gdpr(),
        ];
    }
}
