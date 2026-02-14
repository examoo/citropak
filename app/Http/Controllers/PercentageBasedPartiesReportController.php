<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerBrandPercentage;
use App\Models\Brand;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PercentageBasedPartiesReportController extends Controller
{
    public function index(Request $request)
    {
        $customerIds = $request->input('customer_ids', []);

        // Get customers where percentage is allowed
        $query = Customer::query()
            ->where(function($q) {
                $q->where('percentage', '!=', 0)
                  ->orWhereNotNull('percentage');
            })
            ->where('status', 'active')
            ->with(['brandPercentages.brand'])
            ->select('id', 'customer_code', 'shop_name', 'percentage', 'phone', 'address', 'van', 'route');

        // Filter by selected customers if provided
        if (!empty($customerIds)) {
            $query->whereIn('id', $customerIds);
        }

        $customers = $query->orderBy('shop_name')->get();

        // Get all brands for reference
        $brands = Brand::select('id', 'name')->orderBy('name')->get();

        // Format report data
        $reportData = $customers->map(function ($customer) use ($brands) {
            $brandDiscounts = [];

            foreach ($brands as $brand) {
                $brandPercentage = $customer->brandPercentages->firstWhere('brand_id', $brand->id);
                $brandDiscounts[] = [
                    'brand_id' => $brand->id,
                    'brand_name' => $brand->name,
                    'percentage' => $brandPercentage ? $brandPercentage->percentage : 0,
                ];
            }

            return [
                'customer_id' => $customer->id,
                'customer_code' => $customer->customer_code,
                'shop_name' => $customer->shop_name,
                'percentage' => $customer->percentage,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'van' => $customer->van,
                'route' => $customer->route,
                'brand_discounts' => $brandDiscounts,
                'total_brands_with_discount' => collect($brandDiscounts)->where('percentage', '>', 0)->count(),
            ];
        });

        // Get all percentage-based customers for filter dropdown
        $allPercentageCustomers = Customer::query()
            ->where(function($q) {
                $q->where('percentage', '!=', 0)
                  ->orWhereNotNull('percentage');
            })
            ->where('status', 'active')
            ->select('id', 'customer_code', 'shop_name')
            ->orderBy('shop_name')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => "{$c->customer_code} - {$c->shop_name}",
            ]);

        return Inertia::render('PercentageBasedPartiesReport/Index', [
            'reportData' => $reportData,
            'brands' => $brands,
            'filters' => [
                'customer_ids' => $customerIds,
            ],
            'customers' => $allPercentageCustomers,
        ]);
    }
}
