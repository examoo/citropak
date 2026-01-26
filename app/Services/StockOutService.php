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
    /**
     * Get all stock out items with filters.
     */
    public function getAll(array $filters = [], $distributionId = null)
    {
        $query = \App\Models\StockOutItem::query()
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->select('stock_out_items.*', 'stock_outs.date', 'stock_outs.bilty_number', 'stock_outs.status', 'stock_outs.distribution_id as parent_distribution_id')
            ->with(['stockOut.distribution', 'product']);

        if ($distributionId) {
            $query->where('stock_outs.distribution_id', $distributionId);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('stock_outs.bilty_number', 'like', "%{$search}%")
                  ->orWhereHas('product', function($p) use ($search) {
                      $p->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('stock_outs.status', $filters['status']);
        }

        return $query->latest('stock_outs.date')->paginate(15)->withQueryString();
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
    /**
     * Reverse and delete a stock out.
     */
    public function reverseAndDelete(StockOut $stockOut)
    {
        return DB::transaction(function () use ($stockOut) {
            foreach ($stockOut->items as $item) {
                // Find original stock or matching stock
                $stock = null;
                
                if ($item->stock_id) {
                    $stock = Stock::lockForUpdate()->find($item->stock_id);
                }

                if (!$stock) {
                    // Try to find matching stock by product + distribution + batch
                    $query = Stock::where('product_id', $item->product_id)
                        ->where('distribution_id', $stockOut->distribution_id);
                    
                    if ($item->batch_number) {
                        $query->where('batch_number', $item->batch_number);
                    }
                    
                    $stock = $query->first();
                }

                if ($stock) {
                    $stock->increment('quantity', $item->quantity);
                } else {
                    // Create new stock if necessary info is present to restore inventory
                     Stock::create([
                        'distribution_id' => $stockOut->distribution_id,
                        'product_id' => $item->product_id,
                        'batch_number' => $item->batch_number ?? 'Restored',
                        'expiry_date' => $item->expiry_date,
                        'quantity' => $item->quantity,
                        'unit_cost' => $item->unit_cost ?? 0,
                        'location' => $item->location ?? 'Default',
                     ]);
                }
            }
            
            $stockOut->delete();
            return true;
        });
    }
    /**
     * Create and post a StockOut record for the invoice.
     * Shared logic for Web and API.
     */
    public function createFromInvoice(\App\Models\Invoice $invoice)
    {
        // Group items by product to handle allocation
        $itemsByProduct = $invoice->items->groupBy('product_id');
        $distributionId = $invoice->distribution_id;

        $stockOutItems = [];

        foreach ($itemsByProduct as $productId => $items) {
            $totalQuantity = $items->sum('total_pieces');
            $remainingQty = $totalQuantity;

            // FIFO: Get oldest stocks first
            $stocks = Stock::where('product_id', $productId)
                ->where('distribution_id', $distributionId)
                ->where('quantity', '>', 0)
                ->orderBy('created_at', 'asc') // FIFO by creation date
                ->get();

            foreach ($stocks as $stock) {
                if ($remainingQty <= 0) break;

                $takeQty = min($remainingQty, $stock->quantity);

                $stockOutItems[] = [
                    'stock_id' => $stock->id,
                    'product_id' => $productId,
                    'quantity' => $takeQty,
                    'batch_number' => $stock->batch_number,
                    'expiry_date' => $stock->expiry_date,
                    'location' => $stock->location,
                    'unit_cost' => $stock->unit_cost,
                ];

                $remainingQty -= $takeQty;
            }

            // If we still have remaining quantity (insufficient stock),
            // we create an item without a stock_id
            if ($remainingQty > 0) {
                $stockOutItems[] = [
                    'stock_id' => null, 
                    'product_id' => $productId,
                    'quantity' => $remainingQty,
                    'batch_number' => null,
                    'expiry_date' => null,
                    'location' => null,
                    'unit_cost' => 0, 
                ];
            }
        }

        if (!empty($stockOutItems)) {
            $stockOutData = [
                'distribution_id' => $distributionId,
                'bilty_number' => $invoice->invoice_number,
                'date' => $invoice->invoice_date,
                'status' => 'posted', // Auto-post on invoice creation
                'gate_pass_number' => null,
                'vehicle_number' => null,
                'builty_number_2' => null,
                'notes' => 'Auto-generated from Invoice #' . $invoice->invoice_number,
                'created_by' => $invoice->created_by ?? auth()->id(),
            ];

            $stockOut = StockOut::create($stockOutData);

            foreach ($stockOutItems as $item) {
                $stockOut->items()->create($item);
            }

            // Auto-post: deduct stock immediately
            $this->post($stockOut);
        }
    }
}
