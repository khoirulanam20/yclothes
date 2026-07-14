<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');

        $query = Customer::query()->withCount('orders')->latest();

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($builder) use ($like) {
                $builder->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        $customers = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Customers/Index', [
            'customers' => ModelSerializer::paginated($customers, [ModelSerializer::class, 'adminCustomerSummary']),
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function show(Customer $customer)
    {
        $customer->loadCount('orders');
        $customer->load([
            'addresses' => fn ($query) => $query->orderByDesc('is_default')->orderBy('label'),
            'orders' => fn ($query) => $query->withCount('items')->latest()->limit(20),
        ]);

        return Inertia::render('Admin/Customers/Show', [
            'customer' => ModelSerializer::adminCustomerDetail($customer),
            'addresses' => ModelSerializer::collection($customer->addresses, [ModelSerializer::class, 'customerAddress']),
            'orders' => ModelSerializer::collection($customer->orders, [ModelSerializer::class, 'orderSummary']),
        ]);
    }
}
