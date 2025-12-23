<?php

namespace App\Services;

use App\Models\Distribution;

class DistributionService
{
    /**
     * Get all distributions with filtering and pagination
     */
    public function getAll($filters = [])
    {
        $query = Distribution::query();

        // Search
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter by Status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate(10)->withQueryString();
    }

    /**
     * Get all active distributions for dropdowns
     */
    public function getActive()
    {
        return Distribution::where('status', 'active')->orderBy('name')->get();
    }

    /**
     * Create a new distribution
     */
    public function create(array $data): Distribution
    {
        return Distribution::create($data);
    }

    /**
     * Find distribution by ID
     */
    public function find(int $id): ?Distribution
    {
        return Distribution::find($id);
    }

    /**
     * Update distribution
     */
    public function update(int $id, array $data): bool
    {
        $distribution = Distribution::findOrFail($id);
        return $distribution->update($data);
    }

    /**
     * Delete distribution
     */
    public function delete(int $id): bool
    {
        $distribution = Distribution::findOrFail($id);
        return $distribution->delete();
    }
}
