<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\ReturnRequest;

class ReturnRequestPolicy
{
    public function view(?Customer $customer, ReturnRequest $returnRequest): bool
    {
        if (! $customer) {
            return false;
        }

        return $returnRequest->customer_id === $customer->id;
    }
}
