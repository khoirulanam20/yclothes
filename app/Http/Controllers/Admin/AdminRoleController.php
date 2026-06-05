<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminRoleController extends Controller
{
    public function index()
    {
        $roles = AdminRole::withCount('users')->latest()->get();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'permissions' => $role->permissions ?? [],
            ])->values()->all(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Roles/Form', [
            'allPermissions' => AdminRole::PERMISSIONS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRole($request);
        $validated['permissions'] = $request->input('permissions', []);

        AdminRole::create($validated);

        return redirect()->route('admin.roles.index')->with('success', 'Role berhasil ditambahkan.');
    }

    public function edit(AdminRole $role)
    {
        return Inertia::render('Admin/Roles/Form', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'permissions' => $role->permissions ?? [],
            ],
            'allPermissions' => AdminRole::PERMISSIONS,
        ]);
    }

    public function update(Request $request, AdminRole $role)
    {
        $validated = $this->validateRole($request);
        $validated['permissions'] = $request->input('permissions', []);

        $role->update($validated);

        return redirect()->route('admin.roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(AdminRole $role)
    {
        if ($role->users()->exists()) {
            return back()->with('error', 'Role masih digunakan oleh staff.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role berhasil dihapus.');
    }

    private function validateRole(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'in:'.implode(',', AdminRole::PERMISSIONS),
        ]);
    }
}
