<?php

namespace App\Domains\Order\Repository;

use App\Models\Customer;

class CustomerRepository
{
    public function findByPhone(string $phone): ?Customer
    {
        return Customer::where('phone', $phone)->first();
    }

    public function upsert(string $phone, array $data): Customer
    {
        return Customer::updateOrCreate(['phone' => $phone], $data);
    }
}
