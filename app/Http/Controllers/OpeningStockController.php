<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
use App\Models\OpeningStock;
use App\Models\Product;
use App\Models\Stock;
use App\Services\OpeningStockService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OpeningStockController extends Controller
{
    public function __construct(private OpeningStockService $service) {}

    /**
     * Display a listing of opening stocks with daily summary.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'date']);
        $selectedDate = $request->get('date', now()->format('Y-m-d'));
        $distributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($distributionId === 'all') $distributionId = null;

        // Get stock summary by product
        $stockSummary = $this->getStockSummary($selectedDate, $distributionId, $filters['search'] ?? null);

        // Get stocks that don't have opening stock entries yet (for the current distribution)
        $stockQuery = Stock::with(['product', 'distribution'])
            ->whereNotIn('id', function ($query) use ($distributionId) {
                $query->select('stocks.id')
                    ->from('stocks')
                    ->join('opening_stocks', function ($join) {
                        $join->on('stocks.product_id', '=', 'opening_stocks.product_id')
                            ->on('stocks.distribution_id', '=', 'opening_stocks.distribution_id');
                    });
            });
        
        if ($distributionId) {
            $stockQuery->where('distribution_id', $distributionId);
        }
        
        $availableStocks = $stockQuery->get()->map(function ($stock) {
            return [
                'id' => $stock->id,
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

        return Inertia::render('OpeningStocks/Index', [
            'openingStocks' => $this->service->getAll($filters, $distributionId),
            'stockSummary' => $stockSummary,
            'selectedDate' => $selectedDate,
            'filters' => $filters,
            'availableStocks' => $availableStocks,
            'distributions' => Distribution::where('status', 'active')->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Get stock summary with opening, available, closing for each product.
     */
    private function getStockSummary(string $date, $distributionId = null, $search = null): array
    {
        $query = Product::with(['stocks' => function ($q) use ($distributionId) {
            if ($distributionId) {
                $q->where('distribution_id', $distributionId);
            }
        }]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('dms_code', 'like', "%{$search}%");
            });
        }

        $products = $query->get();

        $summary = [];
        foreach ($products as $product) {
            // Get opening stock for this date (from opening_stocks table)
            $openingQuery = OpeningStock::where('product_id', $product->id)
                ->where('date', '<=', $date)
                ->where('status', 'posted');
            if ($distributionId) {
                $openingQuery->where('distribution_id', $distributionId);
            }
            $openingStock = $openingQuery->sum('quantity');

            // Get available stock (current stock from stocks table)
            $availableStock = $product->stocks->sum('quantity');

            // Get closing stock for this date (we'll use opening + stock-ins on that date as closing)
            // For now, closing = available stock (can be expanded later with stock-out transactions)
            $closingStock = $availableStock;

            // Only include products with some stock activity
            if ($openingStock > 0 || $availableStock > 0) {
                $summary[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'dms_code' => $product->dms_code,
                    'packing' => $product->packing,
                    'opening_stock' => $openingStock,
                    'available_stock' => $availableStock,
                    'closing_stock' => $closingStock,
                    'is_closed' => false, // Can be set based on closing date logic
                ];
            }
        }

        return $summary;
    }

    /**
     * Store a newly created opening stock.
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
            return redirect()->back()->with('success', 'Opening stock created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update the specified opening stock.
     */
    public function update(Request $request, OpeningStock $openingStock)
    {
        if ($openingStock->status === 'posted') {
            return redirect()->back()->with('error', 'Cannot edit a posted opening stock.');
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

        $this->service->update($openingStock, $validated);

        return redirect()->back()->with('success', 'Opening stock updated successfully.');
    }

    /**
     * Post opening stock and add to stocks.
     */
    public function post(OpeningStock $openingStock)
    {
        if ($openingStock->status === 'posted') {
            return redirect()->back()->with('error', 'Opening stock is already posted.');
        }

        $this->service->post($openingStock);

        return redirect()->back()->with('success', 'Opening stock posted and added to stocks.');
    }

    /**
     * Remove the specified opening stock.
     */
    public function destroy(OpeningStock $openingStock)
    {
        try {
            $this->service->delete($openingStock);
            return redirect()->back()->with('success', 'Opening stock deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Convert available stocks to opening stocks.
     */
    public function convertFromStocks(Request $request)
    {
        $distributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($distributionId === 'all') $distributionId = null;

        $count = $this->service->convertFromStocks($distributionId, $request->user()->id);

        if ($count === 0) {
            return redirect()->back()->with('warning', 'No available stocks found to convert.');
        }

        return redirect()->back()->with('success', "Successfully created {$count} draft opening stock(s) from available stocks.");
    }
}
