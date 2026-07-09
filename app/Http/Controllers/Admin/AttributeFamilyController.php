<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttributeType;
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
        return Inertia::render('Admin/AttributeFamilies/Form', [
            'attributes' => $this->attributeOptions(),
            'selectedAttributeIds' => [],
            'variantAxisIds' => [],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:100',
            'attribute_ids' => 'nullable|array',
            'attribute_ids.*' => 'exists:attributes,id',
            'variant_axis_ids' => 'nullable|array',
            'variant_axis_ids.*' => 'exists:attributes,id',
        ]);

        $family = AttributeFamily::create(['name' => $validated['name']]);
        $this->syncAttributes($family, $request->input('attribute_ids', []), $request->input('variant_axis_ids', []));

        return redirect()->route('admin.attribute-families.index')
            ->with('success', 'Keluarga atribut berhasil ditambahkan');
    }

    public function edit(AttributeFamily $attributeFamily)
    {
        $attributeFamily->load('attributes');

        return Inertia::render('Admin/AttributeFamilies/Form', [
            'family' => ['id' => $attributeFamily->id, 'name' => $attributeFamily->name],
            'attributes' => $this->attributeOptions(),
            'selectedAttributeIds' => $attributeFamily->attributes->pluck('id')->all(),
            'variantAxisIds' => $attributeFamily->attributes
                ->filter(fn ($a) => (bool) $a->pivot->is_variant_axis)
                ->pluck('id')
                ->all(),
        ]);
    }

    public function update(Request $request, AttributeFamily $attributeFamily)
    {
        $validated = $request->validate([
            'name' => 'required|max:100',
            'attribute_ids' => 'nullable|array',
            'attribute_ids.*' => 'exists:attributes,id',
            'variant_axis_ids' => 'nullable|array',
            'variant_axis_ids.*' => 'exists:attributes,id',
        ]);

        $attributeFamily->update(['name' => $validated['name']]);
        $this->syncAttributes(
            $attributeFamily,
            $request->input('attribute_ids', []),
            $request->input('variant_axis_ids', []),
        );

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

    private function attributeOptions(): array
    {
        return Attribute::orderBy('sort_order')->get()->map(fn ($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'code' => $a->code,
            'type' => $a->type?->value ?? $a->type,
            'canBeVariantAxis' => $this->canBeVariantAxis($a),
        ])->values()->all();
    }

    private function canBeVariantAxis(Attribute $attribute): bool
    {
        return $attribute->type === AttributeType::Multiselect
            || in_array($attribute->code, ['size', 'color'], true);
    }

    private function syncAttributes(AttributeFamily $family, array $attributeIds, array $variantAxisIds): void
    {
        $variantAxisIds = array_values(array_intersect($variantAxisIds, $attributeIds));
        $allowedVariantIds = Attribute::query()
            ->whereIn('id', $variantAxisIds)
            ->get()
            ->filter(fn (Attribute $a) => $this->canBeVariantAxis($a))
            ->pluck('id')
            ->all();

        $sync = [];
        foreach ($attributeIds as $id) {
            $sync[$id] = ['is_variant_axis' => in_array((int) $id, $allowedVariantIds, true)];
        }

        $family->attributes()->sync($sync);
    }
}
