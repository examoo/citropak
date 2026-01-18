<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\OrderBookerTarget;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $orderBooker = $user->orderBooker;

        if (!$orderBooker) {
            return response()->json(['message' => 'Order Booker profile not found.'], 404);
        }

        $currentMonth = now()->format('Y-m');
        
        // Get Targets
        $targets = OrderBookerTarget::where('order_booker_id', $orderBooker->id)
            ->where('month', $currentMonth)
            ->first();

        // Calculate Achieved Sales
        $achieved = Invoice::where('order_booker_id', $orderBooker->id)
            ->where('invoice_type', 'sale')
            ->whereYear('invoice_date', now()->year)
            ->whereMonth('invoice_date', now()->month)
            ->sum('total_amount');

        return response()->json([
            'target_amount' => $targets ? $targets->target_amount : 0,
            'achieved_amount' => $achieved,
            'month' => $currentMonth,
            'order_booker' => $orderBooker->name,
        ]);
    }
}
