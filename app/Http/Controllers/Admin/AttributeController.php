<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttributeType;
use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AttributeController extends Controller
{
    public function index()
    {
        $attributes = Attribute::withCount('options', 'families')->orderBy('sort_order')->paginate(10);

        return Inertia::render('Admin/Attributes/Index', [
            'attributes' => collect($attributes->items())->map(fn ($a) => $this->attributeSummary($a))->values()->all(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Attributes/Form', [
            'attributeTypes' => array_column(AttributeType::cases(), 'value'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateAttribute($request);
        $attribute = Attribute::create($validated);
        $this->syncOptions($attribute, $request);

        return redirect()->route('admin.attributes.index')
            ->with('success', 'Atribut berhasil ditambahkan');
    }

    public function edit(Attribute $attribute)
    {
        $attribute->load('options');

        return Inertia::render('Admin/Attributes/Form', [
            'attribute' => $this->attributeDetail($attribute),
            'attributeTypes' => array_column(AttributeType::cases(), 'value'),
        ]);
    }

    public function update(Request $request, Attribute $attribute)
    {
        $validated = $this->validateAttribute($request, $attribute);
        $attribute->update($validated);
        $this->syncOptions($attribute, $request);

        return redirect()->route('admin.attributes.index')
            ->with('success', 'Atribut berhasil diubah');
    }

    public function destroy(Attribute $attribute)
    {
        if ($attribute->productValues()->count() > 0) {
            return redirect()->route('admin.attributes.index')
                ->with('error', 'Atribut tidak bisa dihapus, masih dipakai produk');
        }

        $attribute->delete();

        return redirect()->route('admin.attributes.index')
            ->with('success', 'Atribut berhasil dihapus');
    }

    private function attributeSummary(Attribute $attribute): array
    {
        return [
            'id' => $attribute->id,
            'name' => $attribute->name,
            'code' => $attribute->code,
            'type' => $attribute->type?->value ?? $attribute->type,
            'isRequired' => (bool) $attribute->is_required,
            'isFilterable' => (bool) $attribute->is_filterable,
        ];
    }

    private function attributeDetail(Attribute $attribute): array
    {
        return array_merge($this->attributeSummary($attribute), [
            'validation' => $attribute->validation,
            'sortOrder' => $attribute->sort_order,
            'options' => $attribute->relationLoaded('options')
                ? $attribute->options->map(fn ($o) => [
                    'name' => $o->name,
                    'sortOrder' => $o->sort_order,
                ])->values()->all()
                : [],
        ]);
    }

    private function validateAttribute(Request $request, ?Attribute $attribute = null): array
    {
        $validated = $request->validate([
            'name' => 'required|max:100',
            'code' => [
                'required',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('attributes', 'code')->ignore($attribute?->id),
            ],
            'type' => ['required', Rule::enum(AttributeType::class)],
            'is_required' => 'nullable|boolean',
            'is_filterable' => 'nullable|boolean',
            'validation' => 'nullable|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'options' => 'nullable|array',
            'options.*.name' => 'required_with:options|string|max:100',
            'options.*.sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_required'] = $request->boolean('is_required');
        $validated['is_filterable'] = $request->boolean('is_filterable');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        if (empty($validated['code'])) {
            $validated['code'] = Str::slug($validated['name'], '_');
        }

        return $validated;
    }

    private function syncOptions(Attribute $attribute, Request $request): void
    {
        if (! $attribute->type->hasOptions()) {
            $attribute->options()->delete();

            return;
        }

        $options = collect($request->input('options', []))
            ->filter(fn ($opt) => ! empty(trim($opt['name'] ?? '')))
            ->values();

        $attribute->options()->delete();

        foreach ($options as $index => $opt) {
            $attribute->options()->create([
                'name' => trim($opt['name']),
                'sort_order' => (int) ($opt['sort_order'] ?? $index),
            ]);
        }
    }
}
