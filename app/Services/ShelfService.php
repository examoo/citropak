<?php

namespace App\Services;

use App\Models\Shelf;
use App\Models\ShelfMonthlyRecord;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShelfService
{
    /**
     * Get all shelves with relationships.
     */
    public function getAll(array $filters = [])
    {
        return Shelf::query()
            ->with(['customer:id,shop_name,customer_code,address', 'orderBooker:id,name,code'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('shelf_code', 'like', "%{$search}%")
                      ->orWhereHas('customer', function ($cq) use ($search) {
                          $cq->where('shop_name', 'like', "%{$search}%")
                            ->orWhere('customer_code', 'like', "%{$search}%");
                      });
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Create a new shelf with auto-generated code.
     */
    public function create(array $data): Shelf
    {
        $distributionId = $data['distribution_id'] ?? null;

        // Auto-generate shelf code if not provided
        if (empty($data['shelf_code'])) {
            $data['shelf_code'] = Shelf::generateShelfCode($distributionId);
        }

        // Calculate end date if start_date and contract_months provided
        if (!empty($data['start_date']) && !empty($data['contract_months'])) {
            $startDate = Carbon::parse($data['start_date']);
            $data['end_date'] = $startDate->addMonths($data['contract_months'])->subDay()->toDateString();
        }

        return Shelf::create($data);
    }

    /**
     * Update a shelf.
     */
    public function update(Shelf $shelf, array $data): Shelf
    {
        // Recalculate end date if needed
        if (!empty($data['start_date']) && !empty($data['contract_months'])) {
            $startDate = Carbon::parse($data['start_date']);
            $data['end_date'] = $startDate->addMonths($data['contract_months'])->subDay()->toDateString();
        }

        $shelf->update($data);
        return $shelf->fresh();
    }

    /**
     * Get shelf report with monthly sales data.
     */
    public function getShelfReport(array $filters = [])
    {
        $year = $filters['year'] ?? now()->year;

        $query = Shelf::query()
            ->with([
                'customer:id,customer_code,shop_name,address',
                'orderBooker:id,name,code',
            ])
            ->whereNotNull('customer_id');

        // Apply filters
        if (!empty($filters['order_booker_id'])) {
            $query->where('order_booker_id', $filters['order_booker_id']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        $shelves = $query->get();

        // Get monthly sales for each customer from invoices
        $customerIds = $shelves->pluck('customer_id')->unique()->filter();
        
        $monthlySales = Invoice::whereIn('customer_id', $customerIds)
            ->whereYear('invoice_date', $year)
            ->where('invoice_type', 'sale')
            ->select(
                'customer_id',
                DB::raw('MONTH(invoice_date) as month'),
                DB::raw('SUM(total_amount) as total_sale')
            )
            ->groupBy('customer_id', DB::raw('MONTH(invoice_date)'))
            ->get()
            ->groupBy('customer_id');

        // Attach monthly sales to shelves
        return $shelves->map(function ($shelf) use ($monthlySales, $year) {
            $customerSales = $monthlySales[$shelf->customer_id] ?? collect();
            
            // Create month-wise data (1-12)
            $monthlyData = [];
            $totalSales = 0;
            
            for ($m = 1; $m <= 12; $m++) {
                $sale = $customerSales->firstWhere('month', $m);
                $amount = $sale ? (float)$sale->total_sale : 0;
                $monthlyData[$m] = $amount;
                $totalSales += $amount;
            }
            
            $shelf->monthly_sales = $monthlyData;
            $shelf->total_sales = $totalSales;
            $shelf->report_year = $year;
            
            return $shelf;
        });
    }

    /**
     * Get or create monthly record for a shelf.
     */
    public function getMonthlyRecord(Shelf $shelf, int $month, int $year): ShelfMonthlyRecord
    {
        return ShelfMonthlyRecord::firstOrCreate(
            [
                'shelf_id' => $shelf->id,
                'month' => $month,
                'year' => $year,
            ],
            [
                'distribution_id' => $shelf->distribution_id,
                'customer_id' => $shelf->customer_id,
                'rent_amount' => $shelf->rent_amount,
                'sales_amount' => 0,
                'rent_paid' => false,
                'incentive_earned' => 0,
            ]
        );
    }

    /**
     * Update monthly record sales from invoices.
     */
    public function syncMonthlySales(Shelf $shelf, int $month, int $year): ShelfMonthlyRecord
    {
        $record = $this->getMonthlyRecord($shelf, $month, $year);
        
        // Get sales for this customer in this month
        $sales = Invoice::where('customer_id', $shelf->customer_id)
            ->whereMonth('invoice_date', $month)
            ->whereYear('invoice_date', $year)
            ->where('invoice_type', 'sale')
            ->sum('total_amount');
        
        $record->update([
            'sales_amount' => $sales,
        ]);
        
        return $record->fresh();
    }

    /**
     * Mark rent as paid for a monthly record.
     */
    public function markRentPaid(ShelfMonthlyRecord $record, bool $paid = true): ShelfMonthlyRecord
    {
        $record->update(['rent_paid' => $paid]);
        return $record->fresh();
    }
}
