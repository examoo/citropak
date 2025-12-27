<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\OrderBooker;
use App\Models\Van;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerSheetController extends Controller
{
    /**
     * Display the customer sheet.
     */
    public function index(Request $request)
    {
        $selectedVanCode = $request->query('van_name'); // Still using van_name param for backward compat
        
        $vans = Van::where('status', 'active')->with('distribution')->latest()->get();
        
        $booker = null;
        $customers = [];

        if ($selectedVanCode) {
            // Find the booker assigned to this VAN by van_id (Van's id)
            $van = Van::where('code', $selectedVanCode)->first();
            if ($van) {
                $booker = OrderBooker::where('van_id', $van->id)->first();
            }
            
            // Find customers assigned to this VAN code, grouped by sub_address (Area)
            $customers = Customer::where('van', $selectedVanCode)
                ->where('status', 'active')
                ->orderBy('sub_address')
                ->orderBy('shop_name')
                ->get()
                ->groupBy('sub_address');
        }

        return Inertia::render('CustomerSheets/Index', [
            'vans' => $vans,
            'filters' => ['van_name' => $selectedVanCode],
            'booker' => $booker,
            'customers' => $customers,
            'allCustomers' => Customer::where('status', 'active')
                ->select('id', 'customer_code', 'shop_name', 'van')
                ->orderBy('customer_code')
                ->get(),
        ]);
    }

    /**
     * Assign customer to a new van.
     */
    public function assignToVan(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'van_code' => 'required|string',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);
        $customer->update(['van' => $validated['van_code']]);

        return redirect()->back()->with('success', 'Customer assigned to van successfully.');
    }
}
