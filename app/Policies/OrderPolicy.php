<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\Order;

class OrderPolicy
{
    public function view(?Customer $customer, Order $order): bool
    {
        if (! $customer) {
            return false;
        }

        return $order->customer_id === $customer->id;
    }

    public function confirmPayment(?Customer $customer, Order $order): bool
    {
        return $this->view($customer, $order);
    }

    public function confirmReceived(?Customer $customer, Order $order): bool
    {
        return $this->view($customer, $order);
    }

    public function review(?Customer $customer, Order $order): bool
    {
        return $this->view($customer, $order);
    }

    public function createReturn(?Customer $customer, Order $order): bool
    {
        return $this->view($customer, $order);
    }
}
