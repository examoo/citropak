<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\InvoiceItem;
use App\Models\SubDistribution;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class SubDistributorBrandSalesReportController extends Controller
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
                DB::raw('SUM(invoice_items.total_pieces) as total_quantity'),
                DB::raw('SUM(invoice_items.gross_amount) as total_gross_amount'),
                DB::raw('SUM(invoice_items.discount) as total_discount_amount'),
                DB::raw('SUM(invoice_items.line_total) as total_net_amount'),
                DB::raw('SUM(CASE WHEN invoice_items.is_free = 1 THEN invoice_items.total_pieces ELSE 0 END) as free_quantity')
            )
            ->groupBy('brands.id', 'brands.name');

        if ($subDistributionId) {
            $query->where(function($q) use ($subDistributionId) {
                $q->where('customers.sub_distribution_id', $subDistributionId)
                  ->orWhereNull('invoices.id'); // Keep brands even if no sales for this subdistributor?
            });
            // Actually, if a sub-distributor is selected, we usually ONLY want to see their sales.
            // But the user said "we need all brand in table if brand filter null also".
            // If sub-distributor is selected, showing brands with 0 sales for THAT sub-distributor is consistent with "show all brands".
        }

        if (!empty($brandIds)) {
            $query->whereIn('brands.id', (array) $brandIds);
        }

        $reportData = $query->get()->map(function ($item) {
            return [
                'brand_id' => $item->brand_id,
                'brand_name' => $item->brand_name,
                'total_quantity' => (int) ($item->total_quantity ?? 0),
                'total_gross_amount' => (float) ($item->total_gross_amount ?? 0),
                'total_discount_amount' => (float) ($item->total_discount_amount ?? 0),
                'total_net_amount' => (float) ($item->total_net_amount ?? 0),
                'free_quantity' => (int) ($item->free_quantity ?? 0),
            ];
        });

        // Totals
        $totals = [
            'quantity' => $reportData->sum('total_quantity'),
            'gross_amount' => $reportData->sum('total_gross_amount'),
            'discount_amount' => $reportData->sum('total_discount_amount'),
            'net_amount' => $reportData->sum('total_net_amount'),
            'free_quantity' => $reportData->sum('free_quantity'),
        ];
        
        // Helper for dropdowns
        $currentDistributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        $subDistributionsQuery = SubDistribution::query();
        if ($currentDistributionId && $currentDistributionId !== 'all') {
            $subDistributionsQuery->forDistribution($currentDistributionId);
        }
        $subDistributions = $subDistributionsQuery->active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('SubDistributorBrandSalesReport/Index', [
            'reportData' => $reportData,
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
