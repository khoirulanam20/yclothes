<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeFamily;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttributeFamilyController extends Controller
{
    public function index()
    {
        $families = AttributeFamily::withCount('attributes', 'products')->latest()->paginate(10);

        return Inertia::render('Admin/AttributeFamilies/Index', [
            'families' => collect($families->items())->map(fn ($f) => [
                'id' => $f->id,
                'name' => $f->name,
                'attributesCount' => $f->attributes_count,
                'productsCount' => $f->products_count,
            ])->values()->all(),
        ]);
    }

    public function create()
    {
        $attributes = Attribute::orderBy('sort_order')->get();

        return Inertia::render('Admin/AttributeFamilies/Form', [
            'attributes' => $attributes->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'code' => $a->code,
            ])->values()->all(),
            'selectedAttributeIds' => [],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:100',
            'attribute_ids' => 'nullable|array',
            'attribute_ids.*' => 'exists:attributes,id',
        ]);

        $family = AttributeFamily::create(['name' => $validated['name']]);
        $family->attributes()->sync($request->input('attribute_ids', []));

        return redirect()->route('admin.attribute-families.index')
            ->with('success', 'Keluarga atribut berhasil ditambahkan');
    }

    public function edit(AttributeFamily $attributeFamily)
    {
        $attributes = Attribute::orderBy('sort_order')->get();

        return Inertia::render('Admin/AttributeFamilies/Form', [
            'family' => ['id' => $attributeFamily->id, 'name' => $attributeFamily->name],
            'attributes' => $attributes->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'code' => $a->code,
            ])->values()->all(),
            'selectedAttributeIds' => $attributeFamily->attributes()->pluck('attributes.id')->all(),
        ]);
    }

    public function update(Request $request, AttributeFamily $attributeFamily)
    {
        $validated = $request->validate([
            'name' => 'required|max:100',
            'attribute_ids' => 'nullable|array',
            'attribute_ids.*' => 'exists:attributes,id',
        ]);

        $attributeFamily->update(['name' => $validated['name']]);
        $attributeFamily->attributes()->sync($request->input('attribute_ids', []));

        return redirect()->route('admin.attribute-families.index')
            ->with('success', 'Keluarga atribut berhasil diubah');
    }

    public function destroy(AttributeFamily $attributeFamily)
    {
        if ($attributeFamily->products()->count() > 0) {
            return redirect()->route('admin.attribute-families.index')
                ->with('error', 'Keluarga atribut tidak bisa dihapus, masih dipakai produk');
        }

        $attributeFamily->delete();

        return redirect()->route('admin.attribute-families.index')
            ->with('success', 'Keluarga atribut berhasil dihapus');
    }
}
