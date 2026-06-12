<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingCost;
use App\Support\ModelSerializer;
use App\Support\WilayahCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ShippingCostController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $courierCode = (string) $request->input('courier_code', '');
        $provinceCode = (string) $request->input('province_code', '');
        $regencyCode = (string) $request->input('regency_code', '');
        $status = (string) $request->input('status', '');

        $query = ShippingCost::query()->latest();

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($builder) use ($like) {
                $builder->where('courier_name', 'like', $like)
                    ->orWhere('courier_code', 'like', $like)
                    ->orWhere('province_name', 'like', $like)
                    ->orWhere('regency_name', 'like', $like)
                    ->orWhere('city_name', 'like', $like)
                    ->orWhere('regency_code', 'like', $like);
            });
        }

        if ($courierCode !== '') {
            $query->where('courier_code', $courierCode);
        }

        if ($provinceCode !== '') {
            $normalizedProvince = WilayahCode::normalize($provinceCode) ?? $provinceCode;
            $query->where('province_code', $normalizedProvince);
        }

        if ($regencyCode !== '') {
            $normalizedRegency = WilayahCode::normalize($regencyCode) ?? $regencyCode;
            $query->where('regency_code', $normalizedRegency);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $costs = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/ShippingCosts/Index', [
            'costs' => ModelSerializer::paginated($costs, [ModelSerializer::class, 'shippingCostRecord']),
            'couriers' => config('couriers.list', []),
            'filters' => [
                'search' => $search,
                'courier_code' => $courierCode,
                'province_code' => $provinceCode,
                'regency_code' => $regencyCode,
                'status' => $status,
            ],
        ]);
    }

    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['activate', 'deactivate', 'delete'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:shipping_costs,id'],
        ]);

        $records = ShippingCost::whereIn('id', $validated['ids']);

        match ($validated['action']) {
            'activate' => $records->update(['is_active' => true]),
            'deactivate' => $records->update(['is_active' => false]),
            'delete' => $records->delete(),
        };

        $message = match ($validated['action']) {
            'activate' => 'Tarif ongkir berhasil diaktifkan',
            'deactivate' => 'Tarif ongkir berhasil dinonaktifkan',
            'delete' => 'Tarif ongkir berhasil dihapus',
        };

        return back()->with('success', $message);
    }

    public function create()
    {
        return Inertia::render('Admin/ShippingCosts/Form', [
            'couriers' => config('couriers.list', []),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateShippingCost($request);
        ShippingCost::create($validated);

        return redirect()->route('admin.shipping-costs.index')->with('success', 'Ongkir berhasil ditambahkan');
    }

    public function edit(ShippingCost $shippingCost)
    {
        return Inertia::render('Admin/ShippingCosts/Form', [
            'cost' => ModelSerializer::shippingCostRecord($shippingCost),
            'couriers' => config('couriers.list', []),
        ]);
    }

    public function update(Request $request, ShippingCost $shippingCost)
    {
        $validated = $this->validateShippingCost($request, $shippingCost->id);
        $shippingCost->update($validated);

        return redirect()->route('admin.shipping-costs.index')->with('success', 'Ongkir berhasil diubah');
    }

    public function destroy(ShippingCost $shippingCost)
    {
        $shippingCost->delete();

        return redirect()->route('admin.shipping-costs.index')->with('success', 'Ongkir berhasil dihapus');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateShippingCost(Request $request, ?int $ignoreId = null): array
    {
        $courierCodes = collect(config('couriers.list', []))->pluck('code')->all();

        $validated = $request->validate([
            'courier_code' => ['required', 'string', Rule::in($courierCodes)],
            'province_code' => 'required|string|max:10',
            'province_name' => 'required|string|max:100',
            'regency_code' => 'required|string|max:10',
            'regency_name' => 'required|string|max:100',
            'cost' => 'required|integer|min:0',
            'cost_per_kg' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['regency_code'] = WilayahCode::normalize($validated['regency_code']) ?? $validated['regency_code'];
        $validated['province_code'] = WilayahCode::normalize($validated['province_code']) ?? $validated['province_code'];
        $validated['is_active'] = $request->boolean('is_active');
        $validated['city_name'] = $validated['regency_name'];
        $validated['courier_name'] = collect(config('couriers.list', []))
            ->firstWhere('code', $validated['courier_code'])['name'] ?? $validated['courier_code'];

        $uniqueRule = Rule::unique('shipping_costs', 'regency_code')
            ->where('courier_code', $validated['courier_code']);

        if ($ignoreId) {
            $uniqueRule->ignore($ignoreId);
        }

        $request->validate([
            'regency_code' => [$uniqueRule],
        ]);

        return $validated;
    }
}
