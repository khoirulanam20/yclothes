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
        $sections = $this->homepageLayout->resolveSections($this->promotionEngine);

        // #region agent log
        $debugLogPath = base_path('.cursor/debug-227592.log');
        @file_put_contents($debugLogPath, json_encode([
            'sessionId' => '227592',
            'hypothesisId' => 'H1-H5',
            'location' => 'HomeController.php:index',
            'message' => 'homepage sections resolved',
            'data' => [
                'sectionTypes' => array_map(fn (array $s) => $s['type'] ?? 'unknown', $sections),
                'promotionBanner' => collect($sections)->firstWhere('type', 'promotion_banner'),
            ],
            'timestamp' => (int) (microtime(true) * 1000),
        ])."\n", FILE_APPEND);
        // #endregion

        return Inertia::render('Guest/Home', [
            'sections' => $sections,
        ]);
    }
}
