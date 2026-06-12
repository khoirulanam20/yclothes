<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use App\Models\TaxRateCategory;
use App\Services\CategoryTreeService;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaxRateController extends Controller
{
    public function __construct(private CategoryTreeService $categoryTree) {}

    public function index()
    {
        $rates = TaxRate::withCount('categories')->latest()->paginate(15);

        return Inertia::render('Admin/TaxRates/Index', [
            'rates' => ModelSerializer::paginated($rates, fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'rate' => (float) $r->rate,
                'type' => $r->type,
                'isActive' => (bool) $r->is_active,
                'categoriesCount' => $r->categories_count,
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/TaxRates/Form', [
            'categoryOptions' => $this->categoryTree->formOptions(),
            'selectedCategories' => [],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRate($request);
        $rate = TaxRate::create($validated);
        $this->syncCategories($rate, $request->input('category_ids', []));

        return redirect()->route('admin.tax-rates.index')->with('success', 'Tarif pajak berhasil ditambahkan');
    }

    public function edit(TaxRate $taxRate)
    {
        return Inertia::render('Admin/TaxRates/Form', [
            'rate' => [
                'id' => $taxRate->id,
                'name' => $taxRate->name,
                'rate' => (float) $taxRate->rate,
                'type' => $taxRate->type,
                'isActive' => (bool) $taxRate->is_active,
            ],
            'categoryOptions' => $this->categoryTree->formOptions(),
            'selectedCategories' => $taxRate->categories()->pluck('category_id')->all(),
        ]);
    }

    public function update(Request $request, TaxRate $taxRate)
    {
        $validated = $this->validateRate($request);
        $taxRate->update($validated);
        $this->syncCategories($taxRate, $request->input('category_ids', []));

        return redirect()->route('admin.tax-rates.index')->with('success', 'Tarif pajak berhasil diubah');
    }

    public function destroy(TaxRate $taxRate)
    {
        $taxRate->delete();

        return redirect()->route('admin.tax-rates.index')->with('success', 'Tarif pajak berhasil dihapus');
    }

    private function validateRate(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|in:percentage,fixed',
            'is_active' => 'nullable|boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function syncCategories(TaxRate $rate, array $categoryIds): void
    {
        $rate->categories()->delete();
        foreach ($categoryIds as $categoryId) {
            TaxRateCategory::create([
                'tax_rate_id' => $rate->id,
                'category_id' => $categoryId,
            ]);
        }
    }
}
