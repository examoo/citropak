<?php

namespace App\Services;

use App\Models\DiscountScheme;

class DiscountSchemeService
{
    /**
     * Get all discount schemes with optional filters.
     */
    public function getAll(array $filters = [], $distributionId = null)
    {
        $query = DiscountScheme::with(['distribution', 'product', 'brand']);

        // Filter by distribution
        if ($distributionId) {
            $query->where(function($q) use ($distributionId) {
                $q->whereNull('distribution_id')
                  ->orWhere('distribution_id', $distributionId);
            });
        }

        // Search
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate(10)->withQueryString();
    }

    /**
     * Create a new discount scheme.
     */
    public function create(array $data): DiscountScheme
    {
        return DiscountScheme::create($data);
    }

    /**
     * Update an existing discount scheme.
     */
    public function update(DiscountScheme $scheme, array $data): DiscountScheme
    {
        $scheme->update($data);
        return $scheme;
    }

    /**
     * Delete a discount scheme.
     */
    public function delete(DiscountScheme $scheme): bool
    {
        return $scheme->delete();
    }
}
