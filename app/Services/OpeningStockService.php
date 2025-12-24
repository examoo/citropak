<?php

namespace App\Services;

use App\Models\OpeningStock;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class OpeningStockService
{
    /**
     * Get all opening stocks with filters.
     */
    public function getAll(array $filters = [], $distributionId = null)
    {
        $query = OpeningStock::with(['product', 'distribution', 'creator']);

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

        // Add available_qty from stocks table for each opening stock
        $result->getCollection()->transform(function ($openingStock) {
            $stockQty = Stock::where('product_id', $openingStock->product_id)
                ->where('distribution_id', $openingStock->distribution_id)
                ->sum('quantity');
            $openingStock->available_qty = (int) $stockQty;
            return $openingStock;
        });

        return $result;
    }

    /**
     * Create a new opening stock entry.
     */
    public function create(array $data): OpeningStock
    {
        // Check for duplicate on same date
        $exists = OpeningStock::where('product_id', $data['product_id'])
            ->where('distribution_id', $data['distribution_id'])
            ->whereDate('date', $data['date'])
            ->exists();
        
        if ($exists) {
            throw new \Exception('Opening stock already exists for this product on this date.');
        }
        
        return OpeningStock::create($data);
    }

    /**
     * Update an opening stock entry.
     */
    public function update(OpeningStock $openingStock, array $data): OpeningStock
    {
        $openingStock->update($data);
        return $openingStock;
    }

    /**
     * Post opening stock (Mark as posted, do NOT add stock).
     * Now treated as a Report/Snapshot only.
     */
    public function post(OpeningStock $openingStock): OpeningStock
    {
        // Simply mark as posted status
        $openingStock->update(['status' => 'posted']);
        return $openingStock;
    }

    /**
     * Delete an opening stock entry.
     */
    public function delete(OpeningStock $openingStock): bool
    {
        if ($openingStock->status === 'posted') {
            throw new \Exception('Cannot delete a posted opening stock.');
        }
        return $openingStock->delete();
    }

    /**
     * Convert available stocks to opening stocks.
     */

    /**
     * Convert available stocks to opening stocks.
     * Aggregates multiple batches of the same product into a single Opening Stock entry.
     */
    public function convertFromStocks($distributionId = null, $userId = null): int
    {
        return DB::transaction(function () use ($distributionId, $userId) {
            $query = Stock::with('product');
            
            if ($distributionId) {
                $query->where('distribution_id', $distributionId);
            }
            
            // Fetch all stocks to process in PHP
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
                $distributionId = $firstStock->distribution_id;
                
                // Skip if already exists for this product on today's date
                $exists = OpeningStock::where('product_id', $productId)
                    ->where('distribution_id', $distributionId)
                    ->whereDate('date', $today)
                    ->exists();
                
                if ($exists) continue;
                
                // Calculate Weighted Average Unit Cost (Optional for Opening, but good practice)
                $totalValue = $productStocks->reduce(function ($carry, $item) {
                    return $carry + ($item->quantity * $item->unit_cost);
                }, 0);
                $avgUnitCost = $totalQty > 0 ? $totalValue / $totalQty : 0;
                
                // Calculate cartons/pieces
                $piecesPerCarton = (int) ($firstStock->product->packing ?? 1);
                $cartons = $piecesPerCarton > 0 ? intdiv($totalQty, $piecesPerCarton) : 0;
                $pieces = $piecesPerCarton > 0 ? $totalQty % $piecesPerCarton : $totalQty;
                
                OpeningStock::create([
                    'product_id' => $productId,
                    'distribution_id' => $distributionId,
                    'date' => now()->format('Y-m-d'),
                    'cartons' => $cartons,
                    'pieces' => $pieces,
                    'pieces_per_carton' => $piecesPerCarton,
                    'quantity' => $totalQty,
                    'batch_number' => null, // Aggregated
                    'expiry_date' => null,  // Aggregated
                    'location' => null,     // Aggregated
                    'unit_cost' => $avgUnitCost,
                    'notes' => 'Converted from available stock',
                    'status' => 'posted', // Assuming converted is posted? Or maybe draft as per user request logic?
                    // Previous logic was 'draft' or 'posted'? 
                    // OpeningStockService previously set 'draft'. Let's stick to 'draft' or 'posted'.
                    // ClosingStockService sets 'posted'.
                    // Opening stocks are usually "starts" so maybe 'posted'?
                    // I will set 'posted' to align with Closing Stock behavior, or check previous code.
                    // Previous code (lines 144) set 'draft'. I will change to 'posted'?
                    // "we have 51 qty... convert it into opening it must be 51 in opening".
                    // I'll set it to 'posted' so it shows up, or 'draft' if they want verification.
                    // User said "converted... must be 51". I'll use 'posted' for consistency with "snapshot".
                    // Wait, previous code used 'draft' (lines 144). I'll use 'draft' to be safe unless user specified "auto-post".
                    // ClosingStockService uses 'posted'.
                    // I'll use 'draft' for Opening Stock to allow review, matching previous behavior.
                    'status' => 'draft', 
                    'created_by' => $userId,
                    // Pricing fields
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
