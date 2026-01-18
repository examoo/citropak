<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Recovery;
use App\Models\Van;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesSheetController extends Controller
{
    /**
     * Display the sales sheet report.
     */
    public function index(Request $request): Response
    {
        $vanId = $request->input('van_id');
        $dateFrom = $request->input('date_from', now()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        // Get invoices for the selected van and date range
        $invoicesQuery = Invoice::query()
            ->with(['customer', 'items.product'])
            ->where('van_id', $vanId)
            ->whereDate('invoice_date', '>=', $dateFrom)
            ->whereDate('invoice_date', '<=', $dateTo);

        $invoices = $invoicesQuery->get();

        // Aggregate by product
        $productAggregates = [];
        $totalAdvanceTax = 0;
        foreach ($invoices as $invoice) {
            foreach ($invoice->items as $item) {
                $productId = $item->product_id;
                if (!isset($productAggregates[$productId])) {
                    $productAggregates[$productId] = [
                        'product_code' => $item->product->dms_code ?? '-',
                        'product_name' => $item->product->name ?? '-',
                        'unit_price' => $item->unit_price ?: ($item->price ?: ($item->product->unit_price ?? 0)),
                        'qty' => 0,
                        'issued' => 0,
                        'damages' => 0,
                        'pro' => 0,
                        'free' => 0,
                        'gross_sale' => 0,
                        'discount' => 0,
                        'percentage' => 0,
                        'net_sale' => 0,
                    ];
                }
                
                $pieces = $item->total_pieces ?? 0;
                $isFreeItem = $item->is_free ?? false;
                $isDamage = $invoice->invoice_type === 'damage';
                
                if ($isDamage) {
                    $productAggregates[$productId]['damages'] += $pieces;
                } else {
                    // Separate free items from regular qty
                    if ($isFreeItem) {
                        $productAggregates[$productId]['free'] += $pieces;
                    } else {
                        $productAggregates[$productId]['qty'] += $pieces;
                        $productAggregates[$productId]['issued'] += $pieces;
                        $productAggregates[$productId]['gross_sale'] += $item->line_total ?? 0;
                        $productAggregates[$productId]['discount'] += $item->scheme_discount ?? 0;
                        $productAggregates[$productId]['net_sale'] += ($item->line_total ?? 0) - ($item->scheme_discount ?? 0);
                    }
                    
                    // Accumulate advance tax (fed_amount + tax) - Only for sales? 
                    // Assuming tax is only for sales. If damage acts as a return, logic might differ but user asked for "damage piece also".
                    $totalAdvanceTax += ($item->fed_amount ?? 0) + ($item->tax ?? 0);
                }
            }
        }

        // Calculate percentage and unit_price for each product
        foreach ($productAggregates as &$product) {
            if ($product['gross_sale'] > 0) {
                $product['percentage'] = round(($product['discount'] / $product['gross_sale']) * 100, 2);
            }
            // Calculate unit price from gross_sale / qty if not set
            if ($product['unit_price'] == 0 && $product['qty'] > 0) {
                $product['unit_price'] = round($product['gross_sale'] / $product['qty'], 2);
            }
        }

        // Credit Summary Bills (invoices marked as credit)
        $creditInvoices = $invoices->where('is_credit', true)->map(fn($inv) => [
            'invoice_no' => $inv->invoice_number,
            'shop_name' => $inv->customer->shop_name ?? '-',
            'amount' => $inv->total_amount,
        ])->values();

        // Recovery Summary Bills
        $recoveries = Recovery::whereHas('invoice', function ($q) use ($vanId, $dateFrom, $dateTo) {
            $q->where('van_id', $vanId)
              ->whereDate('invoice_date', '>=', $dateFrom)
              ->whereDate('invoice_date', '<=', $dateTo);
        })->with('invoice.customer')->get()->map(fn($rec) => [
            'bill_no' => $rec->invoice->invoice_number ?? '-',
            'customer_name' => $rec->invoice->customer->shop_name ?? '-',
            'amount' => $rec->amount,
        ]);

        // Summary Totals
        $totalGross = collect($productAggregates)->sum('gross_sale');
        $totalDiscount = collect($productAggregates)->sum('discount');
        $totalNetSale = collect($productAggregates)->sum('net_sale');
        $totalCredit = $creditInvoices->sum('amount');
        $totalRecovery = $recoveries->sum('amount');
        // Total Cash = Net Sale - Credit + Recovery (recovery is money received)
        $totalCashSales = $totalNetSale - $totalCredit + $totalRecovery;

        // Get Van name for header
        $van = Van::find($vanId);

        return Inertia::render('SalesSheet/Index', [
            'products' => array_values($productAggregates),
            'creditBills' => $creditInvoices,
            'recoveryBills' => $recoveries,
            'summary' => [
                'total_gross' => $totalGross,
                'total_discount' => $totalDiscount,
                'total_percentage' => $totalGross > 0 ? round(($totalDiscount / $totalGross) * 100, 2) : 0,
                'total_advance_tax' => $totalAdvanceTax,
                'total_sale_value' => $totalNetSale,
                'total_credit' => $totalCredit,
                'total_recovery' => $totalRecovery,
                'total_cash_sales' => $totalCashSales,
            ],
            'filters' => [
                'van_id' => $vanId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'van' => $van ? ['id' => $van->id, 'name' => $van->code] : null,
            'vans' => Van::where('status', 'active')->get(['id', 'code as name']),
        ]);
    }
}
