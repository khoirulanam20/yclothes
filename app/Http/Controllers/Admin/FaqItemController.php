<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FaqCategory;
use App\Models\FaqItem;
use App\Services\HtmlSanitizer;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FaqItemController extends Controller
{
    public function index(FaqCategory $faqCategory)
    {
        $items = $faqCategory->items()->orderBy('sort_order')->paginate(15);

        return Inertia::render('Admin/Faq/Items/Index', [
            'category' => ['id' => $faqCategory->id, 'name' => $faqCategory->name],
            'items' => ModelSerializer::paginated($items, fn ($item) => [
                'id' => $item->id,
                'question' => $item->question,
                'sortOrder' => $item->sort_order,
                'isActive' => (bool) $item->is_active,
            ]),
        ]);
    }

    public function create(FaqCategory $faqCategory)
    {
        return Inertia::render('Admin/Faq/Items/Form', [
            'category' => ['id' => $faqCategory->id, 'name' => $faqCategory->name],
        ]);
    }

    public function store(Request $request, FaqCategory $faqCategory)
    {
        $validated = $this->validateItem($request);
        $validated['category_id'] = $faqCategory->id;
        $validated['answer'] = HtmlSanitizer::clean($validated['answer'] ?? null);
        $validated['is_active'] = $request->boolean('is_active', true);

        FaqItem::create($validated);

        return redirect()->route('admin.faq-categories.items.index', $faqCategory)
            ->with('success', 'Pertanyaan FAQ berhasil ditambahkan.');
    }

    public function edit(FaqCategory $faqCategory, FaqItem $item)
    {
        return Inertia::render('Admin/Faq/Items/Form', [
            'category' => ['id' => $faqCategory->id, 'name' => $faqCategory->name],
            'item' => [
                'id' => $item->id,
                'question' => $item->question,
                'answer' => $item->answer,
                'sortOrder' => $item->sort_order,
                'isActive' => (bool) $item->is_active,
            ],
        ]);
    }

    public function update(Request $request, FaqCategory $faqCategory, FaqItem $item)
    {
        $validated = $this->validateItem($request);
        $validated['answer'] = HtmlSanitizer::clean($validated['answer'] ?? null);
        $validated['is_active'] = $request->boolean('is_active');

        $item->update($validated);

        return redirect()->route('admin.faq-categories.items.index', $faqCategory)
            ->with('success', 'Pertanyaan FAQ berhasil diperbarui.');
    }

    public function destroy(FaqCategory $faqCategory, FaqItem $item)
    {
        $item->delete();

        return redirect()->route('admin.faq-categories.items.index', $faqCategory)
            ->with('success', 'Pertanyaan FAQ berhasil dihapus.');
    }

    private function validateItem(Request $request): array
    {
        return $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
    }
}
