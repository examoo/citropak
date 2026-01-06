<?php

namespace App\Services;

use App\Models\Chiller;
use App\Models\ChillerMovement;
use App\Models\ChillerType;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class ChillerService
{
    /**
     * Get all chillers with relationships.
     */
    public function getAll(array $filters = [])
    {
        return Chiller::query()
            ->with(['customer:id,shop_name,customer_code,address', 'chillerType:id,name', 'orderBooker:id,name,code'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('chiller_code', 'like', "%{$search}%")
                      ->orWhereHas('customer', function ($cq) use ($search) {
                          $cq->where('shop_name', 'like', "%{$search}%")
                            ->orWhere('customer_code', 'like', "%{$search}%");
                      });
                });
            })
            ->when($filters['chiller_type_id'] ?? null, function ($query, $typeId) {
                $query->where('chiller_type_id', $typeId);
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Create a new chiller with auto-generated code.
     */
    public function create(array $data): Chiller
    {
        $distributionId = $data['distribution_id'] ?? null;

        // Auto-generate chiller code if not provided
        if (empty($data['chiller_code'])) {
            $data['chiller_code'] = Chiller::generateChillerCode($distributionId);
        }

        $chiller = Chiller::create($data);

        // If customer is assigned, record the initial assignment
        if (!empty($data['customer_id'])) {
            $this->recordMovement($chiller, [
                'to_customer_id' => $data['customer_id'],
                'movement_type' => 'assign',
                'order_booker_id' => $data['order_booker_id'] ?? null,
                'notes' => 'Initial assignment',
            ]);
        }

        return $chiller;
    }

    /**
     * Update a chiller.
     */
    public function update(Chiller $chiller, array $data): Chiller
    {
        $chiller->update($data);
        return $chiller->fresh();
    }

    /**
     * Move chiller to another customer.
     */
    public function moveChiller(Chiller $chiller, array $data): Chiller
    {
        $fromCustomerId = $chiller->customer_id;
        $toCustomerId = $data['customer_id'];

        DB::transaction(function () use ($chiller, $fromCustomerId, $toCustomerId, $data) {
            // Update chiller assignment
            $chiller->update([
                'customer_id' => $toCustomerId,
                'order_booker_id' => $data['order_booker_id'] ?? $chiller->order_booker_id,
            ]);

            // Record the movement
            $this->recordMovement($chiller, [
                'from_customer_id' => $fromCustomerId,
                'to_customer_id' => $toCustomerId,
                'movement_type' => 'move',
                'order_booker_id' => $data['order_booker_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return $chiller->fresh(['customer', 'chillerType', 'orderBooker']);
    }

    /**
     * Return chiller (remove from customer).
     */
    public function returnChiller(Chiller $chiller, array $data): Chiller
    {
        $fromCustomerId = $chiller->customer_id;

        DB::transaction(function () use ($chiller, $fromCustomerId, $data) {
            // Remove customer assignment
            $chiller->update([
                'customer_id' => null,
                'order_booker_id' => $data['order_booker_id'] ?? $chiller->order_booker_id,
            ]);

            // Record the return
            $this->recordMovement($chiller, [
                'from_customer_id' => $fromCustomerId,
                'to_customer_id' => null,
                'movement_type' => 'return',
                'order_booker_id' => $data['order_booker_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return $chiller->fresh(['customer', 'chillerType', 'orderBooker']);
    }

    /**
     * Record a chiller movement.
     */
    protected function recordMovement(Chiller $chiller, array $data): ChillerMovement
    {
        return ChillerMovement::create([
            'distribution_id' => $chiller->distribution_id,
            'chiller_id' => $chiller->id,
            'from_customer_id' => $data['from_customer_id'] ?? null,
            'to_customer_id' => $data['to_customer_id'] ?? null,
            'movement_type' => $data['movement_type'],
            'movement_date' => $data['movement_date'] ?? now()->toDateString(),
            'order_booker_id' => $data['order_booker_id'],
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Get chiller report data.
     */
    public function getChillerReport(array $filters = [])
    {
        $query = Chiller::query()
            ->with([
                'customer:id,customer_code,shop_name,address',
                'chillerType:id,name',
                'orderBooker:id,name,code',
            ])
            ->whereNotNull('customer_id');

        // Apply filters
        if (!empty($filters['chiller_type_id'])) {
            $query->where('chiller_type_id', $filters['chiller_type_id']);
        }

        if (!empty($filters['order_booker_id'])) {
            $query->where('order_booker_id', $filters['order_booker_id']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        $chillers = $query->get();

        // Get sales data for customers with chillers
        $customerIds = $chillers->pluck('customer_id')->unique()->filter();
        
        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->toDateString();

        $sales = Invoice::whereIn('customer_id', $customerIds)
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('invoice_type', 'sale')
            ->select('customer_id', DB::raw('SUM(total_amount) as total_sale'))
            ->groupBy('customer_id')
            ->pluck('total_sale', 'customer_id');

        // Attach sales to chillers
        return $chillers->map(function ($chiller) use ($sales) {
            $chiller->total_sale = $sales[$chiller->customer_id] ?? 0;
            return $chiller;
        });
    }

    /**
     * Get chiller movement history.
     */
    public function getMovementHistory(Chiller $chiller)
    {
        return $chiller->movements()
            ->with(['fromCustomer:id,shop_name,customer_code', 'toCustomer:id,shop_name,customer_code', 'orderBooker:id,name'])
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all chiller types.
     */
    public function getChillerTypes()
    {
        return ChillerType::active()->orderBy('name')->get();
    }
}
