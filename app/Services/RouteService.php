<?php

namespace App\Services;

use App\Models\Route;

class RouteService
{
    public function getAll($filters = [])
    {
        $query = Route::query();

        if (!empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        return $query->latest()->paginate(10)->withQueryString();
    }

    public function getActive()
    {
        return Route::orderBy('name')->get();
    }

    public function create(array $data): Route
    {
        return Route::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Route::findOrFail($id)->update($data);
    }

    public function delete(int $id): bool
    {
        return Route::findOrFail($id)->delete();
    }
}
