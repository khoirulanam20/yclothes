<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogRule;
use App\Models\Product;
use App\Services\CategoryTreeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CatalogRuleController extends Controller
{
    public function __construct(private CategoryTreeService $categoryTree) {}

    public function index()
    {
        $rules = CatalogRule::latest()->get();

        return Inertia::render('Admin/CatalogRules/Index', [
            'rules' => $rules->map(fn ($r) => $this->catalogRule($r))->values()->all(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/CatalogRules/Form', [
            'categoryOptions' => $this->categoryTree->formOptions(),
            'products' => Product::orderBy('name')->get(['id', 'name'])->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values()->all(),
        ]);
    }

    public function store(Request $request)
    {
        CatalogRule::create($this->validateRule($request));

        return redirect()->route('admin.catalog-rules.index')->with('success', 'Catalog rule berhasil ditambahkan');
    }

    public function edit(CatalogRule $catalogRule)
    {
        return Inertia::render('Admin/CatalogRules/Form', [
            'rule' => $this->catalogRule($catalogRule),
            'categoryOptions' => $this->categoryTree->formOptions(),
            'products' => Product::orderBy('name')->get(['id', 'name'])->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values()->all(),
        ]);
    }

    public function update(Request $request, CatalogRule $catalogRule)
    {
        $catalogRule->update($this->validateRule($request, $catalogRule));

        return redirect()->route('admin.catalog-rules.index')->with('success', 'Catalog rule berhasil diubah');
    }

    public function destroy(CatalogRule $catalogRule)
    {
        $catalogRule->delete();

        return redirect()->route('admin.catalog-rules.index')->with('success', 'Catalog rule berhasil dihapus');
    }

    private function catalogRule(CatalogRule $rule): array
    {
        return [
            'id' => $rule->id,
            'name' => $rule->name,
            'description' => $rule->description,
            'ruleType' => $rule->rule_type,
            'discountType' => $rule->discount_type,
            'discountAmount' => (float) $rule->discount_amount,
            'minOrderAmount' => $rule->min_order_amount !== null ? (float) $rule->min_order_amount : null,
            'minQty' => $rule->min_qty,
            'buyQty' => $rule->buy_qty,
            'getQty' => $rule->get_qty,
            'getDiscountPercent' => $rule->get_discount_percent !== null ? (float) $rule->get_discount_percent : null,
            'categoryIds' => $rule->category_ids ?? [],
            'productIds' => $rule->product_ids ?? [],
            'startDate' => $rule->start_date?->format('Y-m-d'),
            'endDate' => $rule->end_date?->format('Y-m-d'),
            'isActive' => (bool) $rule->is_active,
            'priority' => $rule->priority,
            'slug' => $rule->slug,
            'metaTitle' => $rule->meta_title,
            'metaDescription' => $rule->meta_description,
            'bannerImageUrl' => storage_url($rule->banner_image),
        ];
    }

    private function validateRule(Request $request, ?CatalogRule $rule = null): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'rule_type' => 'required|in:percentage_discount,fixed_discount,free_shipping_threshold,tiered_qty_discount,buy_x_get_y',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_amount' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'min_qty' => 'nullable|integer|min:1',
            'buy_qty' => 'nullable|integer|min:1',
            'get_qty' => 'nullable|integer|min:1',
            'get_discount_percent' => 'nullable|numeric|min:0|max:100',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer',
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('catalog_rules', 'slug')->ignore($rule?->id)],
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_banner_image' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['priority'] = $validated['priority'] ?? 0;
        $validated['discount_amount'] = $validated['discount_amount'] ?? 0;

        if ($request->hasFile('banner_image')) {
            $validated['banner_image'] = $request->file('banner_image')->store('promotions', 'public');
        } elseif ($request->boolean('remove_banner_image')) {
            $validated['banner_image'] = null;
        } else {
            unset($validated['banner_image'], $validated['remove_banner_image']);
        }

        return $validated;
    }
}
