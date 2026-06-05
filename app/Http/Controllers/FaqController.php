<?php

namespace App\Http\Controllers;

use App\Models\FaqCategory;
use Inertia\Inertia;

class FaqController extends Controller
{
    public function index()
    {
        $categories = FaqCategory::with(['activeItems'])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn ($cat) => $cat->activeItems->isNotEmpty())
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'items' => $cat->activeItems->map(fn ($item) => [
                    'id' => $item->id,
                    'question' => $item->question,
                    'answer' => $item->answer,
                ])->values()->all(),
            ])
            ->values();

        return Inertia::render('Guest/Faq/Index', [
            'faqCategories' => $categories,
        ]);
    }
}
