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
     * Aggregates multiple batches of the same product into a single Opening Stock entry.
     */
    public function convertFromStocks($distributionId = null, $userId = null): int
    {
        return DB::transaction(function () use ($distributionId, $userId) {
            $query = Stock::with('product');
            
            if ($distributionId) {
                $query->where('distribution_id', $distributionId);
            }
            
            $stocks = $query->get();
            $groupedStocks = $stocks->groupBy('product_id');
            
            $count = 0;
            $today = now()->format('Y-m-d');
            
            foreach ($groupedStocks as $productId => $productStocks) {
                $totalQty = $productStocks->sum('quantity');
                
                if ($totalQty <= 0) continue;
                
                $firstStock = $productStocks->first();
                $distributionId = $firstStock->distribution_id;
                
                $exists = OpeningStock::where('product_id', $productId)
                    ->where('distribution_id', $distributionId)
                    ->whereDate('date', $today)
                    ->exists();
                
                if ($exists) continue;
                
                $totalValue = $productStocks->reduce(function ($carry, $item) {
                    return $carry + ($item->quantity * $item->unit_cost);
                }, 0);
                $avgUnitCost = $totalQty > 0 ? $totalValue / $totalQty : 0;
                
                $piecesPerCarton = (int) ($firstStock->pieces_per_packing ?? $firstStock->product->pieces_per_packing ?? 1);
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
                    'batch_number' => null,
                    'expiry_date' => null,
                    'location' => null,
                    'unit_cost' => $avgUnitCost,
                    'notes' => 'Converted from available stock',
                    'status' => 'draft',
                    'created_by' => $userId,
                    // New simplified pricing fields
                    'pieces_per_packing' => $firstStock->pieces_per_packing ?? $piecesPerCarton,
                    'list_price_before_tax' => $firstStock->list_price_before_tax,
                    'fed_sales_tax' => $firstStock->fed_sales_tax,
                    'fed_percent' => $firstStock->fed_percent,
                    'retail_margin' => $firstStock->retail_margin,
                    'tp_rate' => $firstStock->tp_rate,
                    'distribution_margin' => $firstStock->distribution_margin,
                    'invoice_price' => $firstStock->invoice_price,
                    'unit_price' => $firstStock->unit_price,
                ]);
                
                $count++;
            }
            
            return $count;
        });
    }
}
