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
    public function getAll(array $filters = [], $distributionId = null)
    {
        $query = StockIn::with(['distribution', 'items.product', 'creator']);

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
                    // Pricing fields
                    'list_price_before_tax' => $item->list_price_before_tax,
                    'fed_tax_percent' => $item->fed_tax_percent,
                    'fed_sales_tax' => $item->fed_sales_tax,
                    'net_list_price' => $item->net_list_price,
                    'distribution_margin' => $item->distribution_margin,
                    'trade_price_before_tax' => $item->trade_price_before_tax,
                    'fed_2' => $item->fed_2,
                    'sales_tax_3' => $item->sales_tax_3,
                    'net_trade_price' => $item->net_trade_price,
                    'retailer_margin' => $item->retailer_margin,
                    'consumer_price_before_tax' => $item->consumer_price_before_tax,
                    'fed_5' => $item->fed_5,
                    'sales_tax_6' => $item->sales_tax_6,
                    'net_consumer_price' => $item->net_consumer_price,
                    'unit_price' => $item->unit_price,
                    'total_margin' => $item->total_margin,
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
