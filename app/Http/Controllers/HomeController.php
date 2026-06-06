<?php

namespace App\Http\Controllers;

use App\Services\HomepageLayoutService;
use App\Services\PromotionEngine;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __construct(
        private PromotionEngine $promotionEngine,
        private HomepageLayoutService $homepageLayout,
    ) {}

    public function index()
    {
        return Inertia::render('Guest/Home', [
            'sections' => $this->homepageLayout->resolveSections($this->promotionEngine),
        ]);
    }
}
