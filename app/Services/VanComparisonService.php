<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Van;
use Illuminate\Support\Collection;

class VanComparisonService
{
    /**
     * Get comparison data between two vans for a specific month/year.
     *
     * @param int $van1Id
     * @param int $van2Id
     * @param int $month
     * @param int $year
     * @return array
     */
    public function getComparisonData(int $van1Id, int $van2Id, int $month, int $year): array
    {
        $van1 = Van::find($van1Id);
        $van2 = Van::find($van2Id);

        if (!$van1 || !$van2) {
            return [
                'van1' => null,
                'van2' => null,
                'customers' => [],
                'totals' => ['van1' => 0, 'van2' => 0],
            ];
        }

        // Get all customers who had invoices from either van in the specified month
        $startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        // Get invoices for Van 1
        $van1Invoices = Invoice::where('van_id', $van1Id)
            ->whereDate('invoice_date', '>=', $startDate)
            ->whereDate('invoice_date', '<=', $endDate)
            ->with('customer')
            ->get()
            ->groupBy('customer_id');

        // Get invoices for Van 2
        $van2Invoices = Invoice::where('van_id', $van2Id)
            ->whereDate('invoice_date', '>=', $startDate)
            ->whereDate('invoice_date', '<=', $endDate)
            ->with('customer')
            ->get()
            ->groupBy('customer_id');

        // Merge customer IDs from both vans
        $allCustomerIds = $van1Invoices->keys()->merge($van2Invoices->keys())->unique();

        // Build comparison data
        $customers = [];
        $totalVan1 = 0;
        $totalVan2 = 0;

        foreach ($allCustomerIds as $customerId) {
            $customer = Customer::find($customerId);
            if (!$customer) continue;

            // Calculate net sales for Van 1
            $van1NetSales = 0;
            if ($van1Invoices->has($customerId)) {
                $van1NetSales = $van1Invoices->get($customerId)->sum(function ($invoice) {
                    return $invoice->total_amount - $invoice->discount_amount;
                });
            }

            // Calculate net sales for Van 2
            $van2NetSales = 0;
            if ($van2Invoices->has($customerId)) {
                $van2NetSales = $van2Invoices->get($customerId)->sum(function ($invoice) {
                    return $invoice->total_amount - $invoice->discount_amount;
                });
            }

            $customers[] = [
                'customer_code' => $customer->customer_code ?? '-',
                'shop_name' => $customer->shop_name ?? '-',
                'address' => $customer->address ?? '-',
                'van1_net_sales' => $van1NetSales,
                'van2_net_sales' => $van2NetSales,
            ];

            $totalVan1 += $van1NetSales;
            $totalVan2 += $van2NetSales;
        }

        // Sort by customer code
        usort($customers, function ($a, $b) {
            return strcmp($a['customer_code'], $b['customer_code']);
        });

        return [
            'van1' => [
                'id' => $van1->id,
                'code' => $van1->code,
                'name' => $van1->code,
            ],
            'van2' => [
                'id' => $van2->id,
                'code' => $van2->code,
                'name' => $van2->code,
            ],
            'customers' => $customers,
            'totals' => [
                'van1' => $totalVan1,
                'van2' => $totalVan2,
            ],
        ];
    }
}
