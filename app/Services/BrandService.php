<?php

namespace App\Services;

use App\Models\Brand;

class BrandService
{
    /**
     * Get all brands with filtering and pagination
     */
    public function getAll($filters = [])
    {
        $query = Brand::query();

        if (!empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate(10)->withQueryString();
    }

    /**
     * Get all active brands for dropdowns
     */
    public function getActive()
    {
        return Brand::where('status', 'active')->orderBy('name')->get();
    }

    public function create(array $data): Brand
    {
        return Brand::create($data);
    }

    public function find(int $id): ?Brand
    {
        return Brand::find($id);
    }

    public function update(int $id, array $data): bool
    {
        return Brand::findOrFail($id)->update($data);
    }

    public function delete(int $id): bool
    {
        return Brand::findOrFail($id)->delete();
    }
}
