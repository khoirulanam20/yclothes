<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingCost;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShippingCostController extends Controller
{
    public function index()
    {
        $costs = ShippingCost::latest()->get();

        return Inertia::render('Admin/ShippingCosts/Index', [
            'costs' => ModelSerializer::collection($costs, [ModelSerializer::class, 'shippingCostRecord']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/ShippingCosts/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'city_name' => 'required|string|max:255',
            'cost' => 'required|integer|min:0',
            'cost_per_kg' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        ShippingCost::create($validated);

        return redirect()->route('admin.shipping-costs.index')->with('success', 'Ongkir berhasil ditambahkan');
    }

    public function edit(ShippingCost $shippingCost)
    {
        return Inertia::render('Admin/ShippingCosts/Form', [
            'cost' => ModelSerializer::shippingCostRecord($shippingCost),
        ]);
    }

    public function update(Request $request, ShippingCost $shippingCost)
    {
        $validated = $request->validate([
            'city_name' => 'required|string|max:255',
            'cost' => 'required|integer|min:0',
            'cost_per_kg' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $shippingCost->update($validated);

        return redirect()->route('admin.shipping-costs.index')->with('success', 'Ongkir berhasil diubah');
    }

    public function destroy(ShippingCost $shippingCost)
    {
        $shippingCost->delete();

        return redirect()->route('admin.shipping-costs.index')->with('success', 'Ongkir berhasil dihapus');
    }
}
