<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::latest()->paginate(15);

        return Inertia::render('Admin/Warehouses/Index', [
            'warehouses' => ModelSerializer::paginated($warehouses, fn ($w) => [
                'id' => $w->id,
                'name' => $w->name,
                'city' => $w->city,
                'isActive' => (bool) $w->is_active,
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Warehouses/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        Warehouse::create($validated);

        return redirect()->route('admin.warehouses.index')->with('success', 'Gudang berhasil ditambahkan');
    }

    public function edit(Warehouse $warehouse)
    {
        return Inertia::render('Admin/Warehouses/Form', [
            'warehouse' => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'address' => $warehouse->address,
                'city' => $warehouse->city,
                'isActive' => (bool) $warehouse->is_active,
            ],
        ]);
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $warehouse->update($validated);

        return redirect()->route('admin.warehouses.index')->with('success', 'Gudang berhasil diubah');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return redirect()->route('admin.warehouses.index')->with('success', 'Gudang berhasil dihapus');
    }
}
