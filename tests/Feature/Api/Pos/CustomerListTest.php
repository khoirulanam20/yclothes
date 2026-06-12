<?php

namespace Tests\Feature\Api\Pos;

use App\Models\Customer;

class CustomerListTest extends PosApiTestCase
{
    public function test_can_list_customers_with_pagination(): void
    {
        $response = $this->withHeaders($this->posHeaders())
            ->getJson('/api/pos/customers?per_page=5');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'phone', 'email', 'createdAt']],
                'meta' => ['currentPage', 'lastPage', 'perPage', 'total'],
            ]);

        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_can_filter_customers_by_query(): void
    {
        $customer = Customer::query()->where('name', 'Budi Santoso')->firstOrFail();

        $this->withHeaders($this->posHeaders())
            ->getJson('/api/pos/customers?q=Budi')
            ->assertOk()
            ->assertJsonPath('data.0.id', $customer->id)
            ->assertJsonPath('data.0.name', 'Budi Santoso');
    }
}
