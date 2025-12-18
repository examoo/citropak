<?php

namespace App\Http\Controllers;

use App\Services\CustomerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(private CustomerService $service) {}

    /**
     * Display a listing of the resource.
     * Force sync.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'sort_field', 'sort_direction']);

        return Inertia::render('Customers/Index', [
            'customers' => $this->service->getAll($filters),
            'attributes' => $this->service->getAttributes(),
            'filters' => $filters,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(\App\Http\Requests\CustomerRequest $request)
    {
        $validated = $request->validated();
        
        $this->service->create($validated);

        return redirect()->back()->with('success', 'Customer created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(\App\Http\Requests\CustomerRequest $request, int $id)
    {
        $validated = $request->validated();
        
        $this->service->update($id, $validated);

        return redirect()->back()->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $this->service->delete($id);

        return redirect()->back()->with('success', 'Customer deleted successfully.');
    }
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\CustomersImport, $request->file('file'));

        return redirect()->back()->with('success', 'Customers imported successfully.');
    }
    public function downloadTemplate()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\CustomersTemplateExport, 'customers_template.xlsx');
    }
}
