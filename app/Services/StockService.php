<?php

namespace App\Services;

use App\Models\Stock;

class StockService
{
    /**
     * Get all stocks with optional filters.
     */
    public function getAll(array $filters = [], $distributionId = null)
    {
        $query = Stock::with(['product', 'distribution']);

        // Filter by distribution
        if ($distributionId) {
            $query->where('distribution_id', $distributionId);
        }

        // Search by product name
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('product', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('dms_code', 'like', "%{$search}%");
            });
        }

        // Low stock filter
        if (!empty($filters['low_stock'])) {
            $query->whereColumn('quantity', '<=', 'min_quantity');
        }

        return $query->latest()->paginate(15)->withQueryString();
    }

    /**
     * Create a new stock entry.
     */
    public function create(array $data): Stock
    {
        return Stock::create($data);
    }

    /**
     * Update stock quantity.
     */
    public function updateQuantity(Stock $stock, int $quantity, string $type = 'set'): Stock
    {
        if ($type === 'add') {
            $stock->quantity += $quantity;
        } elseif ($type === 'subtract') {
            $stock->quantity = max(0, $stock->quantity - $quantity);
        } else {
            $stock->quantity = $quantity;
        }
        
        $stock->save();
        return $stock;
    }

    /**
     * Delete a stock entry.
     */
    public function delete(Stock $stock): bool
    {
        return $stock->delete();
    }
}
