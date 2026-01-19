<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
use App\Models\OrderBooker;
use App\Models\Van;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class OrderBookerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        
        $bookers = OrderBooker::query()
            ->with(['distribution', 'van', 'user']) // Load user for display
            ->when($search, function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            })
            // DistributionScope applies automatically via BaseTenantModel.
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('OrderBookers/Index', [
            'bookers' => $bookers,
            'distributions' => Distribution::where('status', 'active')->get(['id', 'name']),
            'vans' => Van::active()->with('distribution')->get(['id', 'code', 'distribution_id']),
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $userDistributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if($userDistributionId === 'all') $userDistributionId = null;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'van_id' => 'nullable|exists:vans,id',
            'distribution_id' => $userDistributionId ? 'nullable' : 'required|exists:distributions,id',
            // Login details
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $distId = $request->distribution_id ?? $userDistributionId;

        // Check code uniqueness manually
        $exists = OrderBooker::where('distribution_id', $distId)
                    ->where('code', $validated['code'])
                    ->exists();
        
        if ($exists) {
            return redirect()->back()->withErrors(['code' => 'The code has already been taken for this distribution.']);
        }

        DB::transaction(function() use ($validated, $distId) {
            // Create User Login
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'distribution_id' => $distId,
                'role' => 'order_booker', // Assuming simplified role column or similar logic
            ]);

            // Assign role via Spatie if used
            // $user->assignRole('order_booker'); 

            OrderBooker::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'van_id' => $validated['van_id'] ?? null,
                'distribution_id' => $distId,
                'user_id' => $user->id,
            ]);
        });

        return redirect()->back()->with('success', 'Order Booker and Login created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OrderBooker $orderBooker)
    {
        $userDistributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if($userDistributionId === 'all') $userDistributionId = null;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'van_id' => 'nullable|exists:vans,id',
            'distribution_id' => $userDistributionId ? 'nullable' : 'required|exists:distributions,id',
            // Login details (optional on update)
            'email' => 'nullable|email|unique:users,email,' . optional($orderBooker->user)->id,
            'password' => 'nullable|string|min:6',
        ]);

        $distId = $request->distribution_id ?? $userDistributionId ?? $orderBooker->distribution_id;

        $exists = OrderBooker::where('distribution_id', $distId)
                    ->where('code', $validated['code'])
                    ->where('id', '!=', $orderBooker->id)
                    ->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['code' => 'The code has already been taken for this distribution.']);
        }

        DB::transaction(function() use ($validated, $orderBooker, $distId) {
            $orderBooker->update([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'van_id' => $validated['van_id'] ?? null,
                'distribution_id' => $distId,
            ]);

            // Handle User Update or Creation
            if ($orderBooker->user) {
                $user = $orderBooker->user;
                $user->name = $validated['name'];
                if ($validated['email']) $user->email = $validated['email'];
                if ($validated['password']) $user->password = Hash::make($validated['password']);
                $user->save();
            } else {
                // If no user exists, create one if email provided
                if (!empty($validated['email']) && !empty($validated['password'])) {
                    $user = User::create([
                        'name' => $validated['name'],
                        'email' => $validated['email'],
                        'password' => Hash::make($validated['password']),
                        'distribution_id' => $distId,
                        'role' => 'order_booker',
                    ]);
                    
                    $orderBooker->user_id = $user->id;
                    $orderBooker->save();
                }
            }
        });

        return redirect()->back()->with('success', 'Order Booker updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderBooker $orderBooker)
    {
        DB::transaction(function() use ($orderBooker) {
            $user = $orderBooker->user;
            $orderBooker->delete();
            
            // Optionally delete the user too? 
            // Better to soft delete or keep it, but typically we want to remove access.
            if ($user && !$user->is_admin) { // Safety check
                $user->delete();
            }
        });

        return redirect()->back()->with('success', 'Order Booker and Login deleted successfully.');
    }
}
