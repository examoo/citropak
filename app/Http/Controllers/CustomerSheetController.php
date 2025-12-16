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
        $selectedVanName = $request->query('van_name');
        
        $vans = Van::where('status', 'active')->latest()->get();
        
        $booker = null;
        $customers = [];

        if ($selectedVanName) {
            // Find the booker assigned to this VAN
            $booker = OrderBooker::where('van', $selectedVanName)->where('status', 'active')->first();
            
            // Find customers assigned to this VAN, grouped by sub_address (Area)
            // We fetch them sorted by sub_address so the grouping in frontend/backend is cleaner
            $customers = Customer::where('van', $selectedVanName)
                ->where('status', 'active')
                ->orderBy('sub_address')
                ->orderBy('shop_name')
                ->get()
                ->groupBy('sub_address');
        }

        return Inertia::render('CustomerSheets/Index', [
            'vans' => $vans,
            'filters' => ['van_name' => $selectedVanName],
            'booker' => $booker,
            'customers' => $customers
        ]);
    }
}
