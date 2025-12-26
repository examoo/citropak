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
        $query = Customer::query()
            ->with(['distribution:id,name,code']);

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

    public function getAttributes($distributionId = null)
    {
        $attributes = collect();
        
        // Fetch Vans scoped by distribution (or all if global)
        $vanQuery = \App\Models\Van::where('status', 'active');
        if ($distributionId) {
            $vanQuery->where('distribution_id', $distributionId);
        }
        $vans = $vanQuery->latest()->get()->map(function($van) {
            return ['id' => $van->id, 'value' => $van->code, 'type' => 'van', 'distribution_id' => $van->distribution_id];
        });
        $attributes->put('van', $vans);

        // Fetch Channels scoped by distribution (global + specific)
        $channelQuery = \App\Models\Channel::where('status', 'active');
        if ($distributionId) {
            $channelQuery->where(function($q) use ($distributionId) {
                $q->whereNull('distribution_id')
                  ->orWhere('distribution_id', $distributionId);
            });
        }
        $channels = $channelQuery->latest()->get()->map(function($channel) {
            return [
                'id' => $channel->id, 
                'value' => $channel->name, 
                'type' => 'channel', 
                'atl' => $channel->atl, 
                'adv_tax_percent' => $channel->adv_tax_percent,
                'distribution_id' => $channel->distribution_id
            ];
        });
        $attributes->put('channel', $channels);

        // Fetch Sub Addresses scoped by distribution (global + specific)
        $subAddressQuery = \App\Models\SubAddress::where('status', 'active');
        if ($distributionId) {
            $subAddressQuery->where(function($q) use ($distributionId) {
                $q->whereNull('distribution_id')
                  ->orWhere('distribution_id', $distributionId);
            });
        }
        $subAddresses = $subAddressQuery->latest()->get()->map(function($sub) {
            return ['id' => $sub->id, 'value' => $sub->name, 'type' => 'sub_address', 'distribution_id' => $sub->distribution_id];
        });
        $attributes->put('sub_address', $subAddresses);

        // Fetch Sub Distributions scoped by distribution (global + specific)
        $subDistributionQuery = \App\Models\SubDistribution::where('status', 'active');
        if ($distributionId) {
            $subDistributionQuery->where(function($q) use ($distributionId) {
                $q->whereNull('distribution_id')
                  ->orWhere('distribution_id', $distributionId);
            });
        }
        $subDistributions = $subDistributionQuery->latest()->get()->map(function($sub) {
            return ['id' => $sub->id, 'value' => $sub->name, 'type' => 'sub_distribution', 'distribution_id' => $sub->distribution_id];
        });
        $attributes->put('sub_distribution', $subDistributions);

        // Fetch Categories (global, no distribution scoping)
        $categories = \App\Models\Category::all()->map(function($cat) {
            return ['id' => $cat->id, 'value' => $cat->name, 'type' => 'category'];
        });
        $attributes->put('category', $categories);

        // Fetch Routes scoped by distribution (global + specific)
        $routeQuery = \App\Models\Route::where('status', 'active');
        if ($distributionId) {
            $routeQuery->where(function($q) use ($distributionId) {
                $q->whereNull('distribution_id')
                  ->orWhere('distribution_id', $distributionId);
            });
        }
        $routes = $routeQuery->latest()->get()->map(function($route) {
            return ['id' => $route->id, 'value' => $route->name, 'type' => 'route', 'distribution_id' => $route->distribution_id];
        });
        $attributes->put('route', $routes);

        // Fetch Distributions (for customer distribution dropdown, if needed)
        $distributions = \App\Models\Distribution::where('status', 'active')->get()->map(function($dist) {
            return ['id' => $dist->id, 'value' => $dist->name, 'type' => 'distribution'];
        });
        $attributes->put('distribution', $distributions);

        return $attributes;
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
