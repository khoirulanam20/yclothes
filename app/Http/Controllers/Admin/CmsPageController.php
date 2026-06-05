<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Services\CmsLayoutService;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CmsPageController extends Controller
{
    public function __construct(private CmsLayoutService $layoutService) {}

    public function index()
    {
        $pages = CmsPage::latest()->paginate(15);

        return Inertia::render('Admin/Cms/Index', [
            'pages' => ModelSerializer::paginated($pages, [ModelSerializer::class, 'cmsPage']),
        ]);
    }

    public function newBuilder()
    {
        return Inertia::render('Admin/Cms/Builder', [
            'page' => null,
        ]);
    }

    public function storeBuilder(Request $request)
    {
        $validated = $this->validateBuilder($request);
        $layout = $this->layoutService->parseLayoutJson($validated['layout_json']);

        $page = CmsPage::create([
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?: Str::slug($validated['title']),
            'status' => $validated['status'],
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'layout_json' => $layout,
            'layout_version' => 'puck-1',
        ]);

        return redirect()
            ->route('admin.cms-pages.builder', $page)
            ->with('success', 'Halaman berhasil dibuat.');
    }

    public function destroy(CmsPage $cmsPage)
    {
        if ($cmsPage->banner_image) {
            Storage::disk('public')->delete($cmsPage->banner_image);
        }

        $cmsPage->delete();

        return redirect()->route('admin.cms-pages.index')->with('success', 'Halaman berhasil dihapus.');
    }

    public function builder(CmsPage $cmsPage)
    {
        $cmsPage = $this->layoutService->migrateLegacyIfNeeded($cmsPage);

        return Inertia::render('Admin/Cms/Builder', [
            'page' => ModelSerializer::cmsPage($cmsPage),
        ]);
    }

    public function saveBuilder(Request $request, CmsPage $cmsPage)
    {
        $validated = $this->validateBuilder($request, $cmsPage->id);
        $layout = $this->layoutService->parseLayoutJson($validated['layout_json']);

        if (! isset($layout['root']['props']['pageTitle'])) {
            $layout['root']['props']['pageTitle'] = $validated['title'];
        }

        $cmsPage->update([
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?: Str::slug($validated['title']),
            'status' => $validated['status'],
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'layout_json' => $layout,
            'layout_version' => 'puck-1',
        ]);

        return redirect()
            ->route('admin.cms-pages.builder', $cmsPage)
            ->with('success', 'Halaman berhasil disimpan.');
    }

    public function preview(CmsPage $cmsPage)
    {
        return redirect()->route('pages.show', ['slug' => $cmsPage->slug, 'preview' => 1]);
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $path = $request->file('image')->store('cms', 'public');

        return response()->json([
            'path' => $path,
            'url' => storage_url($path),
        ]);
    }

    private function validateBuilder(Request $request, ?int $ignoreId = null): array
    {
        $slugRule = 'nullable|max:255|unique:cms_pages,slug';
        if ($ignoreId) {
            $slugRule .= ','.$ignoreId;
        }

        return $request->validate([
            'title' => 'required|string|max:255',
            'slug' => $slugRule,
            'status' => 'required|in:draft,published',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1000',
            'layout_json' => 'required|string',
        ]);
    }
}
