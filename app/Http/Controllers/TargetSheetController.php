<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\OrderBooker;
use App\Models\OrderBookerTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TargetSheetController extends Controller
{
    /**
     */
    public function index(Request $request)
    {
        $month = $request->query('month');
        
        // Default to current month if not provided
        if (!$month) {
            $month = date('Y-m');
        }

        // Parse month for date range
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate)); // Last day of month
        
        // Get all active brands
        $brands = Brand::where('status', 'active')->get();

        // Get all order bookers with their targets and achieved sales for the selected month
        $orderBookers = OrderBooker::with(['distribution', 'van'])
            ->get()
            ->map(function ($booker) use ($month, $startDate, $endDate, $brands) {
                $target = OrderBookerTarget::where('order_booker_id', $booker->id)
                    ->where('month', $month)
                    ->first();
                
                $brandTargets = $target?->brand_targets ?? [];
                
                // Calculate achieved amount from invoices
                $achievedTotal = Invoice::where('order_booker_id', $booker->id)
                    ->whereBetween('invoice_date', [$startDate, $endDate])
                    ->sum('total_amount');

                // Calculate brand-wise achieved amount
                $brandAchieved = InvoiceItem::join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                    ->join('products', 'invoice_items.product_id', '=', 'products.id')
                    ->where('invoices.order_booker_id', $booker->id)
                    ->whereBetween('invoices.invoice_date', [$startDate, $endDate])
                    ->select('products.brand_id', DB::raw('SUM(invoice_items.line_total) as total'))
                    ->groupBy('products.brand_id')
                    ->pluck('total', 'brand_id');
                
                $targetAmount = $target?->target_amount ?? 0;
                $percentage = $targetAmount > 0 ? round(($achievedTotal / $targetAmount) * 100, 1) : 0;

                // Prepare brand data
                $brandDetails = $brands->map(function ($brand) use ($brandTargets, $brandAchieved) {
                    $bTarget = $brandTargets[$brand->id] ?? 0;
                    $bAchieved = $brandAchieved[$brand->id] ?? 0;
                    return [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'target' => $bTarget,
                        'achieved' => $bAchieved,
                        'percentage' => $bTarget > 0 ? round(($bAchieved / $bTarget) * 100, 1) : 0,
                    ];
                });
                
                return [
                    'id' => $booker->id,
                    'name' => $booker->name,
                    'code' => $booker->code,
                    'van' => $booker->van?->code,
                    'distribution' => $booker->distribution?->name,
                    'distribution_code' => $booker->distribution?->code,
                    'target_amount' => $targetAmount,
                    'achieved_amount' => $achievedTotal,
                    'percentage' => $percentage,
                    'has_target' => $target !== null,
                    'brand_details' => $brandDetails,
                ];
            });

        // Group by distribution
        $groupedByDistribution = $orderBookers->groupBy('distribution');

        // Calculate totals
        $totalTarget = $orderBookers->sum('target_amount');
        $totalAchieved = $orderBookers->sum('achieved_amount');
        $overallPercentage = $totalTarget > 0 ? round(($totalAchieved / $totalTarget) * 100, 1) : 0;
        $bookersWithTarget = $orderBookers->where('has_target', true)->count();
        $bookersWithoutTarget = $orderBookers->where('has_target', false)->count();

        return Inertia::render('TargetSheets/Index', [
            'orderBookers' => $groupedByDistribution,
            'brands' => $brands,
            'filters' => ['month' => $month],
            'stats' => [
                'total_target' => $totalTarget,
                'total_achieved' => $totalAchieved,
                'overall_percentage' => $overallPercentage,
                'bookers_with_target' => $bookersWithTarget,
                'bookers_without_target' => $bookersWithoutTarget,
                'total_bookers' => $orderBookers->count(),
            ],
        ]);
    }
}
