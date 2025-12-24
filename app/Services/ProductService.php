<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    /**
     * Get all products with filtering and pagination
     */
    public function getAll($filters = [])
    {
        $query = Product::query()->with(['brand', 'category', 'productType', 'packing']);

        // Search
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('dms_code', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        // Filter by Type
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Sort
        $sortField = $filters['sort_field'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        // Whitelist sort fields to prevent SQL injection or errors
        $allowedSorts = ['name', 'dms_code', 'brand', 'net_consumer_price', 'stock_quantity', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->latest();
        }

        return $query->paginate(10)->withQueryString();
    }

    /**
     * Create a new product
     */
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * Find product by ID
     */
    public function find(int $id): ?Product
    {
        return Product::find($id);
    }

    /**
     * Update product
     */
    public function update(int $id, array $data): bool
    {
        $product = Product::findOrFail($id);
        return $product->update($data);
    }

    /**
     * Delete product
     */
    public function delete(int $id): bool
    {
        $product = Product::findOrFail($id);
        return $product->delete();
    }
}
