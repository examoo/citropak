<?php

namespace App\Services;

use App\Models\Category;

class CategoryService
{
    public function getAll($filters = [])
    {
        $query = Category::with('parent');

        if (!empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        return $query->latest()->paginate(10)->withQueryString();
    }

    public function getActive()
    {
        return Category::orderBy('name')->get();
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Category::findOrFail($id)->update($data);
    }

    public function delete(int $id): bool
    {
        return Category::findOrFail($id)->delete();
    }
}
