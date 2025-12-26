<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Van;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesAccountController extends Controller
{
    /**
     * Display the sales account report.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['van_id', 'date_from', 'date_to']);
        
        $vanId = $request->input('van_id');
        $dateFrom = $request->input('date_from', now()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $query = Invoice::query()
            ->with(['customer', 'van']) // items loaded only if needed, or we sum strictly
            ->withSum('items', 'scheme_discount') // For discount total checking
            ->withSum('items', 'tax')
            ->withSum('items', 'fed_amount')
            ->withSum('items', 'total_pieces') // Assuming total_pieces exists on items
            ->withSum('recoveries', 'amount'); // Sum of existing recoveries

        // Filter by Van
        if ($vanId) {
            $query->where('van_id', $vanId);
        }

        // ... existing filters ...

        $invoices = $query->latest('invoice_date')
            ->get()
            ->map(function ($invoice) {
                // ... gross sale calc ...
                $grossSale = $invoice->subtotal + $invoice->discount_amount; 
                $paidAmount = $invoice->recoveries_sum_amount ?? 0;
                $netSale = $invoice->total_amount;
                $balance = $netSale - $paidAmount;

                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_code' => $invoice->customer->customer_code ?? '-',
                    'customer_name' => $invoice->customer->shop_name ?? '-',
                    'customer_address' => $invoice->customer->address ?? '-',
                    'gross_sale' => $grossSale,
                    'scheme_discount' => $invoice->discount_amount,
                    'percentage_discount' => 0,
                    'net_monetary_sale' => $netSale,
                    'paid_amount' => $paidAmount,
                    'balance' => $balance,
                    'net_pieces' => $invoice->items_sum_total_pieces ?? 0,
                    'status' => [
                        'is_sale' => $invoice->invoice_type === 'sale',
                        'is_credit' => $invoice->is_credit,
                        'is_recovery' => false
                    ]
                ];
            });

        return Inertia::render('SalesAccount/Index', [
            'invoices' => $invoices,
            'filters' => [
                'van_id' => $vanId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'vans' => Van::where('status', 'active')->get(['id', 'code as name']), // Use code as name for dropdown
        ]);
    }
}
