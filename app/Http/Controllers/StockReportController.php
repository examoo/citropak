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
use Carbon\Carbon;

class StockReportController extends Controller
{
    public function index(Request $request): Response
    {
        // Support both date and month filter
        $month = $request->input('month', now()->format('Y-m'));
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = Carbon::parse($month . '-01')->endOfMonth();
        
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
                $distributionId = $distribution->id;

                // Previous month dates for opening stock calculation
                $prevMonthEnd = $startDate->copy()->subDay()->endOfDay();
                $prevMonthStart = $prevMonthEnd->copy()->startOfMonth();

                // Opening Stock: Try multiple sources
                // 1. First, try explicit OpeningStock record for the month
                $opening = OpeningStock::where('product_id', $product->id)
                    ->whereDate('date', $startDate)
                    ->where('distribution_id', $distributionId)
                    ->value('quantity');
                
                // 2. If no opening stock, try previous month's closing stock
                if ($opening === null) {
                    $prevClosing = ClosingStock::where('product_id', $product->id)
                        ->whereDate('date', $prevMonthEnd)
                        ->where('distribution_id', $distributionId)
                        ->value('quantity');
                    
                    if ($prevClosing !== null) {
                        $opening = $prevClosing;
                    }
                }

                // 3. If still no opening, calculate from current stock minus net movements since month start
                if ($opening === null) {
                    $currentStock = Stock::where('product_id', $product->id)
                        ->where('distribution_id', $distributionId)
                        ->sum('quantity');
                    
                    // Get all movements from month start to now
                    $totalInSinceStart = StockInItem::where('product_id', $product->id)
                        ->whereHas('stockIn', function ($q) use ($startDate, $distributionId) {
                            $q->where('date', '>=', $startDate)
                              ->where('distribution_id', $distributionId);
                        })
                        ->sum('quantity');
                    
                    $totalOutSinceStart = StockOutItem::where('product_id', $product->id)
                        ->whereHas('stockOut', function ($q) use ($startDate, $distributionId) {
                            $q->where('date', '>=', $startDate)
                              ->where('distribution_id', $distributionId);
                        })
                        ->sum('quantity');
                    
                    // Opening = Current - In + Out (reverse the movements)
                    $opening = $currentStock - $totalInSinceStart + $totalOutSinceStart;
                }

                $opening = $opening ?? 0;

                // Stock In (for the entire month)
                $in = StockInItem::where('product_id', $product->id)
                    ->whereHas('stockIn', function ($q) use ($startDate, $endDate, $distributionId) {
                        $q->whereBetween('date', [$startDate, $endDate])
                          ->where('distribution_id', $distributionId);
                    })
                    ->sum('quantity');

                // Stock Out (for the entire month)
                $out = StockOutItem::where('product_id', $product->id)
                    ->whereHas('stockOut', function ($q) use ($startDate, $endDate, $distributionId) {
                        $q->whereBetween('date', [$startDate, $endDate])
                          ->where('distribution_id', $distributionId);
                    })
                    ->sum('quantity');

                // Closing Stock (from the last day of the month, or current stock if current month)
                $closingRaw = ClosingStock::where('product_id', $product->id)
                    ->whereDate('date', $endDate)
                    ->where('distribution_id', $distributionId)
                    ->first();
                
                $closing = $closingRaw ? $closingRaw->quantity : null;
                $isClosed = $closingRaw !== null;
                
                // Available: If current month and not closed, show current stock
                $available = null;
                $isCurrentMonth = $month === now()->format('Y-m');
                if (!$isClosed && $isCurrentMonth) {
                     $available = Stock::where('product_id', $product->id)
                        ->where('distribution_id', $distributionId)
                        ->sum('quantity');
                }

                // Calculate closing if not explicitly saved (Opening + In - Out)
                if ($closing === null && !$isCurrentMonth) {
                    $closing = $opening + $in - $out;
                }

                // Skip empty rows
                if ($opening == 0 && $in == 0 && $out == 0 && $closing === null && ($available ?? 0) == 0) {
                    continue;
                }

                $report[] = [
                    'id' => $product->id . '-' . $distributionId,
                    'product_name' => $product->name,
                    'product_code' => $product->dms_code,
                    'distribution_name' => $distribution->name,
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
                'month' => $month,
                'search' => $filters['search'] ?? ''
            ],
            'showDistributionColumn' => $inputDistributionId === null // True if "All" selected
        ]);
    }
}

