<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Recovery;
use App\Models\Van;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DayClosingController extends Controller
{
    public function index(Request $request): Response
    {
        $date = $request->input('date', date('Y-m-d'));
        $vanCode = $request->input('van');

        $data = [];

        // Build query
        $invoiceQuery = Invoice::whereDate('invoice_date', $date);
        $recoveryQuery = Recovery::whereDate('recovery_date', $date);

        if ($vanCode) {
            $invoiceQuery->whereHas('van', fn($q) => $q->where('code', $vanCode));
            $recoveryQuery->whereHas('invoice.van', fn($q) => $q->where('code', $vanCode));
        }

        // Get invoices
        $invoices = $invoiceQuery->with(['customer', 'van'])->get();
        $recoveries = $recoveryQuery->with(['invoice.customer'])->get();

        // Cash invoices (is_credit = false or null)
        $cashInvoices = $invoices->filter(fn($i) => !$i->is_credit);
        $creditInvoices = $invoices->filter(fn($i) => $i->is_credit);

        // Calculate totals
        $data = [
            'date' => $date,
            'van' => $vanCode,
            'cash_sales' => [
                'count' => $cashInvoices->count(),
                'total' => $cashInvoices->sum('total_amount'),
            ],
            'credit_sales' => [
                'count' => $creditInvoices->count(),
                'total' => $creditInvoices->sum('total_amount'),
            ],
            'total_sales' => [
                'count' => $invoices->count(),
                'total' => $invoices->sum('total_amount'),
            ],
            'recovery' => [
                'count' => $recoveries->count(),
                'total' => $recoveries->sum('amount'),
            ],
            'net_collection' => $cashInvoices->sum('total_amount') + $recoveries->sum('amount'),
            'invoices' => $invoices->map(fn($inv) => [
                'invoice_number' => $inv->invoice_number,
                'customer_code' => $inv->customer->customer_code ?? '-',
                'customer_name' => $inv->customer->shop_name ?? '-',
                'van' => $inv->van->code ?? '-',
                'amount' => $inv->total_amount,
                'is_credit' => $inv->is_credit,
            ]),
            'recoveries' => $recoveries->map(fn($rec) => [
                'invoice_number' => $rec->invoice->invoice_number ?? '-',
                'customer_code' => $rec->invoice->customer->customer_code ?? '-',
                'customer_name' => $rec->invoice->customer->shop_name ?? '-',
                'amount' => $rec->amount,
            ]),
        ];

        // Get vans for filter
        $vans = Van::where('status', 'active')
            ->orderBy('code')
            ->get()
            ->map(fn($v) => ['code' => $v->code, 'name' => $v->code]);

        return Inertia::render('DayClosing/Index', [
            'data' => $data,
            'vans' => $vans,
            'filters' => [
                'date' => $date,
                'van' => $vanCode,
            ],
        ]);
    }
}
