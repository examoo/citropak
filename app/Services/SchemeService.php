<?php

namespace App\Services;

use App\Models\Scheme;

class SchemeService
{
    public function getAll($filters = [])
    {
        $query = Scheme::with(['brand', 'product', 'subDistribution']);

        if (!empty($filters['scheme_type'])) {
            $query->where('scheme_type', $filters['scheme_type']);
        }

        if (!empty($filters['is_active'])) {
            $query->where('is_active', $filters['is_active'] === 'true');
        }

        return $query->latest()->paginate(10)->withQueryString();
    }

    public function create(array $data): Scheme
    {
        return Scheme::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Scheme::findOrFail($id)->update($data);
    }

    public function delete(int $id): bool
    {
        return Scheme::findOrFail($id)->delete();
    }
}
