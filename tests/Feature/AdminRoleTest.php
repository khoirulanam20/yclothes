<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_super_admin_can_create_role(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();

        $this->actingAs($admin)
            ->post('/admin/roles', [
                'name' => 'Content Editor',
                'description' => 'Kelola CMS',
                'permissions' => ['cms.manage'],
            ])
            ->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseHas('admin_roles', ['name' => 'Content Editor']);
    }

    public function test_staff_without_permission_is_blocked_from_products(): void
    {
        $role = AdminRole::where('name', 'Staff')->first();
        $staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff@yclothes.test',
            'password' => Hash::make('password123'),
            'is_admin' => false,
            'admin_role_id' => $role->id,
        ]);

        $this->actingAs($staff)
            ->get('/admin/products')
            ->assertForbidden();
    }

    public function test_staff_with_orders_permission_can_view_orders(): void
    {
        $role = AdminRole::where('name', 'Staff')->first();
        $staff = User::create([
            'name' => 'Staff Orders',
            'email' => 'staff-orders@yclothes.test',
            'password' => Hash::make('password123'),
            'is_admin' => false,
            'admin_role_id' => $role->id,
        ]);

        $this->actingAs($staff)
            ->get('/admin/orders')
            ->assertOk();
    }

    public function test_staff_can_login_to_admin(): void
    {
        $role = AdminRole::where('name', 'Staff')->first();
        User::create([
            'name' => 'Staff Login',
            'email' => 'staff-login@yclothes.test',
            'password' => Hash::make('password123'),
            'is_admin' => false,
            'admin_role_id' => $role->id,
        ]);

        $this->post('/admin/login', [
            'email' => 'staff-login@yclothes.test',
            'password' => 'password123',
        ])->assertRedirect(route('admin.dashboard'));
    }
}
