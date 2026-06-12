<?php

namespace App\Http\Controllers\Api\Pos;

use App\Models\Customer;
use App\Support\Api\PosApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->get('q', ''));
        $perPage = min(50, max(5, $request->integer('per_page', 10)));

        $builder = Customer::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($query !== '') {
            $builder->where(function ($inner) use ($query) {
                $inner->where('name', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            });
        }

        $paginator = $builder->paginate($perPage, ['id', 'name', 'phone', 'email', 'created_at']);

        return PosApiResponse::success(
            $paginator->getCollection()
                ->map(fn (Customer $customer) => $this->serializeCustomer($customer))
                ->values()
                ->all(),
            [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        );
    }

    public function search(Request $request)
    {
        $query = trim((string) $request->get('q', ''));

        if ($query === '') {
            return PosApiResponse::success([]);
        }

        $customers = Customer::query()
            ->where('is_active', true)
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'phone', 'email', 'created_at']);

        return PosApiResponse::success(
            $customers->map(fn (Customer $customer) => $this->serializeCustomer($customer))->values()->all(),
        );
    }

    public function quick(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $email = $validated['email'] ?? 'pos+'.Str::uuid().'@walk-in.local';

        $customer = Customer::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $email,
            'password' => Str::password(32),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return PosApiResponse::success($this->serializeCustomer($customer), [], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCustomer(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'createdAt' => $customer->created_at?->toIso8601String(),
        ];
    }
}
