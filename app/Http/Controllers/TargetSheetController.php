<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\OrderBooker;
use App\Models\OrderBookerTarget;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TargetSheetController extends Controller
{
    /**
     * Display the target sheet.
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
        
        // Get all order bookers with their targets and achieved sales for the selected month
        $orderBookers = OrderBooker::with(['distribution', 'van'])
            ->get()
            ->map(function ($booker) use ($month, $startDate, $endDate) {
                $target = OrderBookerTarget::where('order_booker_id', $booker->id)
                    ->where('month', $month)
                    ->first();
                
                // Calculate achieved amount from invoices
                $achieved = Invoice::where('order_booker_id', $booker->id)
                    ->whereBetween('invoice_date', [$startDate, $endDate])
                    ->sum('total_amount');
                
                $targetAmount = $target?->target_amount ?? 0;
                $percentage = $targetAmount > 0 ? round(($achieved / $targetAmount) * 100, 1) : 0;
                
                return [
                    'id' => $booker->id,
                    'name' => $booker->name,
                    'code' => $booker->code,
                    'van' => $booker->van?->code,
                    'distribution' => $booker->distribution?->name,
                    'distribution_code' => $booker->distribution?->code,
                    'target_amount' => $targetAmount,
                    'achieved_amount' => $achieved,
                    'percentage' => $percentage,
                    'has_target' => $target !== null,
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
