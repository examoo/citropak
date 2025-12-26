<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with real statistics.
     */
    public function index()
    {
        $currentMonth = now()->startOfMonth();
        
        // Calculate real statistics
        $totalSales = Invoice::where('invoice_date', '>=', $currentMonth)
            ->sum('total_amount');
            
        $invoicesThisMonth = Invoice::where('invoice_date', '>=', $currentMonth)
            ->count();
            
        $totalCredit = Invoice::where('is_credit', true)
            ->sum('total_amount');
            
        $totalProducts = Product::count();
        
        // Get recent activity (latest 10 invoices)
        $recentActivity = Invoice::with(['customer', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer?->shop_name ?? 'N/A',
                    'total_amount' => number_format($invoice->total_amount, 2),
                    'is_credit' => $invoice->is_credit,
                    'invoice_type' => $invoice->invoice_type,
                    'created_by' => $invoice->createdBy?->name ?? 'System',
                    'created_at' => $invoice->created_at->diffForHumans(),
                    'status' => $invoice->is_credit ? 'Credit' : 'Paid',
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalSales' => number_format($totalSales, 2),
                'invoicesThisMonth' => $invoicesThisMonth,
                'totalCredit' => number_format($totalCredit, 2),
                'totalProducts' => number_format($totalProducts),
            ],
            'recentActivity' => $recentActivity,
        ]);
    }
}
