<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class SaleTaxInvoicesReportController extends Controller
{
    public function index(Request $request): Response
    {
        // Default to current month/year
        $month = $request->input('month', now()->format('m'));
        $year = $request->input('year', now()->format('Y'));

        // Query Invoices
        $invoices = Invoice::query()
            ->with(['customer'])
            ->withSum('items', 'extra_tax_amount')
            ->withSum('items', 'adv_tax_amount')
            ->withSum('items', 'retail_margin')
            ->withSum('items', 'exclusive_amount')
            ->whereYear('invoice_date', $year)
            ->whereMonth('invoice_date', $month)
            ->orderBy('invoice_date')
            ->orderBy('invoice_number')
            ->get();

        // Process data for the report
        $reportData = $invoices->map(function ($invoice) {
            // Determine Buyer Name and NTN/CNIC
            $buyerName = $invoice->customer->shop_name ?? $invoice->customer_name ?? 'Walk-in Customer';
            $buyerNtn = $invoice->customer->ntn_number ?? $invoice->customer->cnic ?? '';

            // Calculate Values
            $exclValue = $invoice->items_sum_exclusive_amount ?? 0;
            $tradeDiscount = $invoice->items_sum_retail_margin ?? 0;
            $extraTax = $invoice->items_sum_extra_tax_amount ?? 0;
            $advTax = $invoice->items_sum_adv_tax_amount ?? 0;
            
            // Gross per Show.vue logic (Excl + FED + Sales Tax)
            // Or Excl Value if that's what user means by "Value Excl Tax" (usually is)
            
            return [
                'id' => $invoice->id,
                'invoice_date' => $invoice->invoice_date->format('d-m-Y'),
                'invoice_number' => $invoice->invoice_number,
                // Customer Details
                'customer_code' => $invoice->customer->customer_code ?? '',
                'buyer_name' => $buyerName,
                'address' => $invoice->customer->address ?? '',
                'phone' => $invoice->customer->phone ?? '',
                'buyer_ntn' => $buyerNtn,
                'sales_tax_number' => $invoice->customer->sales_tax_number ?? '',
                'cnic' => $invoice->customer->cnic ?? '',
                'status' => $invoice->customer->status ?? '',
                
                'excl_value' => $exclValue,
                'sales_tax' => $invoice->tax_amount,
                'fed_amount' => $invoice->fed_amount, // Further Tax / FED
                'extra_tax' => $extraTax,
                'gross_amount' => $exclValue + $invoice->tax_amount + $invoice->fed_amount + $extraTax, // Gross Value (Excl + Taxes)
                'trade_discount' => $tradeDiscount,
                'scheme_discount' => $invoice->discount_amount,
                'net_value' => $invoice->subtotal, // Net Sale Value (Inclusive of taxes, minus discounts)
                'adv_tax' => $advTax,
                'total_value' => $invoice->total_amount, // Invoice Value (Net + Adv Tax)
            ];
        });

        // Calculate Totals
        $totals = [
            'excl_value' => $reportData->sum('excl_value'),
            'sales_tax' => $reportData->sum('sales_tax'),
            'fed_amount' => $reportData->sum('fed_amount'),
            'extra_tax' => $reportData->sum('extra_tax'),
            'gross_amount' => $reportData->sum('gross_amount'),
            'trade_discount' => $reportData->sum('trade_discount'),
            'scheme_discount' => $reportData->sum('scheme_discount'),
            'net_value' => $reportData->sum('net_value'),
            'adv_tax' => $reportData->sum('adv_tax'),
            'total_value' => $reportData->sum('total_value'),
        ];

        return Inertia::render('SaleTaxInvoicesReport/Index', [
            'invoices' => $reportData,
            'totals' => $totals,
            'filters' => [
                'month' => $month,
                'year' => $year,
            ],
        ]);
    }

    public function export(Request $request)
    {
        $month = $request->input('month', now()->format('m'));
        $year = $request->input('year', now()->format('Y'));

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\SaleTaxInvoicesExport($month, $year), 'sale-tax-invoices-report.xlsx');
    }
}
