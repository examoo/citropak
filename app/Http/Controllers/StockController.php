<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockRequest;
use App\Models\Distribution;
use App\Models\Product;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    public function __construct(private StockService $service) {}

    /**
     * Display a listing of stocks.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'low_stock']);
        $distributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($distributionId === 'all') $distributionId = null;

        return Inertia::render('Stocks/Index', [
            'stocks' => $this->service->getAll($filters, $distributionId),
            'filters' => $filters,
            'products' => Product::all(),
            'distributions' => Distribution::where('status', 'active')->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Store a newly created stock.
     */
    public function store(StockRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->back()->with('success', 'Stock entry created successfully.');
    }

    /**
     * Update the specified stock.
     */
    public function update(StockRequest $request, Stock $stock)
    {
        $stock->update($request->validated());

        return redirect()->back()->with('success', 'Stock updated successfully.');
    }

    /**
     * Remove the specified stock.
     */
    public function destroy(Stock $stock)
    {
        $this->service->delete($stock);

        return redirect()->back()->with('success', 'Stock deleted successfully.');
    }

    /**
     * Adjust stock quantity (add/subtract).
     */
    public function adjust(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'type' => 'required|in:add,subtract',
        ]);

        $this->service->updateQuantity($stock, $validated['quantity'], $validated['type']);

        return redirect()->back()->with('success', 'Stock adjusted successfully.');
    }
}
