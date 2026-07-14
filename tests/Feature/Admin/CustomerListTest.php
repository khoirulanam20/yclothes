<?php

namespace Tests\Feature\Admin;

use App\Models\AdminRole;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerListTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@yclothes.test')->first();
    }

    public function test_can_list_customers_with_pagination(): void
    {
        Customer::factory()->count(3)->create();
        $total = Customer::count();

        $this->actingAs($this->admin)
            ->get('/admin/customers')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Customers/Index')
                ->where('customers.meta.total', $total)
                ->has('customers.data')
            );
    }

    public function test_can_filter_customers_by_search(): void
    {
        $target = Customer::factory()->create([
            'name' => 'Andi Khusus',
            'email' => 'andi.khusus@example.test',
            'phone' => '081234567890',
        ]);
        Customer::factory()->create(['name' => 'Budi Lain']);

        $this->actingAs($this->admin)
            ->get('/admin/customers?search=Andi')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Customers/Index')
                ->has('customers.data', 1)
                ->where('customers.data.0.id', $target->id)
            );
    }

    public function test_can_filter_customers_by_active_status(): void
    {
        Customer::factory()->create(['name' => 'Aktif Satu', 'is_active' => true]);
        Customer::factory()->create(['name' => 'Nonaktif Satu', 'is_active' => false]);

        $this->actingAs($this->admin)
            ->get('/admin/customers?status=inactive')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Customers/Index')
                ->has('customers.data', 1)
                ->where('customers.data.0.name', 'Nonaktif Satu')
            );
    }

    public function test_show_page_displays_addresses_and_orders(): void
    {
        $customer = Customer::factory()->create(['name' => 'Siti Pelanggan']);

        CustomerAddress::create([
            'customer_id' => $customer->id,
            'label' => 'Rumah',
            'recipient_name' => $customer->name,
            'phone' => $customer->phone,
            'street_address' => 'Jl. Merdeka No. 1',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'postal_code' => '10110',
            'is_default' => true,
            'type' => 'shipping',
        ]);

        $order = Order::factory()->forCustomer($customer)->create();

        $this->actingAs($this->admin)
            ->get("/admin/customers/{$customer->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Customers/Show')
                ->where('customer.id', $customer->id)
                ->where('customer.name', 'Siti Pelanggan')
                ->has('addresses', 1)
                ->where('addresses.0.label', 'Rumah')
                ->has('orders', 1)
                ->where('orders.0.id', $order->id)
            );
    }

    public function test_customers_require_customers_view_permission(): void
    {
        $role = AdminRole::create([
            'name' => 'No Customer Access '.uniqid(),
            'description' => 'Test role',
            'permissions' => ['orders.view'],
        ]);

        $staff = User::create([
            'name' => 'Staff '.uniqid(),
            'email' => 'staff-'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
            'admin_role_id' => $role->id,
        ]);

        $this->actingAs($staff)
            ->get('/admin/customers')
            ->assertForbidden();
    }
}
