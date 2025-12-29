<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class CustomerWiseDiscountReportController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));
        $customerIds = $request->input('customer_ids', []);

        // Use InvoiceItem query to aggregate everything including free items
        $query = InvoiceItem::query()
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->whereDate('invoices.invoice_date', '>=', $dateFrom)
            ->whereDate('invoices.invoice_date', '<=', $dateTo)
            ->where('invoices.invoice_type', 'sale') // Ensure we only get sales
            ->select(
                'invoices.customer_id',
                'customers.customer_code',
                'customers.shop_name as customer_name',
                DB::raw('SUM(invoice_items.gross_amount) as total_gross_amount'),
                DB::raw('SUM(invoice_items.discount) as total_discount_amount'),
                DB::raw('SUM(invoice_items.line_total) as total_net_amount'),
                DB::raw('SUM(CASE WHEN invoice_items.is_free = 1 THEN invoice_items.total_pieces ELSE 0 END) as free_quantity')
            )
            ->groupBy('invoices.customer_id', 'customers.customer_code', 'customers.shop_name');

        if (!empty($customerIds)) {
            $query->whereIn('invoices.customer_id', $customerIds);
        }

        $reportData = $query->get()->map(function ($item) {
            return [
                'customer_id' => $item->customer_id,
                'customer_code' => $item->customer_code,
                'customer_name' => $item->customer_name,
                'total_gross_amount' => (float) $item->total_gross_amount,
                'total_discount_amount' => (float) $item->total_discount_amount,
                'total_net_amount' => (float) $item->total_net_amount,
                'free_quantity' => (int) $item->free_quantity,
            ];
        });

        // Calculate Totals
        $totals = [
            'gross_amount' => $reportData->sum('total_gross_amount'),
            'discount_amount' => $reportData->sum('total_discount_amount'),
            'net_amount' => $reportData->sum('total_net_amount'),
            'free_quantity' => $reportData->sum('free_quantity'),
        ];

        return Inertia::render('CustomerWiseDiscountReport/Index', [
            'reportData' => $reportData,
            'totals' => $totals,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'customer_ids' => $customerIds,
            ],
            'customers' => Customer::select('id', 'customer_code', 'shop_name')
                ->orderBy('shop_name')
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'name' => "{$c->customer_code} - {$c->shop_name}",
                ]),
        ]);
    }
}
