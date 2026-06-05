<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PageController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $page = CmsPage::where('slug', $slug)->firstOrFail();

        if (! $page->isPublished()) {
            $isAdminPreview = $request->user('web')?->is_admin && $request->boolean('preview');
            abort_unless($isAdminPreview, 404);
        }

        return Inertia::render('Guest/Cms/Show', [
            'page' => ModelSerializer::cmsPage($page),
        ]);
    }
}
