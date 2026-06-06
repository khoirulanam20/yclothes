<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CartRule;
use App\Services\CategoryTreeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CartRuleController extends Controller
{
    public function __construct(private CategoryTreeService $categoryTree) {}

    public function index()
    {
        $rules = CartRule::latest()->get();

        return Inertia::render('Admin/CartRules/Index', [
            'rules' => $rules->map(fn ($r) => $this->cartRule($r))->values()->all(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/CartRules/Form', [
            'categoryOptions' => $this->categoryTree->formOptions(),
        ]);
    }

    public function store(Request $request)
    {
        CartRule::create($this->validateRule($request));

        return redirect()->route('admin.cart-rules.index')->with('success', 'Cart rule berhasil ditambahkan');
    }

    public function edit(CartRule $cartRule)
    {
        return Inertia::render('Admin/CartRules/Form', [
            'rule' => $this->cartRule($cartRule),
            'categoryOptions' => $this->categoryTree->formOptions(),
        ]);
    }

    public function update(Request $request, CartRule $cartRule)
    {
        $cartRule->update($this->validateRule($request, $cartRule));

        return redirect()->route('admin.cart-rules.index')->with('success', 'Cart rule berhasil diubah');
    }

    public function destroy(CartRule $cartRule)
    {
        $cartRule->delete();

        return redirect()->route('admin.cart-rules.index')->with('success', 'Cart rule berhasil dihapus');
    }

    private function cartRule(CartRule $rule): array
    {
        return [
            'id' => $rule->id,
            'name' => $rule->name,
            'description' => $rule->description,
            'couponCode' => $rule->coupon_code,
            'usesPerCoupon' => $rule->uses_per_coupon,
            'usesPerCustomer' => $rule->uses_per_customer,
            'discountType' => $rule->discount_type,
            'discountAmount' => (float) $rule->discount_amount,
            'minOrderAmount' => $rule->min_order_amount !== null ? (float) $rule->min_order_amount : null,
            'maxDiscount' => $rule->max_discount !== null ? (float) $rule->max_discount : null,
            'categoryIds' => $rule->category_ids ?? [],
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

    private function validateRule(Request $request, ?CartRule $rule = null): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'coupon_code' => ['nullable', 'string', 'max:50', Rule::unique('cart_rules', 'coupon_code')->ignore($rule?->id)],
            'uses_per_coupon' => 'nullable|integer|min:0',
            'uses_per_customer' => 'nullable|integer|min:0',
            'discount_type' => 'required|in:percentage,fixed,free_shipping',
            'discount_amount' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer',
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('cart_rules', 'slug')->ignore($rule?->id)],
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_banner_image' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['uses_per_coupon'] = $validated['uses_per_coupon'] ?? 0;
        $validated['uses_per_customer'] = $validated['uses_per_customer'] ?? 0;
        $validated['priority'] = $validated['priority'] ?? 0;

        if (! empty($validated['coupon_code'])) {
            $validated['coupon_code'] = strtoupper(trim($validated['coupon_code']));
        }

        $validated['category_ids'] = ! empty($validated['category_ids'])
            ? array_values(array_unique(array_map('intval', $validated['category_ids'])))
            : null;

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
