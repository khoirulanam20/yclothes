<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FaqCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FaqCategoryController extends Controller
{
    public function index()
    {
        $categories = FaqCategory::withCount('items')->orderBy('sort_order')->get();

        return Inertia::render('Admin/Faq/Categories/Index', [
            'categories' => $categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'sortOrder' => $c->sort_order,
                'itemsCount' => $c->items_count,
            ])->values()->all(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Faq/Categories/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        FaqCategory::create($validated);

        return redirect()->route('admin.faq-categories.index')->with('success', 'Kategori FAQ berhasil ditambahkan.');
    }

    public function edit(FaqCategory $faqCategory)
    {
        return Inertia::render('Admin/Faq/Categories/Form', [
            'category' => [
                'id' => $faqCategory->id,
                'name' => $faqCategory->name,
                'sortOrder' => $faqCategory->sort_order,
            ],
        ]);
    }

    public function update(Request $request, FaqCategory $faqCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $faqCategory->update($validated);

        return redirect()->route('admin.faq-categories.index')->with('success', 'Kategori FAQ berhasil diperbarui.');
    }

    public function destroy(FaqCategory $faqCategory)
    {
        $faqCategory->delete();

        return redirect()->route('admin.faq-categories.index')->with('success', 'Kategori FAQ berhasil dihapus.');
    }
}
