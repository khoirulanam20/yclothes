<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CategoryTreeService;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function __construct(private CategoryTreeService $categoryTree) {}

    public function index()
    {
        $roots = Category::tree();
        $this->categoryTree->loadCounts($roots);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $this->categoryTree->flattenForIndex($roots),
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Admin/Categories/Form', [
            'parentOptions' => $this->categoryTree->parentOptions(),
            'defaultParentId' => $request->integer('parent_id') ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'slug' => 'nullable|max:255|unique:categories',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'order' => 'nullable|integer|min:0',
        ]);

        $this->categoryTree->validateParent(null, $validated['parent_id'] ?? null);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        Category::create($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil ditambahkan');
    }

    public function edit(Category $category)
    {
        return Inertia::render('Admin/Categories/Form', [
            'category' => ModelSerializer::category($category),
            'parentOptions' => $this->categoryTree->parentOptions($category),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'slug' => 'nullable|max:255|unique:categories,slug,'.$category->id,
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_image' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        $this->categoryTree->validateParent($category, $validated['parent_id'] ?? null);

        if ($request->hasFile('image')) {
            if ($category->image && ! Str::startsWith($category->image, 'http')) {
                Storage::disk('public')->delete($category->image);
            }
            $validated['image'] = $request->file('image')->store('categories', 'public');
        } elseif ($request->boolean('remove_image')) {
            if ($category->image && ! Str::startsWith($category->image, 'http')) {
                Storage::disk('public')->delete($category->image);
            }
            $validated['image'] = null;
        } else {
            unset($validated['image']);
        }

        $category->update($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil diubah');
    }

    public function destroy(Category $category)
    {
        if ($category->children()->exists()) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Kategori tidak bisa dihapus, masih memiliki sub-kategori');
        }

        if ($category->products()->count() > 0) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Kategori tidak bisa dihapus, masih memiliki produk');
        }

        if ($category->image && ! Str::startsWith($category->image, 'http')) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dihapus');
    }
}
