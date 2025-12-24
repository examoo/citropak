<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\OpeningStock;
use App\Models\StockInItem;
use App\Models\StockOutItem;
use App\Models\ClosingStock;
use App\Models\Stock;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class StockReportController extends Controller
{
    public function index(Request $request): Response
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        $inputDistributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($inputDistributionId === 'all') $inputDistributionId = null;

        // Determine if we are filtering by a specific distribution or showing all
        $distributions = $inputDistributionId 
            ? \App\Models\Distribution::where('id', $inputDistributionId)->get() 
            : \App\Models\Distribution::all();

        $filters = $request->only(['search']);
        $products = Product::query();
        if ($filters['search'] ?? false) {
             $products->where('name', 'like', '%' . $filters['search'] . '%')
                 ->orWhere('dms_code', 'like', '%' . $filters['search'] . '%');
        }
        $products = $products->get();

        $report = [];

        foreach ($products as $product) {
            foreach ($distributions as $distribution) {
                // Determine if there is any activity for this product/distrib pair to avoid clutter?
                // For now, let's just generate for all active combinations or maybe skip if all zeros?
                // User requirement implies seeing report for distributions.
                
                $distributionId = $distribution->id;

                // Opening Stock
                $opening = OpeningStock::where('product_id', $product->id)
                    ->whereDate('date', $date)
                    ->where('distribution_id', $distributionId)
                    ->value('quantity') ?? 0;

                // Stock In
                $in = StockInItem::where('product_id', $product->id)
                    ->whereHas('stockIn', function ($q) use ($date, $distributionId) {
                        $q->whereDate('date', $date)
                          ->where('distribution_id', $distributionId);
                    })
                    ->sum('quantity');

                // Stock Out
                $out = StockOutItem::whereHas('stock', function($q) use ($product) {
                        $q->where('product_id', $product->id);
                    })
                    ->whereHas('stockOut', function ($q) use ($date, $distributionId) {
                        $q->whereDate('date', $date)
                          ->where('distribution_id', $distributionId);
                    })
                    ->sum('quantity');

                // Closing Stock OR Available
                $closingRaw = ClosingStock::where('product_id', $product->id)
                    ->whereDate('date', $date)
                    ->where('distribution_id', $distributionId)
                    ->first();
                
                $closing = $closingRaw ? $closingRaw->quantity : null;
                $isClosed = $closingRaw !== null;
                
                $available = null;
                if (!$isClosed && $date === now()->format('Y-m-d')) {
                     $available = Stock::where('product_id', $product->id)
                        ->where('distribution_id', $distributionId)
                        ->sum('quantity');
                }

                // Optimization: Skip completely empty rows if "All" is selected? 
                // But user might want to see 0s. 
                // Let's at least skip if Product has NO association with current filter? 
                // Keeping it simple: Show all.
                
                // If everything is 0/null, maybe skip?
                if ($opening == 0 && $in == 0 && $out == 0 && $closing === null && $available == 0) {
                   // continue; // Optional: Uncomment to hide empty rows
                }

                $report[] = [
                    'id' => $product->id . '-' . $distributionId, // Unique Key
                    'product_name' => $product->name,
                    'product_code' => $product->dms_code,
                    'distribution_name' => $distribution->name, // Assuming 'name' exists
                    'distribution_code' => $distribution->code ?? $distribution->name,
                    'opening' => $opening,
                    'in' => $in,
                    'out' => $out,
                    'closing' => $closing,
                    'available' => $available,
                    'is_closed' => $isClosed
                ];
            }
        }

        return Inertia::render('StockReport/Index', [
            'report' => $report,
            'filters' => [
                'date' => $date,
                'search' => $filters['search'] ?? ''
            ],
            'showDistributionColumn' => $inputDistributionId === null // True if "All" selected
        ]);
    }
}
