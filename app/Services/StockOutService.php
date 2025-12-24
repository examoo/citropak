<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockOut;
use Illuminate\Support\Facades\DB;

class StockOutService
{
    /**
     * Get all stock outs with filters.
     */
    public function getAll(array $filters = [], $distributionId = null)
    {
        $query = StockOut::with(['distribution', 'items.product', 'creator']);

        if ($distributionId) {
            $query->where('distribution_id', $distributionId);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('bilty_number', 'like', "%{$search}%");
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate(15)->withQueryString();
    }

    /**
     * Create a new stock out with items.
     */
    public function create(array $data, array $items): StockOut
    {
        return DB::transaction(function () use ($data, $items) {
            $stockOut = StockOut::create($data);

            foreach ($items as $item) {
                $stockOut->items()->create($item);
            }

            return $stockOut->load('items.product');
        });
    }

    /**
     * Update a stock out with items.
     */
    public function update(StockOut $stockOut, array $data, array $items): StockOut
    {
        return DB::transaction(function () use ($stockOut, $data, $items) {
            $stockOut->update($data);

            // Delete existing items and recreate
            $stockOut->items()->delete();

            foreach ($items as $item) {
                $stockOut->items()->create($item);
            }

            return $stockOut->load('items.product');
        });
    }

    /**
     * Post/Confirm stock out and deduct from stocks table.
     */
    public function post(StockOut $stockOut): StockOut
    {
        return DB::transaction(function () use ($stockOut) {
            // Deduct from stocks table for each item
            foreach ($stockOut->items as $item) {
                $stock = null;

                // 1. Try to find by direct stock_id (most accurate)
                if ($item->stock_id) {
                    $stock = Stock::find($item->stock_id);
                }

                // 2. Fallback: Try to find by Product + Batch + Distribution
                if (!$stock) {
                    $query = Stock::where('product_id', $item->product_id)
                        ->where('distribution_id', $stockOut->distribution_id);
                    
                    if ($item->batch_number) {
                        $query->where('batch_number', $item->batch_number);
                    }
                    
                    // Get the first matching stock
                    $stock = $query->first();
                }

                if ($stock) {
                    $stock->quantity -= $item->quantity;
                    if ($stock->quantity < 0) $stock->quantity = 0; 
                    $stock->save();
                }
            }

            // Mark stock out as posted
            $stockOut->update(['status' => 'posted']);

            return $stockOut;
        });
    }

    /**
     * Delete a stock out.
     */
    public function delete(StockOut $stockOut): bool
    {
        if ($stockOut->status === 'posted') {
            throw new \Exception('Cannot delete a posted stock out.');
        }
        return $stockOut->delete();
    }
}
