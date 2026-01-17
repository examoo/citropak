<?php

namespace App\Http\Controllers;

use App\Models\Chiller;
use App\Models\ChillerType;
use App\Models\Customer;
use App\Models\OrderBooker;
use App\Services\ChillerService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChillerController extends Controller
{
    public function __construct(private ChillerService $service)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $chillers = $this->service->getAll($request->only(['search', 'chiller_type_id', 'status']));

        $customers = Customer::select('id', 'shop_name as name', 'customer_code as code')
            ->where('status', 'active')
            ->orderBy('shop_name')
            ->get();

        $chillerTypes = ChillerType::active()->orderBy('name')->get();

        $orderBookers = OrderBooker::select('id', 'name', 'code')->orderBy('name')->get();

        return Inertia::render('Chillers/Index', [
            'chillers' => $chillers,
            'filters' => $request->only(['search', 'chiller_type_id', 'status']),
            'customers' => $customers,
            'chillerTypes' => $chillerTypes,
            'orderBookers' => $orderBookers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'chiller_code' => 'nullable|string|max:50',
            'chiller_type_id' => 'nullable|exists:chiller_types,id',
            'status' => 'required|in:active,inactive',
            'customer_id' => 'nullable|exists:customers,id',
            'order_booker_id' => 'nullable|exists:order_bookers,id',
        ]);

        // Auto-set distribution_id from session/user
        $distributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($distributionId && $distributionId !== 'all') {
            $validated['distribution_id'] = $distributionId;
        }

        $this->service->create($validated);

        return redirect()->route('chillers.index')->with('success', 'Chiller created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Chiller $chiller)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'chiller_code' => 'nullable|string|max:50',
            'chiller_type_id' => 'nullable|exists:chiller_types,id',
            'status' => 'required|in:active,inactive',
            'customer_id' => 'nullable|exists:customers,id',
            'order_booker_id' => 'nullable|exists:order_bookers,id',
        ]);

        $this->service->update($chiller, $validated);

        return redirect()->route('chillers.index')->with('success', 'Chiller updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Chiller $chiller)
    {
        $chiller->delete();

        return redirect()->back()->with('success', 'Chiller deleted successfully.');
    }

    /**
     * Move chiller to another customer.
     */
    public function move(Request $request, Chiller $chiller)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_booker_id' => 'nullable|exists:order_bookers,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $this->service->moveChiller($chiller, $validated);

        return redirect()->back()->with('success', 'Chiller moved successfully.');
    }

    /**
     * Return chiller from customer.
     */
    public function returnChiller(Request $request, Chiller $chiller)
    {
        $validated = $request->validate([
            'order_booker_id' => 'nullable|exists:order_bookers,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $this->service->returnChiller($chiller, $validated);

        return redirect()->back()->with('success', 'Chiller returned successfully.');
    }

    /**
     * Get chiller movement history.
     */
    public function history(Chiller $chiller)
    {
        $movements = $this->service->getMovementHistory($chiller);

        return response()->json([
            'chiller' => $chiller->load(['customer', 'chillerType']),
            'movements' => $movements,
        ]);
    }

    /**
     * Display chiller report.
     */
    public function report(Request $request)
    {
        $filters = $request->only(['chiller_type_id', 'order_booker_id', 'customer_id', 'date_from', 'date_to']);
        
        $chillers = $this->service->getChillerReport($filters);

        $chillerTypes = ChillerType::active()->orderBy('name')->get();
        $orderBookers = OrderBooker::select('id', 'name', 'code')->orderBy('name')->get();
        $customers = Customer::select('id', 'shop_name as name', 'customer_code as code')
            ->where('status', 'active')
            ->orderBy('shop_name')
            ->get();

        return Inertia::render('Chillers/Report', [
            'chillers' => $chillers,
            'filters' => $filters,
            'chillerTypes' => $chillerTypes,
            'orderBookers' => $orderBookers,
            'customers' => $customers,
        ]);
    }
    public function exportReport(Request $request) 
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ChillerReportExport($request->all()), 'chiller-report.xlsx');
    }
}
