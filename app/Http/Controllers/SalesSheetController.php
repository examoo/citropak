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
        $summary = [
            'gross_sale' => 0,
            'scheme_discount' => 0,
            'customer_discount' => 0,
            'trade_discount' => 0,
            'advance_tax' => 0,
            'net_sale' => 0,
        ];

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
                        'scheme_discount' => 0,
                        'customer_discount' => 0,
                        'trade_discount' => 0,
                        'customer_percentage' => 0,
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
                        $grossAmt = $item->gross_amount ?? 0;
                        $tradeAmt = $item->retail_margin ?? 0; // = retail_margin column = trade discount amount
                        $schDisc = $item->scheme_discount_amount ?? ($item->scheme_discount ?? 0);
                        
                        // Mirror Show.vue: Customer Discount = Total Discount - Scheme Discount
                        $totalDisc = $item->discount ?? 0;
                        $custDisc = max(0, $totalDisc - $schDisc);

                        $productAggregates[$productId]['qty'] += $pieces;
                        $productAggregates[$productId]['issued'] += $pieces;
                        $productAggregates[$productId]['gross_sale'] += $grossAmt;
                        $productAggregates[$productId]['scheme_discount'] += $schDisc;
                        $productAggregates[$productId]['customer_discount'] += $custDisc;
                        $productAggregates[$productId]['trade_discount'] += $tradeAmt;
                        $productAggregates[$productId]['net_sale'] += ($grossAmt - $schDisc - $custDisc - $tradeAmt);

                        // Update global summary accumulators
                        $summary['gross_sale'] += $grossAmt;
                        $summary['scheme_discount'] += $schDisc;
                        $summary['customer_discount'] += $custDisc;
                        $summary['trade_discount'] += $tradeAmt;
                        $summary['advance_tax'] += ($item->adv_tax_amount ?? 0);
                        $summary['net_sale'] += ($grossAmt - $schDisc - $custDisc - $tradeAmt);
                    }
                }
            }
        }

        // Calculate percentages and finalize products array
        $products = [];
        foreach ($productAggregates as $productId => $data) {
            $product = $data;
            if ($product['gross_sale'] > 0) {
                $calcBase = $product['gross_sale'] - $product['trade_discount'];
                $product['customer_percentage'] = $calcBase > 0 
                    ? round(($product['customer_discount'] / $calcBase) * 100, 2) 
                    : 0;
            } else {
                $product['customer_percentage'] = 0;
            }
            
            // Calculate unit price from gross_sale / qty if not set
            if ($product['unit_price'] == 0 && $product['qty'] > 0) {
                $product['unit_price'] = round($product['gross_sale'] / $product['qty'], 2);
            }
            $products[] = $product;
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

        $totalCredit = $creditInvoices->sum('amount');
        $totalRecovery = $recoveries->sum('amount');
        $totalCashSales = $summary['net_sale'] - $totalCredit + $totalRecovery;

        // Get Van name for header
        $van = Van::find($vanId);

        return Inertia::render('SalesSheet/Index', [
            'products' => $products,
            'creditBills' => $creditInvoices,
            'recoveryBills' => $recoveries,
            'summary' => [
                'total_gross' => $summary['gross_sale'],
                'total_scheme_discount' => $summary['scheme_discount'],
                'total_customer_discount' => $summary['customer_discount'],
                'total_trade_discount' => $summary['trade_discount'],
                'total_customer_percentage' => ($summary['gross_sale'] - $summary['trade_discount']) > 0
                    ? round(($summary['customer_discount'] / ($summary['gross_sale'] - $summary['trade_discount'])) * 100, 2)
                    : 0,
                'total_advance_tax' => $summary['advance_tax'],
                'total_sale_value' => $summary['net_sale'],
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
