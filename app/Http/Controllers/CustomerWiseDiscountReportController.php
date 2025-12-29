<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
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

        $query = Invoice::query()
            ->select(
                'customer_id',
                DB::raw('SUM(subtotal) as total_gross'), // subtotal in invoice is exclusive amount, but usually gross sale means before discount. Let's check logic. 
                // In CustomerSalesReportController: gross_sale = subtotal + discount_amount. 
                // Invoice subtotal is usually net of item discount but before invoice discount? 
                // Let's rely on calculation: 
                // Gross Amount = Sum of (Item Total + Item Discount) roughly, or just Invoice Subtotal + Invoice Discount.
                // Let's use: Gross = subtotal + discount_amount
                DB::raw('SUM(subtotal + discount_amount) as total_gross_amount'),
                DB::raw('SUM(discount_amount) as total_discount_amount'),
                DB::raw('SUM(subtotal) as total_net_amount')
            )
            ->with(['customer:id,customer_code,shop_name'])
            ->whereDate('invoice_date', '>=', $dateFrom)
            ->whereDate('invoice_date', '<=', $dateTo)
            ->groupBy('customer_id');

        if (!empty($customerIds)) {
            $query->whereIn('customer_id', $customerIds);
        }

        $reportData = $query->get()->map(function ($item) {
            return [
                'customer_id' => $item->customer_id,
                'customer_code' => $item->customer->customer_code ?? '-',
                'customer_name' => $item->customer->shop_name ?? '-',
                'total_gross_amount' => (float) $item->total_gross_amount,
                'total_discount_amount' => (float) $item->total_discount_amount,
                'total_net_amount' => (float) $item->total_net_amount,
            ];
        });

        // Calculate Totals
        $totals = [
            'gross_amount' => $reportData->sum('total_gross_amount'),
            'discount_amount' => $reportData->sum('total_discount_amount'),
            'net_amount' => $reportData->sum('total_net_amount'),
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
