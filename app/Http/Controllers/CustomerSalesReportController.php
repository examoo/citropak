<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerSalesReportController extends Controller
{
    public function index(Request $request): Response
    {
        $customerCode = $request->input('customer_code');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $customer = null;
        $invoices = collect();
        $productsByDate = collect();
        $brandWiseSales = collect();

        if ($customerCode) {
            $customer = Customer::where('customer_code', $customerCode)->first();
            
            if ($customer) {
                $query = Invoice::query()
                    ->with(['van', 'items.product.brand'])
                    ->where('customer_id', $customer->id);

                if ($dateFrom) {
                    $query->whereDate('invoice_date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $query->whereDate('invoice_date', '<=', $dateTo);
                }

                $rawInvoices = $query->latest('invoice_date')->get();

                // All Invoices Tab
                $invoices = $rawInvoices->map(fn($inv) => [
                    'invoice_number' => $inv->invoice_number,
                    'date' => $inv->invoice_date,
                    'van' => $inv->van->code ?? '-',
                    'gross_sale' => $inv->subtotal + $inv->discount_amount,
                    'discount' => $inv->discount_amount,
                    'after_discount' => $inv->subtotal,
                    'percentage' => $customer->percentage ?? 0,
                ]);

                // Products by Date Tab
                $productData = [];
                foreach ($rawInvoices as $inv) {
                    foreach ($inv->items as $item) {
                        $key = $inv->invoice_date . '_' . $item->product_id;
                        if (!isset($productData[$key])) {
                            $productData[$key] = [
                                'date' => $inv->invoice_date,
                                'product_code' => $item->product->dms_code ?? '-',
                                'product_name' => $item->product->name ?? '-',
                                'qty' => 0,
                                'amount' => 0,
                            ];
                        }
                        $productData[$key]['qty'] += $item->total_pieces ?? 0;
                        $productData[$key]['amount'] += $item->line_total ?? 0;
                    }
                }
                $productsByDate = array_values($productData);

                // Brand-wise Sale Tab
                $brandData = [];
                foreach ($rawInvoices as $inv) {
                    foreach ($inv->items as $item) {
                        $brandName = $item->product->brand->name ?? 'Unknown';
                        if (!isset($brandData[$brandName])) {
                            $brandData[$brandName] = [
                                'brand' => $brandName,
                                'qty' => 0,
                                'amount' => 0,
                            ];
                        }
                        $brandData[$brandName]['qty'] += $item->total_pieces ?? 0;
                        $brandData[$brandName]['amount'] += $item->line_total ?? 0;
                    }
                }
                $brandWiseSales = array_values($brandData);
            }
        }

        return Inertia::render('CustomerSalesReport/Index', [
            'customer' => $customer ? [
                'id' => $customer->id,
                'code' => $customer->customer_code,
                'name' => $customer->shop_name,
            ] : null,
            'invoices' => $invoices,
            'productsByDate' => $productsByDate,
            'brandWiseSales' => $brandWiseSales,
            'filters' => [
                'customer_code' => $customerCode,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'customers' => Customer::select('id', 'customer_code', 'shop_name')
                ->orderBy('customer_code')
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'code' => $c->customer_code,
                    'name' => $c->customer_code . ' - ' . $c->shop_name,
                ]),
        ]);
    }
}
