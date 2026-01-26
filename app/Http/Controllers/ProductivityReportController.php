<?php

namespace App\Http\Controllers;

use App\Models\OrderBooker;
use App\Models\ShopVisit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class ProductivityReportController extends Controller
{
    public function index(Request $request): Response
    {
        $dateFrom = $request->input('date_from', now()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $orderBookerId = $request->input('order_booker_id');

        $query = ShopVisit::query()
            ->with(['orderBooker', 'customer'])
            ->whereDate('check_in_at', '>=', $dateFrom)
            ->whereDate('check_in_at', '<=', $dateTo)
            ->orderBy('check_in_at', 'desc');

        if ($orderBookerId) {
            $query->where('order_booker_id', $orderBookerId);
        }

        $visits = $query->get()->map(function ($visit) {
            $checkIn = $visit->check_in_at;
            $checkOut = $visit->check_out_at;
            $duration = null;
            $durationFormatted = '-';

            if ($checkIn && $checkOut) {
                $duration = $checkIn->diffInMinutes($checkOut);
                
                $hours = floor($duration / 60);
                $minutes = $duration % 60;
                
                if ($hours > 0) {
                    $durationFormatted = "{$hours}h {$minutes}m";
                } else {
                    $durationFormatted = "{$minutes}m";
                }
            }

            return [
                'id' => $visit->id,
                'order_booker_name' => $visit->orderBooker->name ?? 'Unknown',
                'shop_name' => $visit->customer->shop_name ?? 'Unknown',
                'shop_address' => $visit->customer->address ?? '',
                'check_in_time' => $checkIn ? $checkIn->format('d-m-Y h:i A') : '-',
                'check_out_time' => $checkOut ? $checkOut->format('d-m-Y h:i A') : '-',
                'duration' => $durationFormatted,
                'notes' => $visit->notes,
            ];
        });

        return Inertia::render('ProductivityReport/Index', [
            'visits' => $visits,
            'orderBookers' => OrderBooker::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'order_booker_id' => $orderBookerId,
            ],
        ]);
    }
}
