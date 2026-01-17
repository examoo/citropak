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

            // Calculate Value Excl Tax (Net Sale)
            // Assuming subtotal is gross, and we deduct discount to get taxable value
            // Or if subtotal already includes something?
            // standard: Taxable Value = (Quantity * Rate) - Discount
            // Invoice model: subtotal, discount_amount
            $taxableValue = $invoice->subtotal - $invoice->discount_amount;
            
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
                
                'subtotal' => $invoice->subtotal, // Gross
                'discount' => $invoice->discount_amount,
                'taxable_value' => $taxableValue,
                'sales_tax' => $invoice->tax_amount,
                'further_tax' => $invoice->fed_amount,
                'total_value' => $invoice->total_amount,
            ];
        });

        // Calculate Totals
        $totals = [
            'subtotal' => $reportData->sum('subtotal'),
            'discount' => $reportData->sum('discount'),
            'taxable_value' => $reportData->sum('taxable_value'),
            'sales_tax' => $reportData->sum('sales_tax'),
            'further_tax' => $reportData->sum('further_tax'),
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
}
