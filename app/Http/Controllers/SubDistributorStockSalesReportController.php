<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\InvoiceItem;
use App\Models\SubDistribution;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class SubDistributorStockSalesReportController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));
        $subDistributionId = $request->input('sub_distribution_id');
        $brandIds = (array) $request->input('brand_ids', []);

        $query = Brand::query()
            ->leftJoin('products', 'brands.id', '=', 'products.brand_id')
            ->leftJoin('invoice_items', 'products.id', '=', 'invoice_items.product_id')
            ->leftJoin('invoices', function($join) use ($dateFrom, $dateTo) {
                $join->on('invoice_items.invoice_id', '=', 'invoices.id')
                    ->where('invoices.invoice_type', 'sale')
                    ->whereDate('invoices.invoice_date', '>=', $dateFrom)
                    ->whereDate('invoices.invoice_date', '<=', $dateTo);
            })
            ->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id')
            ->select(
                'brands.id as brand_id',
                'brands.name as brand_name',
                'products.id as product_id',
                'products.dms_code as product_code',
                'products.name as product_name',
                DB::raw('SUM(invoice_items.total_pieces) as total_quantity'),
                DB::raw('SUM(invoice_items.gross_amount) as total_gross_amount'),
                DB::raw('SUM(invoice_items.discount) as total_discount_amount'),
                DB::raw('SUM(invoice_items.line_total) as total_net_amount'),
                DB::raw('SUM(CASE WHEN invoice_items.is_free = 1 THEN invoice_items.total_pieces ELSE 0 END) as free_quantity')
            )
            ->groupBy('brands.id', 'brands.name', 'products.id', 'products.dms_code', 'products.name')
            ->orderBy('brands.name')
            ->orderBy('products.name');

        if ($subDistributionId) {
            $query->where(function($q) use ($subDistributionId) {
                $q->where('customers.sub_distribution_id', $subDistributionId)
                  ->orWhereNull('invoices.id');
            });
        }

        if (!empty($brandIds)) {
            $query->whereIn('brands.id', (array) $brandIds);
        }

        $reportData = $query->get()->filter(function($item) {
            return $item->product_id !== null; // Filter out brands with NO products at all if desired, or keep them?
            // Usually products report needs products.
        })->map(function ($item) {
            return [
                'brand_id' => $item->brand_id,
                'brand_name' => $item->brand_name,
                'product_id' => $item->product_id,
                'product_code' => $item->product_code,
                'product_name' => $item->product_name,
                'total_quantity' => (int) ($item->total_quantity ?? 0),
                'total_gross_amount' => (float) ($item->total_gross_amount ?? 0),
                'total_discount_amount' => (float) ($item->total_discount_amount ?? 0),
                'total_net_amount' => (float) ($item->total_net_amount ?? 0),
                'free_quantity' => (int) ($item->free_quantity ?? 0),
            ];
        });

        // Group by Brand for easier viewing
        $groupedData = $reportData->groupBy('brand_name');

        // Totals
        $totals = [
            'quantity' => $reportData->sum('total_quantity'),
            'gross_amount' => $reportData->sum('total_gross_amount'),
            'discount_amount' => $reportData->sum('total_discount_amount'),
            'net_amount' => $reportData->sum('total_net_amount'),
            'free_quantity' => $reportData->sum('free_quantity'),
        ];
        
        $currentDistributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        $subDistributionsQuery = SubDistribution::query();
        if ($currentDistributionId && $currentDistributionId !== 'all') {
            $subDistributionsQuery->forDistribution($currentDistributionId);
        }
        $subDistributions = $subDistributionsQuery->active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('SubDistributorStockSalesReport/Index', [
            'groupedData' => $groupedData,
            'totals' => $totals,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sub_distribution_id' => $subDistributionId,
                'brand_ids' => (array) $brandIds,
            ],
            'brands' => Brand::select('id', 'name')->orderBy('name')->get(),
            'subDistributions' => $subDistributions,
        ]);
    }
}
