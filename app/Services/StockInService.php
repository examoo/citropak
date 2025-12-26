<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockIn;
use Illuminate\Support\Facades\DB;

class StockInService
{
    /**
     * Get all stock ins with filters.
     */
    /**
     * Get all stock in items with filters.
     */
    public function getAll(array $filters = [], $distributionId = null)
    {
        $query = \App\Models\StockInItem::query()
            ->join('stock_ins', 'stock_in_items.stock_in_id', '=', 'stock_ins.id')
            ->select('stock_in_items.*', 'stock_ins.date', 'stock_ins.bilty_number', 'stock_ins.status', 'stock_ins.distribution_id as parent_distribution_id')
            ->with(['stockIn.distribution', 'product']);

        if ($distributionId) {
            $query->where('stock_ins.distribution_id', $distributionId);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('stock_ins.bilty_number', 'like', "%{$search}%")
                  ->orWhereHas('product', function($p) use ($search) {
                      $p->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('stock_ins.status', $filters['status']);
        }

        return $query->latest('stock_ins.date')->paginate(15)->withQueryString();
    }

    /**
     * Create a new stock in with items.
     */
    public function create(array $data, array $items): StockIn
    {
        return DB::transaction(function () use ($data, $items) {
            $stockIn = StockIn::create($data);

            foreach ($items as $item) {
                $stockIn->items()->create($item);
            }

            return $stockIn->load('items.product');
        });
    }

    /**
     * Update a stock in with items.
     */
    public function update(StockIn $stockIn, array $data, array $items): StockIn
    {
        return DB::transaction(function () use ($stockIn, $data, $items) {
            $stockIn->update($data);

            // Delete existing items and recreate
            $stockIn->items()->delete();

            foreach ($items as $item) {
                $stockIn->items()->create($item);
            }

            return $stockIn->load('items.product');
        });
    }

    /**
     * Post/Confirm stock in and update stocks table.
     */
    public function post(StockIn $stockIn): StockIn
    {
        return DB::transaction(function () use ($stockIn) {
            // Update stocks table for each item
            foreach ($stockIn->items as $item) {
                Stock::create([
                    'product_id' => $item->product_id,
                    'distribution_id' => $stockIn->distribution_id,
                    'quantity' => $item->quantity,
                    'min_quantity' => 0,
                    'max_quantity' => null,
                    'unit_cost' => $item->unit_cost,
                    'batch_number' => $item->batch_number,
                    'expiry_date' => $item->expiry_date,
                    'location' => $item->location,
                    // Pricing fields (simplified structure)
                    'pieces_per_packing' => $item->pieces_per_packing,
                    'list_price_before_tax' => $item->list_price_before_tax,
                    'fed_sales_tax' => $item->fed_sales_tax,
                    'fed_percent' => $item->fed_percent,
                    'retail_margin' => $item->retail_margin,
                    'tp_rate' => $item->tp_rate,
                    'distribution_margin' => $item->distribution_margin,
                    'invoice_price' => $item->invoice_price,
                    'unit_price' => $item->unit_price,
                ]);
            }

            // Mark stock in as posted
            $stockIn->update(['status' => 'posted']);

            return $stockIn;
        });
    }

    /**
     * Delete a stock in.
     */
    public function delete(StockIn $stockIn): bool
    {
        if ($stockIn->status === 'posted') {
            throw new \Exception('Cannot delete a posted stock in.');
        }
        return $stockIn->delete();
    }
}
