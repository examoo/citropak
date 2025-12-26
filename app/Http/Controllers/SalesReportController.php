<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Van;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesReportController extends Controller
{
    /**
     * Display the sales report.
     */
    public function index(Request $request): Response
    {
        $vanId = $request->input('van_id');
        $orderBookerId = $request->input('order_booker_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Invoice::query()
            ->with(['customer', 'van', 'orderBooker']);

        // Only apply date filter if provided
        if ($dateFrom) {
            $query->whereDate('invoice_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('invoice_date', '<=', $dateTo);
        }

        if ($vanId) {
            $query->where('van_id', $vanId);
        }

        if ($orderBookerId) {
            $query->where('order_booker_id', $orderBookerId);
        }

        $invoices = $query->latest('invoice_date')->get()->map(fn($invoice) => [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->invoice_date,
            'order_booker' => $invoice->orderBooker->name ?? '-',
            'van' => $invoice->van->code ?? '-',
            'customer_code' => $invoice->customer->customer_code ?? '-',
            'customer_name' => $invoice->customer->shop_name ?? '-',
            'total_amount' => $invoice->total_amount,
            'is_credit' => $invoice->is_credit,
        ]);

        // Totals
        $totalAmount = $invoices->sum('total_amount');

        return Inertia::render('SalesReport/Index', [
            'invoices' => $invoices,
            'totalAmount' => $totalAmount,
            'filters' => [
                'van_id' => $vanId,
                'order_booker_id' => $orderBookerId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'vans' => Van::where('status', 'active')->get(['id', 'code as name']),
            'orderBookers' => User::where('role', 'order_booker')->get(['id', 'name']),
        ]);
    }
}
