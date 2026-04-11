<?php

namespace App\Http\Controllers;

use App\Models\OrderBooker;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrackingController extends Controller
{
    public function index()
    {
        $bookers = OrderBooker::whereNotNull('last_latitude')
            ->whereNotNull('last_longitude')
            ->with(['van', 'distribution'])
            ->get()
            ->map(function ($booker) {
                // Find last shop visit to show shop name
                $lastVisit = \App\Models\ShopVisit::where('order_booker_id', $booker->id)
                    ->with('customer')
                    ->latest('check_in_at')
                    ->first();

                return [
                    'id' => $booker->id,
                    'name' => $booker->name,
                    'code' => $booker->code,
                    'van_code' => $booker->van?->code,
                    'distribution_name' => $booker->distribution?->name,
                    'lat' => $booker->last_latitude,
                    'lng' => $booker->last_longitude,
                    'updated_at' => $booker->last_location_updated_at ? $booker->last_location_updated_at->diffForHumans() : 'Never',
                    'last_shop' => $lastVisit?->customer?->shop_name ?? 'Unknown',
                ];
            });

        return Inertia::render('Tracking/Index', [
            'bookers' => $bookers,
        ]);
    }
}
