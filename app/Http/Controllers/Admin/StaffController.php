<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\User;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function index()
    {
        $staff = User::where(function ($q) {
            $q->where('is_admin', true)->orWhereNotNull('admin_role_id');
        })->with('adminRole')->latest()->paginate(15);

        return Inertia::render('Admin/Staff/Index', [
            'staff' => collect($staff->items())->map([ModelSerializer::class, 'staffUser'])->values()->all(),
        ]);
    }

    public function create()
    {
        $roles = AdminRole::orderBy('name')->get();

        return Inertia::render('Admin/Staff/Form', [
            'roles' => $roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->values()->all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateStaff($request);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $request->boolean('is_admin'),
            'admin_role_id' => $validated['admin_role_id'] ?? null,
        ]);

        return redirect()->route('admin.staff.index')->with('success', 'Staff berhasil ditambahkan.');
    }

    public function edit(User $staff)
    {
        abort_unless($staff->canAccessAdmin(), 404);

        $staff->load('adminRole');
        $roles = AdminRole::orderBy('name')->get();

        return Inertia::render('Admin/Staff/Form', [
            'staff' => ModelSerializer::staffUser($staff),
            'roles' => $roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->values()->all(),
        ]);
    }

    public function update(Request $request, User $staff)
    {
        abort_unless($staff->canAccessAdmin(), 404);

        $validated = $this->validateStaff($request, $staff->id);

        $staff->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_admin' => $request->boolean('is_admin'),
            'admin_role_id' => $validated['admin_role_id'] ?? null,
        ]);

        if (! empty($validated['password'])) {
            $staff->update(['password' => Hash::make($validated['password'])]);
        }

        return redirect()->route('admin.staff.index')->with('success', 'Staff berhasil diperbarui.');
    }

    public function destroy(User $staff)
    {
        abort_unless($staff->canAccessAdmin(), 404);

        if ($staff->id === Auth::id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        $staff->delete();

        return redirect()->route('admin.staff.index')->with('success', 'Staff berhasil dihapus.');
    }

    private function validateStaff(Request $request, ?int $ignoreId = null): array
    {
        $emailRule = 'required|email|max:255|unique:users,email';
        if ($ignoreId) {
            $emailRule .= ','.$ignoreId;
        }

        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => $emailRule,
            'password' => ($ignoreId ? 'nullable' : 'required').'|min:8|confirmed',
            'admin_role_id' => 'nullable|exists:admin_roles,id',
            'is_admin' => 'nullable|boolean',
        ]);
    }
}
