<?php

namespace App\Services;

use App\Models\ProductCategory;
use Illuminate\Support\Str;

class ProductCategoryService
{
    /**
     * Get all product categories with optional filters.
     */
    public function getAll(array $filters = [])
    {
        $query = ProductCategory::query();

        // Search
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate(10)->withQueryString();
    }

    /**
     * Create a new product category.
     */
    public function create(array $data): ProductCategory
    {
        return ProductCategory::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'status' => $data['status'],
        ]);
    }

    /**
     * Update an existing product category.
     */
    public function update(ProductCategory $productCategory, array $data): ProductCategory
    {
        $productCategory->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'status' => $data['status'],
        ]);

        return $productCategory;
    }

    /**
     * Delete a product category.
     */
    public function delete(ProductCategory $productCategory): bool
    {
        return $productCategory->delete();
    }
}
