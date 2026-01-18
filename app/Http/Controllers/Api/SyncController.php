<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\DiscountScheme;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function products(Request $request)
    {
        // Return active products with their related data
        // Note: Stocks might be distribution specific. 
        // If Product model is global, we might need to filter stocks by distribution.
        
        $user = $request->user();
        $distributionId = $user->distribution_id;

        $products = Product::active()
            ->with(['stocks' => function($query) use ($distributionId) {
                // Assuming Stock model has distribution_id or logic to filter
                // If Stock is global per product/distribution, we need to check Stock model structure.
                // For now, loading all stocks.
            }])
            ->get();

        return response()->json($products);
    }

    public function schemas(Request $request)
    {
        $user = $request->user();
        
        $schemas = DiscountScheme::active()
            ->where('distribution_id', $user->distribution_id) // Filter by distribution for now
            ->get();

        return response()->json($schemas);
    }
}
