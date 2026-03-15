<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Brand;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PercentageBasedPartiesReportController extends Controller
{
    public function index(Request $request)
    {
        $customerIds = $request->input('customer_ids', []);
        $dateFrom    = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo      = $request->input('date_to',   now()->endOfMonth()->format('Y-m-d'));
        $brandIds    = (array) $request->input('brand_ids', []);

        // Get customers where percentage is allowed
        $query = Customer::query()
            ->where(function ($q) {
                $q->where('percentage', '!=', 0)
                  ->orWhereNotNull('percentage');
            })
            ->where('status', 'active')
            ->with(['brandPercentages.brand'])
            ->select('id', 'customer_code', 'shop_name', 'percentage', 'phone', 'address', 'van', 'route');

        if (!empty($customerIds)) {
            $query->whereIn('id', $customerIds);
        }

        $customers = $query->orderBy('shop_name')->get();

        // Get brands for column display (filtered or all)
        $brandsQuery = Brand::select('id', 'name')->orderBy('name');
        if (!empty($brandIds)) {
            $brandsQuery->whereIn('id', $brandIds);
        }
        $brands = $brandsQuery->get();

        // --- Brand-wise sales per customer within date range ---
        // Build a lookup: [customer_id][brand_id] => total sale amount
        $salesRaw = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->where('invoices.invoice_type', 'sale')
            ->whereDate('invoices.invoice_date', '>=', $dateFrom)
            ->whereDate('invoices.invoice_date', '<=', $dateTo)
            ->whereIn('invoices.customer_id', $customers->pluck('id')->toArray())
            ->when(!empty($brandIds), fn($q) => $q->whereIn('products.brand_id', $brandIds))
            ->select(
                'invoices.customer_id',
                'products.brand_id',
                DB::raw('SUM(invoice_items.line_total) as total_sale')
            )
            ->groupBy('invoices.customer_id', 'products.brand_id')
            ->get();

        // Index: [customer_id][brand_id] = total_sale
        $salesIndex = [];
        foreach ($salesRaw as $row) {
            $salesIndex[$row->customer_id][$row->brand_id] = (float) $row->total_sale;
        }

        // Total sale per customer (across all shown brands)
        $customerTotalSale = [];
        foreach ($salesIndex as $custId => $brandSales) {
            $customerTotalSale[$custId] = array_sum($brandSales);
        }

        // Format report data
        $reportData = $customers->map(function ($customer) use ($brands, $salesIndex, $customerTotalSale) {
            $custId    = $customer->id;
            $custTotal = $customerTotalSale[$custId] ?? 0;

            $brandDiscounts = [];
            foreach ($brands as $brand) {
                $brandPercentage = $customer->brandPercentages->firstWhere('brand_id', $brand->id);
                $saleAmount      = $salesIndex[$custId][$brand->id] ?? 0;
                $salePercent     = $custTotal > 0 ? round(($saleAmount / $custTotal) * 100, 2) : 0;

                $brandDiscounts[] = [
                    'brand_id'      => $brand->id,
                    'brand_name'    => $brand->name,
                    'percentage'    => $brandPercentage ? $brandPercentage->percentage : 0,
                    'sale_amount'   => $saleAmount,
                    'sale_percent'  => $salePercent,
                ];
            }

            return [
                'customer_id'                => $custId,
                'customer_code'              => $customer->customer_code,
                'shop_name'                  => $customer->shop_name,
                'percentage'                 => $customer->percentage,
                'phone'                      => $customer->phone,
                'address'                    => $customer->address,
                'van'                        => $customer->van,
                'route'                      => $customer->route,
                'brand_discounts'            => $brandDiscounts,
                'total_sale'                 => $custTotal,
                'total_brands_with_discount' => collect($brandDiscounts)->where('percentage', '>', 0)->count(),
            ];
        });

        // Get all percentage-based customers for filter dropdown
        $allPercentageCustomers = Customer::query()
            ->where(function ($q) {
                $q->where('percentage', '!=', 0)
                  ->orWhereNotNull('percentage');
            })
            ->where('status', 'active')
            ->select('id', 'customer_code', 'shop_name')
            ->orderBy('shop_name')
            ->get()
            ->map(fn($c) => [
                'id'   => $c->id,
                'name' => "{$c->customer_code} - {$c->shop_name}",
            ]);

        return Inertia::render('PercentageBasedPartiesReport/Index', [
            'reportData' => $reportData,
            'brands'     => Brand::select('id', 'name')->orderBy('name')->get(), // all brands for brand filter dropdown
            'filters'    => [
                'customer_ids' => $customerIds,
                'date_from'    => $dateFrom,
                'date_to'      => $dateTo,
                'brand_ids'    => $brandIds,
            ],
            'customers'  => $allPercentageCustomers,
        ]);
    }
}
