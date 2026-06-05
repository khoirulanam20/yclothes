<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NavigationItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NavigationController extends Controller
{
    public function index()
    {
        $headerItems = NavigationItem::forMenu('header')->roots()->with('children')->orderBy('sort_order')->get();
        $footerItems = NavigationItem::forMenu('footer')->roots()->with('children')->orderBy('sort_order')->get();

        $items = array_merge(
            $this->flattenNavItems($headerItems),
            $this->flattenNavItems($footerItems),
        );

        return Inertia::render('Admin/Navigation/Index', [
            'items' => $items,
        ]);
    }

    public function create()
    {
        $parents = NavigationItem::roots()->orderBy('menu')->orderBy('sort_order')->get();

        return Inertia::render('Admin/Navigation/Form', [
            'parents' => $parents->map(fn ($p) => ['id' => $p->id, 'label' => $p->label])->values()->all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateItem($request);
        $validated['is_active'] = $request->boolean('is_active', true);

        NavigationItem::create($validated);

        return redirect()->route('admin.navigation.index')->with('success', 'Menu berhasil ditambahkan.');
    }

    public function edit(NavigationItem $navigation)
    {
        $parents = NavigationItem::roots()
            ->where('id', '!=', $navigation->id)
            ->where('menu', $navigation->menu)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Admin/Navigation/Form', [
            'item' => $this->navItem($navigation),
            'parents' => $parents->map(fn ($p) => ['id' => $p->id, 'label' => $p->label])->values()->all(),
        ]);
    }

    public function update(Request $request, NavigationItem $navigation)
    {
        $validated = $this->validateItem($request, $navigation->id);
        $validated['is_active'] = $request->boolean('is_active');

        if (! empty($validated['parent_id'])) {
            $parent = NavigationItem::find($validated['parent_id']);
            if ($parent && $parent->parent_id) {
                return back()->withErrors(['parent_id' => 'Maksimal 2 level menu.'])->withInput();
            }
        }

        $navigation->update($validated);

        return redirect()->route('admin.navigation.index')->with('success', 'Menu berhasil diperbarui.');
    }

    public function destroy(NavigationItem $navigation)
    {
        $navigation->children()->delete();
        $navigation->delete();

        return redirect()->route('admin.navigation.index')->with('success', 'Menu berhasil dihapus.');
    }

    private function validateItem(Request $request, ?int $ignoreId = null): array
    {
        $parentRule = 'nullable|exists:navigation_items,id';
        if ($ignoreId) {
            $parentRule .= '|not_in:'.$ignoreId;
        }

        return $request->validate([
            'menu' => 'required|in:header,footer',
            'parent_id' => $parentRule,
            'label' => 'required|string|max:255',
            'url' => 'required|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
    }

    private function navItem(NavigationItem $item): array
    {
        return [
            'id' => $item->id,
            'menu' => $item->menu,
            'label' => $item->label,
            'url' => $item->url,
            'sortOrder' => $item->sort_order,
            'isActive' => (bool) $item->is_active,
            'parentId' => $item->parent_id,
        ];
    }

    private function flattenNavItems($roots): array
    {
        $items = [];
        foreach ($roots as $root) {
            $items[] = $this->navItem($root);
            foreach ($root->children as $child) {
                $items[] = $this->navItem($child);
            }
        }

        return $items;
    }
}
