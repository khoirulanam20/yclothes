<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use App\Models\TaxZone;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaxZoneController extends Controller
{
    public function index()
    {
        $zones = TaxZone::with('taxRate')->latest()->get();

        return Inertia::render('Admin/TaxZones/Index', [
            'zones' => $zones->map(fn ($z) => [
                'id' => $z->id,
                'province' => $z->province,
                'city' => $z->city,
                'taxRate' => $z->taxRate ? ['name' => $z->taxRate->name] : null,
            ])->values()->all(),
        ]);
    }

    public function create()
    {
        $taxRates = TaxRate::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Admin/TaxZones/Form', [
            'taxRates' => $taxRates->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->values()->all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateZone($request);
        TaxZone::create($validated);

        return redirect()->route('admin.tax-zones.index')->with('success', 'Zona pajak berhasil ditambahkan');
    }

    public function edit(TaxZone $taxZone)
    {
        $taxRates = TaxRate::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Admin/TaxZones/Form', [
            'zone' => [
                'id' => $taxZone->id,
                'province' => $taxZone->province,
                'city' => $taxZone->city,
                'taxRateId' => $taxZone->tax_rate_id,
            ],
            'taxRates' => $taxRates->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->values()->all(),
        ]);
    }

    public function update(Request $request, TaxZone $taxZone)
    {
        $taxZone->update($this->validateZone($request));

        return redirect()->route('admin.tax-zones.index')->with('success', 'Zona pajak berhasil diubah');
    }

    public function destroy(TaxZone $taxZone)
    {
        $taxZone->delete();

        return redirect()->route('admin.tax-zones.index')->with('success', 'Zona pajak berhasil dihapus');
    }

    private function validateZone(Request $request): array
    {
        return $request->validate([
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'tax_rate_id' => 'required|exists:tax_rates,id',
        ]);
    }
}
