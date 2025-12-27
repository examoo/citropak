<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Recovery;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CreditManagementController extends Controller
{
    /**
     * Credit Management Dashboard/Home
     */
    public function index(): Response
    {
        // Get credit statistics
        $totalCreditInvoices = Invoice::where('is_credit', true)->count();
        $totalCreditAmount = Invoice::where('is_credit', true)->sum('total_amount');
        $totalRecovered = Recovery::sum('amount');
        $pendingAmount = $totalCreditAmount - $totalRecovered;

        // Recent credit invoices
        $recentCreditInvoices = Invoice::where('is_credit', true)
            ->with(['customer', 'van'])
            ->latest('invoice_date')
            ->limit(10)
            ->get()
            ->map(fn($inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'customer_name' => $inv->customer->shop_name ?? '-',
                'customer_code' => $inv->customer->customer_code ?? '-',
                'amount' => $inv->total_amount,
                'date' => $inv->invoice_date->format('Y-m-d'),
                'van' => $inv->van->code ?? '-',
            ]);

        // Recent recoveries
        $recentRecoveries = Recovery::with('invoice.customer')
            ->latest('recovery_date')
            ->limit(10)
            ->get()
            ->map(fn($rec) => [
                'id' => $rec->id,
                'invoice_number' => $rec->invoice->invoice_number ?? '-',
                'customer_name' => $rec->invoice->customer->shop_name ?? '-',
                'amount' => $rec->amount,
                'date' => $rec->recovery_date->format('Y-m-d'),
            ]);

        // Monthly stats (current month)
        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();

        $monthlyCredit = Invoice::where('is_credit', true)
            ->whereBetween('invoice_date', [$currentMonthStart, $currentMonthEnd])
            ->sum('total_amount');

        $monthlyRecovery = Recovery::whereBetween('recovery_date', [$currentMonthStart, $currentMonthEnd])
            ->sum('amount');

        return Inertia::render('CreditManagement/Home', [
            'stats' => [
                'total_credit_invoices' => $totalCreditInvoices,
                'total_credit_amount' => (float) $totalCreditAmount,
                'total_recovered' => (float) $totalRecovered,
                'pending_amount' => (float) $pendingAmount,
                'monthly_credit' => (float) $monthlyCredit,
                'monthly_recovery' => (float) $monthlyRecovery,
            ],
            'recentCreditInvoices' => $recentCreditInvoices,
            'recentRecoveries' => $recentRecoveries,
        ]);
    }

    /**
     * Credit Entries - List all credit invoices
     */
    public function entries(Request $request): Response
    {
        $query = Invoice::where('is_credit', true)
            ->with(['customer', 'van', 'recoveries']);

        // Filters
        if ($request->filled('customer_code')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('customer_code', 'like', '%' . $request->customer_code . '%');
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }

        $invoices = $query->latest('invoice_date')
            ->paginate(20)
            ->through(fn($inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'customer_name' => $inv->customer->shop_name ?? '-',
                'customer_code' => $inv->customer->customer_code ?? '-',
                'amount' => $inv->total_amount,
                'recovered' => $inv->recoveries->sum('amount'),
                'pending' => $inv->total_amount - $inv->recoveries->sum('amount'),
                'date' => $inv->invoice_date->format('Y-m-d'),
                'van' => $inv->van->code ?? '-',
            ]);

        return Inertia::render('CreditManagement/Entries', [
            'invoices' => $invoices,
            'filters' => $request->only(['customer_code', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Summary - Overall credit summary
     */
    public function summary(Request $request): Response
    {
        $month = $request->input('month', date('n'));
        $year = $request->input('year', date('Y'));

        $startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        // Customer-wise summary
        $customerSummary = Invoice::where('is_credit', true)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->with(['customer', 'recoveries'])
            ->get()
            ->groupBy('customer_id')
            ->map(function ($invoices) {
                $customer = $invoices->first()->customer;
                $totalCredit = $invoices->sum('total_amount');
                $totalRecovered = $invoices->flatMap->recoveries->sum('amount');
                
                return [
                    'customer_code' => $customer->customer_code ?? '-',
                    'customer_name' => $customer->shop_name ?? '-',
                    'total_credit' => $totalCredit,
                    'total_recovered' => $totalRecovered,
                    'pending' => $totalCredit - $totalRecovered,
                    'invoice_count' => $invoices->count(),
                ];
            })
            ->values();

        // Generate years for dropdown
        $currentYear = (int) date('Y');
        $years = range($currentYear, $currentYear - 5);

        return Inertia::render('CreditManagement/Summary', [
            'customerSummary' => $customerSummary,
            'filters' => [
                'month' => (int) $month,
                'year' => (int) $year,
            ],
            'years' => $years,
        ]);
    }

    /**
     * Credit Bill Summary - Bill-wise credit summary
     */
    public function billSummary(Request $request): Response
    {
        $query = Invoice::where('is_credit', true)
            ->with(['customer', 'van', 'recoveries']);

        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }

        $bills = $query->latest('invoice_date')
            ->get()
            ->map(fn($inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'customer_code' => $inv->customer->customer_code ?? '-',
                'customer_name' => $inv->customer->shop_name ?? '-',
                'van' => $inv->van->code ?? '-',
                'date' => $inv->invoice_date->format('Y-m-d'),
                'amount' => $inv->total_amount,
                'recovered' => $inv->recoveries->sum('amount'),
                'pending' => $inv->total_amount - $inv->recoveries->sum('amount'),
                'recovery_count' => $inv->recoveries->count(),
            ]);

        $totals = [
            'amount' => $bills->sum('amount'),
            'recovered' => $bills->sum('recovered'),
            'pending' => $bills->sum('pending'),
        ];

        return Inertia::render('CreditManagement/BillSummary', [
            'bills' => $bills,
            'totals' => $totals,
            'filters' => $request->only(['date_from', 'date_to']),
        ]);
    }

    /**
     * Bill-Wise Recovery
     */
    public function billWiseRecovery(Request $request): Response
    {
        $query = Recovery::with(['invoice.customer', 'invoice.van']);

        if ($request->filled('date_from')) {
            $query->whereDate('recovery_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('recovery_date', '<=', $request->date_to);
        }

        $recoveries = $query->latest('recovery_date')
            ->get()
            ->map(fn($rec) => [
                'id' => $rec->id,
                'invoice_number' => $rec->invoice->invoice_number ?? '-',
                'customer_code' => $rec->invoice->customer->customer_code ?? '-',
                'customer_name' => $rec->invoice->customer->shop_name ?? '-',
                'van' => $rec->invoice->van->code ?? '-',
                'invoice_amount' => $rec->invoice->total_amount ?? 0,
                'recovery_amount' => $rec->amount,
                'recovery_date' => $rec->recovery_date->format('Y-m-d'),
            ]);

        $totalRecovered = $recoveries->sum('recovery_amount');

        return Inertia::render('CreditManagement/BillWiseRecovery', [
            'recoveries' => $recoveries,
            'totalRecovered' => $totalRecovered,
            'filters' => $request->only(['date_from', 'date_to']),
        ]);
    }

    /**
     * Daily Credit Report
     */
    public function dailyReport(Request $request): Response
    {
        $date = $request->input('date', date('Y-m-d'));

        // Credit invoices for the date
        $creditInvoices = Invoice::where('is_credit', true)
            ->whereDate('invoice_date', $date)
            ->with(['customer', 'van'])
            ->get()
            ->map(fn($inv) => [
                'invoice_number' => $inv->invoice_number,
                'customer_code' => $inv->customer->customer_code ?? '-',
                'customer_name' => $inv->customer->shop_name ?? '-',
                'van' => $inv->van->code ?? '-',
                'amount' => $inv->total_amount,
            ]);

        // Recoveries for the date
        $recoveries = Recovery::whereDate('recovery_date', $date)
            ->with(['invoice.customer'])
            ->get()
            ->map(fn($rec) => [
                'invoice_number' => $rec->invoice->invoice_number ?? '-',
                'customer_code' => $rec->invoice->customer->customer_code ?? '-',
                'customer_name' => $rec->invoice->customer->shop_name ?? '-',
                'amount' => $rec->amount,
            ]);

        return Inertia::render('CreditManagement/DailyReport', [
            'date' => $date,
            'creditInvoices' => $creditInvoices,
            'recoveries' => $recoveries,
            'totals' => [
                'credit' => $creditInvoices->sum('amount'),
                'recovery' => $recoveries->sum('amount'),
            ],
        ]);
    }

    /**
     * Daily Credit Progress
     */
    public function dailyProgress(Request $request): Response
    {
        $month = $request->input('month', date('n'));
        $year = $request->input('year', date('Y'));

        $startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        // Get daily data
        $dailyData = [];
        $currentDate = $startDate;
        
        while ($currentDate <= $endDate) {
            $dayCredit = Invoice::where('is_credit', true)
                ->whereDate('invoice_date', $currentDate)
                ->sum('total_amount');

            $dayRecovery = Recovery::whereDate('recovery_date', $currentDate)
                ->sum('amount');

            if ($dayCredit > 0 || $dayRecovery > 0) {
                $dailyData[] = [
                    'date' => $currentDate,
                    'credit' => (float) $dayCredit,
                    'recovery' => (float) $dayRecovery,
                    'net' => (float) ($dayCredit - $dayRecovery),
                ];
            }

            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        $totals = [
            'credit' => array_sum(array_column($dailyData, 'credit')),
            'recovery' => array_sum(array_column($dailyData, 'recovery')),
        ];

        // Generate years for dropdown
        $currentYear = (int) date('Y');
        $years = range($currentYear, $currentYear - 5);

        return Inertia::render('CreditManagement/DailyProgress', [
            'dailyData' => $dailyData,
            'totals' => $totals,
            'filters' => [
                'month' => (int) $month,
                'year' => (int) $year,
            ],
            'years' => $years,
        ]);
    }

    /**
     * Search - Search across credit data
     */
    public function search(Request $request): Response
    {
        $query = $request->input('q');
        $results = [];

        if ($query) {
            // Search invoices
            $invoices = Invoice::where('is_credit', true)
                ->where(function ($q) use ($query) {
                    $q->where('invoice_number', 'like', "%$query%")
                        ->orWhereHas('customer', function ($cq) use ($query) {
                            $cq->where('customer_code', 'like', "%$query%")
                                ->orWhere('shop_name', 'like', "%$query%");
                        });
                })
                ->with(['customer', 'van'])
                ->limit(50)
                ->get()
                ->map(fn($inv) => [
                    'type' => 'invoice',
                    'id' => $inv->id,
                    'reference' => $inv->invoice_number,
                    'customer_code' => $inv->customer->customer_code ?? '-',
                    'customer_name' => $inv->customer->shop_name ?? '-',
                    'amount' => $inv->total_amount,
                    'date' => $inv->invoice_date->format('Y-m-d'),
                ]);

            $results = $invoices->toArray();
        }

        return Inertia::render('CreditManagement/Search', [
            'results' => $results,
            'query' => $query,
        ]);
    }

    /**
     * Sales Sheet - Credit sales sheet
     */
    public function salesSheet(Request $request): Response
    {
        $month = $request->input('month', date('n'));
        $year = $request->input('year', date('Y'));

        $startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        $invoices = Invoice::where('is_credit', true)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->with(['customer', 'van', 'items.product'])
            ->latest('invoice_date')
            ->get()
            ->map(fn($inv) => [
                'invoice_number' => $inv->invoice_number,
                'date' => $inv->invoice_date->format('Y-m-d'),
                'customer_code' => $inv->customer->customer_code ?? '-',
                'customer_name' => $inv->customer->shop_name ?? '-',
                'van' => $inv->van->code ?? '-',
                'subtotal' => $inv->subtotal,
                'discount' => $inv->discount_amount,
                'total' => $inv->total_amount,
            ]);

        $totals = [
            'subtotal' => $invoices->sum('subtotal'),
            'discount' => $invoices->sum('discount'),
            'total' => $invoices->sum('total'),
        ];

        // Generate years for dropdown
        $currentYear = (int) date('Y');
        $years = range($currentYear, $currentYear - 5);

        return Inertia::render('CreditManagement/SalesSheet', [
            'invoices' => $invoices,
            'totals' => $totals,
            'filters' => [
                'month' => (int) $month,
                'year' => (int) $year,
            ],
            'years' => $years,
        ]);
    }
}
