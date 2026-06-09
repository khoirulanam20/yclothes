<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BadgePreset;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Slider;
use App\Services\CategoryTreeService;
use App\Services\HomepageLayoutService;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class HomepageController extends Controller
{
    public function __construct(private HomepageLayoutService $layoutService) {}

    public function edit()
    {
        $treeService = app(CategoryTreeService::class);
        $roots = Category::whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('order')->with(['children' => fn ($q) => $q->orderBy('order')])])
            ->orderBy('order')
            ->get();
        $categories = $treeService->flattenForSelect($roots);
        $products = Product::where('is_active', true)->orderBy('name')->take(100)->get(['id', 'name', 'sku']);
        $sliders = Slider::orderBy('sort_order')->get();

        return Inertia::render('Admin/Homepage/Builder', [
            'layout' => $this->layoutService->getLayout(),
            'sectionTypes' => $this->layoutService->sectionTypeOptions(),
            'sliders' => ModelSerializer::collection($sliders, [ModelSerializer::class, 'slider']),
            'categories' => array_map(
                fn (array $c) => ['id' => $c['id'], 'name' => $c['name'], 'depth' => $c['depth']],
                $categories,
            ),
            'products' => $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku])->values()->all(),
            'badgePresets' => BadgePreset::options(),
        ]);
    }

    public function searchProducts(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $products = Product::where('is_active', true)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->take(30)
            ->get(['id', 'name', 'sku', 'price', 'sale_price']);

        return response()->json([
            'products' => $products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => (int) $p->price,
                'salePrice' => $p->sale_price !== null ? (int) $p->sale_price : null,
            ])->values()->all(),
        ]);
    }

    public function uploadBannerImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        $path = $request->file('image')->store('homepage-banners', 'public');

        return response()->json([
            'path' => $path,
            'url' => storage_url($path),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'layout' => 'required|array',
            'layout.*.id' => 'required|string|max:100',
            'layout.*.type' => 'required|string|in:'.implode(',', HomepageLayoutService::SECTION_TYPES),
            'layout.*.enabled' => 'nullable|boolean',
            'layout.*.props' => 'nullable|array',
        ]);

        $this->layoutService->saveLayout($validated['layout']);

        return redirect()->route('admin.homepage.edit')->with('success', 'Layout halaman utama berhasil disimpan.');
    }

    public function storeSlider(Request $request)
    {
        $validated = $this->validateSlider($request);
        $validated['image'] = $request->file('image')->store('sliders', 'public');
        $validated['is_active'] = $request->boolean('is_active', true);

        Slider::create($validated);

        return back()->with('success', 'Slide berhasil ditambahkan.');
    }

    public function updateSlider(Request $request, Slider $slider)
    {
        $validated = $this->validateSlider($request, false);

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($slider->image);
            $validated['image'] = $request->file('image')->store('sliders', 'public');
        } else {
            unset($validated['image']);
        }

        $validated['is_active'] = $request->boolean('is_active');
        $slider->update($validated);

        return back()->with('success', 'Slide berhasil diperbarui.');
    }

    public function destroySlider(Slider $slider)
    {
        Storage::disk('public')->delete($slider->image);
        $slider->delete();

        return back()->with('success', 'Slide berhasil dihapus.');
    }

    private function validateSlider(Request $request, bool $requireImage = true): array
    {
        return $request->validate([
            'title' => 'nullable|string|max:255',
            'image' => ($requireImage ? 'required' : 'nullable').'|image|mimes:jpeg,png,jpg,webp|max:4096',
            'link_url' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
    }
}
