<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ShopVisit;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        // For now, return all customers in the user's distribution.
        
        $customers = Customer::query()
            ->orderBy('shop_name')
            ->paginate(50);

        return response()->json($customers);
    }

    public function checkIn(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'check_in_at' => 'required|date',
        ]);

        $user = $request->user();
        $orderBooker = $user->orderBooker;

        if (!$orderBooker) {
            return response()->json(['message' => 'User is not an Order Booker.'], 403);
        }

        // Optional logic: Check if already checked in somewhere else?
        
        $visit = ShopVisit::create([
            'order_booker_id' => $orderBooker->id,
            'customer_id' => $request->customer_id,
            'check_in_at' => $request->check_in_at,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json([
            'message' => 'Checked in successfully.',
            'visit_id' => $visit->id,
            'visit' => $visit,
        ], 201);
    }

    public function checkOut(Request $request) 
    {
        $request->validate([
            'visit_id' => 'required|exists:shop_visits,id',
            'check_out_at' => 'required|date',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $visit = ShopVisit::findOrFail($request->visit_id);
        
        // Ensure the visit belongs to this user
        $user = $request->user();
        if ($visit->orderBooker->user_id !== $user->id && $visit->order_booker_id !== $user->orderBooker?->id) {
             return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $visit->update([
            'check_out_at' => $request->check_out_at,
            'check_out_latitude' => $request->latitude,
            'check_out_longitude' => $request->longitude,
            'notes' => $request->notes,
        ]);

        return response()->json([
             'message' => 'Checked out successfully.',
             'visit' => $visit,
        ]);
    }
}
