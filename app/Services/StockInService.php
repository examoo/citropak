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
            ->with(['stockIn.distribution', 'stockIn.items.product', 'product']);

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

            // If status is 'posted', also update the stocks table
            if ($stockIn->status === 'posted') {
                $this->updateStocksFromItems($stockIn);
            }

            return $stockIn->load('items.product');
        });
    }

    /**
     * Update stocks table from stock in items.
     */
    protected function updateStocksFromItems(StockIn $stockIn): void
    {
        foreach ($stockIn->items as $item) {
            // Get product pricing data for the stock
            $product = $item->product;
            
            Stock::create([
                'product_id' => $item->product_id,
                'distribution_id' => $stockIn->distribution_id,
                'quantity' => $item->quantity,
                'min_quantity' => 0,
                'max_quantity' => null,
                'unit_cost' => $item->unit_cost ?? 0,
                'batch_number' => $item->batch_number,
                'expiry_date' => $item->expiry_date,
                'location' => $item->location,
                // Pricing fields from product
                'pieces_per_packing' => $product->pieces_per_packing ?? 1,
                'list_price_before_tax' => $product->list_price_before_tax ?? 0,
                'fed_sales_tax' => $product->fed_sales_tax ?? 0,
                'fed_percent' => $product->fed_percent ?? 0,
                'retail_margin' => $product->retail_margin ?? 0,
                'tp_rate' => $product->tp_rate ?? 0,
                'distribution_margin' => $product->distribution_margin ?? 0,
                'invoice_price' => $product->invoice_price ?? 0,
                'unit_price' => $product->unit_price ?? 0,
                'unit_price' => $product->unit_price ?? 0,
                'stock_in_item_id' => $item->id,
            ]);
        }
    }

    /**
     * Update a stock in with items.
     */
    public function update(StockIn $stockIn, array $data, array $items): StockIn
    {
        return DB::transaction(function () use ($stockIn, $data, $items) {
            // Cleanup legacy stocks (pre-migration) if needed
            if ($stockIn->status === 'posted') {
                $this->cleanupLegacyStocks($stockIn->items);
            }

            $stockIn->update($data);

            // Delete existing items (this will cascade delete stocks because of stock_in_item_id fk)
            $stockIn->items()->delete();

            foreach ($items as $item) {
                $stockIn->items()->create($item);
            }
            
            // If it was already posted, we need to recreate the stocks for the new items
            if ($stockIn->status === 'posted') {
                $stockIn->load('items'); // Reload items with new IDs
                $this->updateStocksFromItems($stockIn);
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
                    'invoice_price' => $item->invoice_price,
                    'unit_price' => $item->unit_price,
                    'stock_in_item_id' => $item->id,
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
        // Cleanup legacy stocks (pre-migration) if needed
        if ($stockIn->status === 'posted') {
            $this->cleanupLegacyStocks($stockIn->items);
        }

        // Cascade delete ensures stock_in_items and linked stocks are deleted
        return $stockIn->delete();
    }
    
    /**
     * Attempt to find and delete matching stocks for items that don't have stock_in_item_id link.
     */
    protected function cleanupLegacyStocks($items): void
    {
        foreach ($items as $item) {
            // Linked stocks will be handled by cascade, we only look for unlinked ones
            $query = Stock::where('product_id', $item->product_id)
                ->where('distribution_id', $item->stockIn->distribution_id)
                ->where('quantity', $item->quantity)
                ->where('batch_number', $item->batch_number)
                ->whereNull('stock_in_item_id'); // Only target legacy unlinked stocks
            
            // Handle null/empty expiry
            if ($item->expiry_date) {
                $query->whereDate('expiry_date', $item->expiry_date);
            }
            
            $legacyStock = $query->first();
            
            if ($legacyStock) {
                $legacyStock->delete();
            }
        }
    }
}
