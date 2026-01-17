<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
use App\Models\ClosingStock;
use App\Models\Stock;
use App\Services\ClosingStockService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClosingStockController extends Controller
{
    public function __construct(private ClosingStockService $service) {}

    /**
     * Display a listing of closing stocks (monthly).
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'date']);
        $selectedDate = $request->get('date', now()->format('Y-m-d'));
        
        $distributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($distributionId === 'all') $distributionId = null;

        // Get stocks that don't have closing stock entries yet for the SELECTED DATE
        $stockQuery = Stock::with(['product', 'distribution'])
            ->whereNotIn('id', function ($query) use ($distributionId, $selectedDate) {
                $query->select('stocks.id')
                    ->from('stocks')
                    ->join('closing_stocks', function ($join) use ($selectedDate) {
                        $join->on('stocks.product_id', '=', 'closing_stocks.product_id')
                            ->on('stocks.distribution_id', '=', 'closing_stocks.distribution_id')
                            ->whereDate('closing_stocks.date', '=', $selectedDate);
                    });
            });
        
        if ($distributionId) {
            $stockQuery->where('distribution_id', $distributionId);
        }
        
        $availableStocks = $stockQuery->get()->map(function ($stock) {
            return [
                'id' => $stock->id,
                // ... (rest of mapping same)
                'name' => $stock->product->name . ' (' . $stock->quantity . ' pcs)',
                'product_id' => $stock->product_id,
                'distribution_id' => $stock->distribution_id,
                'quantity' => $stock->quantity,
                'product' => $stock->product,
                'batch_number' => $stock->batch_number,
                'expiry_date' => $stock->expiry_date,
                'location' => $stock->location,
                'unit_cost' => $stock->unit_cost,
            ];
        });
        
        // Ensure filters has default date if not provided, for the view to use
        if (!isset($filters['date'])) {
            $filters['date'] = $selectedDate;
        }

        return Inertia::render('ClosingStocks/Index', [
            'closingStocks' => $this->service->getAll($filters, $distributionId),
            'filters' => $filters,
            'availableStocks' => $availableStocks,
            'distributions' => Distribution::where('status', 'active')->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Store a newly created closing stock.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'distribution_id' => 'required|exists:distributions,id',
            'date' => 'required|date',
            'cartons' => 'required|integer|min:0',
            'pieces' => 'required|integer|min:0',
            'pieces_per_carton' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:0',
            'batch_number' => 'nullable|string',
            'expiry_date' => 'nullable|date',
            'location' => 'nullable|string',
            'unit_cost' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $validated['status'] = 'draft';
        $validated['created_by'] = $request->user()->id;

        try {
            $this->service->create($validated);
            return redirect()->back()->with('success', 'Closing stock created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update the specified closing stock.
     */
    public function update(Request $request, ClosingStock $closingStock)
    {
        if ($closingStock->status === 'posted') {
            return redirect()->back()->with('error', 'Cannot edit a posted closing stock.');
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'distribution_id' => 'required|exists:distributions,id',
            'date' => 'required|date',
            'cartons' => 'required|integer|min:0',
            'pieces' => 'required|integer|min:0',
            'pieces_per_carton' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:0',
            'batch_number' => 'nullable|string',
            'expiry_date' => 'nullable|date',
            'location' => 'nullable|string',
            'unit_cost' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $this->service->update($closingStock, $validated);

        return redirect()->back()->with('success', 'Closing stock updated successfully.');
    }

    /**
     * Post closing stock and reduce from stocks.
     */
    public function post(ClosingStock $closingStock)
    {
        if ($closingStock->status === 'posted') {
            return redirect()->back()->with('error', 'Closing stock is already posted.');
        }

        $this->service->post($closingStock);

        return redirect()->back()->with('success', 'Closing stock posted and deducted from stocks.');
    }

    /**
     * Remove the specified closing stock.
     */
    public function destroy(ClosingStock $closingStock)
    {
        try {
            $this->service->delete($closingStock);
            return redirect()->back()->with('success', 'Closing stock deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Convert available stocks to closing stocks.
     */
    public function convertFromStocks(Request $request)
    {
        $distributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($distributionId === 'all') $distributionId = null;

        $count = $this->service->convertFromStocks($distributionId, $request->user()->id);

        if ($count === 0) {
            return redirect()->back()->with('warning', 'No available stocks found to convert.');
        }

        return redirect()->back()->with('success', "Successfully created {$count} draft closing stock(s) from available stocks.");
    }
}
