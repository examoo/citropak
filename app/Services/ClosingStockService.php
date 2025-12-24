<?php

namespace App\Services;

use App\Models\ClosingStock;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class ClosingStockService
{
    /**
     * Get all closing stocks with filters.
     */
    public function getAll(array $filters = [], $distributionId = null)
    {
        $query = ClosingStock::with(['product', 'distribution', 'creator']);

        if ($distributionId) {
            $query->where('distribution_id', $distributionId);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('dms_code', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date'])) {
            $query->whereDate('date', $filters['date']);
        }

        $result = $query->latest()->paginate(15)->withQueryString();

        // Add available_qty from stocks table for each closing stock
        $result->getCollection()->transform(function ($closingStock) {
            $stockQty = Stock::where('product_id', $closingStock->product_id)
                ->where('distribution_id', $closingStock->distribution_id)
                ->sum('quantity');
            $closingStock->available_qty = (int) $stockQty;
            return $closingStock;
        });

        return $result;
    }

    /**
     * Create a new closing stock entry.
     */
    public function create(array $data): ClosingStock
    {
        // Check for duplicate on same date
        $exists = ClosingStock::where('product_id', $data['product_id'])
            ->where('distribution_id', $data['distribution_id'])
            ->whereDate('date', $data['date'])
            ->exists();
        
        if ($exists) {
            throw new \Exception('Closing stock already exists for this product on this date.');
        }
        
        return ClosingStock::create($data);
    }

    /**
     * Update a closing stock entry.
     */
    public function update(ClosingStock $closingStock, array $data): ClosingStock
    {
        $closingStock->update($data);
        return $closingStock;
    }

    /**
     * Post closing stock (Mark as posted, do NOT deduct stock).
     * Now treated as a Report/Snapshot only.
     */
    public function post(ClosingStock $closingStock): ClosingStock
    {
        // Simply mark as posted status
        $closingStock->update(['status' => 'posted']);
        return $closingStock;
    }

    /**
     * Delete a closing stock entry.
     */
    public function delete(ClosingStock $closingStock): bool
    {
        // Allow deleting posted records now, as they are just reports.
        return $closingStock->delete();
    }

    /**
     * Revert a closing stock entry (Unpost).
     */
    public function revert(ClosingStock $closingStock): ClosingStock
    {
        $closingStock->update(['status' => 'draft']);
        return $closingStock;
    }

    /**
     * Convert available stocks to closing stocks (auto-posted).
     */
    /**
     * Convert available stocks to closing stocks (auto-posted).
     * Aggregates multiple batches of the same product into a single Closing Stock entry.
     */
    public function convertFromStocks($distributionId = null, $userId = null): int
    {
        return DB::transaction(function () use ($distributionId, $userId) {
            $query = Stock::with('product');
            
            if ($distributionId) {
                $query->where('distribution_id', $distributionId);
            }
            
            // Fetch all stocks to process in PHP (easier for weighted averages than complex SQL)
            $stocks = $query->get();
            
            // Group by Product ID
            $groupedStocks = $stocks->groupBy('product_id');
            
            $count = 0;
            $today = now()->format('Y-m-d');
            
            foreach ($groupedStocks as $productId => $productStocks) {
                // Sum quantity
                $totalQty = $productStocks->sum('quantity');
                
                if ($totalQty <= 0) continue; // Skip if total quantity is 0
                
                $firstStock = $productStocks->first();
                $distributionId = $firstStock->distribution_id; // Assuming grouped by dist too if multiple
                
                // Skip if already exists for this product on today's date
                $exists = ClosingStock::where('product_id', $productId)
                    ->where('distribution_id', $distributionId)
                    ->whereDate('date', $today)
                    ->exists();
                
                if ($exists) continue;
                
                // Calculate Weighted Average Unit Cost
                $totalValue = $productStocks->reduce(function ($carry, $item) {
                    return $carry + ($item->quantity * $item->unit_cost);
                }, 0);
                
                $avgUnitCost = $totalValue / $totalQty;
                
                // Calculate cartons/pieces
                $piecesPerCarton = (int) ($firstStock->product->packing ?? 1);
                $cartons = $piecesPerCarton > 0 ? intdiv($totalQty, $piecesPerCarton) : 0;
                $pieces = $piecesPerCarton > 0 ? $totalQty % $piecesPerCarton : $totalQty;
                
                // Create closing stock as POSTED
                ClosingStock::create([
                    'product_id' => $productId,
                    'distribution_id' => $distributionId,
                    'date' => now(),
                    'cartons' => $cartons,
                    'pieces' => $pieces,
                    'pieces_per_carton' => $piecesPerCarton,
                    'quantity' => $totalQty,
                    'batch_number' => null, // Aggregated, so no specific batch
                    'expiry_date' => null,  // Aggregated
                    'location' => null,     // Aggregated
                    'unit_cost' => $avgUnitCost,
                    'notes' => 'Closing stock - Aggregated from available stocks',
                    'status' => 'posted',
                    'created_by' => $userId,
                    // Pricing fields (Take from first stock or Product default)
                    'list_price_before_tax' => $firstStock->list_price_before_tax,
                    'fed_tax_percent' => $firstStock->fed_tax_percent,
                    'fed_sales_tax' => $firstStock->fed_sales_tax,
                    'net_list_price' => $firstStock->net_list_price,
                    'distribution_margin' => $firstStock->distribution_margin,
                    'trade_price_before_tax' => $firstStock->trade_price_before_tax,
                    'fed_2' => $firstStock->fed_2,
                    'sales_tax_3' => $firstStock->sales_tax_3,
                    'net_trade_price' => $firstStock->net_trade_price,
                    'retailer_margin' => $firstStock->retailer_margin,
                    'consumer_price_before_tax' => $firstStock->consumer_price_before_tax,
                    'fed_5' => $firstStock->fed_5,
                    'sales_tax_6' => $firstStock->sales_tax_6,
                    'net_consumer_price' => $firstStock->net_consumer_price,
                    'unit_price' => $firstStock->unit_price,
                    'total_margin' => $firstStock->total_margin,
                ]);
                
                $count++;
            }
            
            return $count;
        });
    }
}
