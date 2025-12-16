<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

class CustomerService
{
    /**
     * Get all customers with filtering and pagination
     */
    public function getAll($filters = [])
    {
        $query = Customer::query();

        // Search
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('shop_name', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('van', 'like', "%{$search}%")
                  ->orWhere('ntn_number', 'like', "%{$search}%");
            });
        }

        // Filter by Status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Sort
        $sortField = $filters['sort_field'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        // Whitelist sort fields to prevent SQL injection or errors
        $allowedSorts = ['shop_name', 'customer_code', 'phone', 'channel', 'status', 'created_at', 'van'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->latest();
        }

        return $query->paginate(10)->withQueryString();
    }

    public function getAttributes()
    {
        return \App\Models\CustomerAttribute::all()->groupBy('type');
    }

    /**
     * Create a new customer
     */
    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    /**
     * Find customer by ID
     */
    public function find(int $id): ?Customer
    {
        return Customer::find($id);
    }

    /**
     * Update customer
     */
    public function update(int $id, array $data): bool
    {
        $customer = Customer::findOrFail($id);
        return $customer->update($data);
    }

    /**
     * Delete customer
     */
    public function delete(int $id): bool
    {
        $customer = Customer::findOrFail($id);
        return $customer->delete();
    }
}
