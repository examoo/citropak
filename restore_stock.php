<?php

use App\Models\OpeningStock;
use App\Models\Stock;

echo "Restoring Stocks from Posted Opening Stocks...\n";

$openingStocks = OpeningStock::where('status', 'posted')->get();

foreach ($openingStocks as $os) {
    // Find or create stock
    $stock = Stock::where('product_id', $os->product_id)
        ->where('distribution_id', $os->distribution_id)
        ->where('batch_number', $os->batch_number)
        ->first();
        
    if (!$stock) {
        $stock = new Stock();
        $stock->product_id = $os->product_id;
        $stock->distribution_id = $os->distribution_id;
        $stock->batch_number = $os->batch_number;
    }
    
    // Set quantity directly to opening stock quantity (assuming this is the start)
    // Or ADD to it? Since we depleted it, let's set it.
    // Ideally we want to "reset" to what it should be. 
    // If we assume Opening Stock is the START, then stock should be >= Opening Stock (minus valid sales).
    // For now, let's just ADD the opening stock quantity back, effectively reversing the "draining" that might have happened or ensuring it exists.
    // Actually, since Closing Stock DEDUCTED it, we just need to add it back if it's missing.
    
    // Better approach for safe recovery:
    // If stock is 0, set it to Opening Stock quantity.
    if ($stock->quantity <= 0) {
        $stock->quantity = $os->quantity;
        
        // Fill other fields if new
        $stock->unit_cost = $os->unit_cost;
        $stock->expiry_date = $os->expiry_date;
        $stock->location = $os->location;
        $stock->notes = 'Restored from Opening Stock';
        // Fill pricing
        $stock->list_price_before_tax = $os->list_price_before_tax;
        $stock->fed_tax_percent = $os->fed_tax_percent;
        $stock->fed_sales_tax = $os->fed_sales_tax;
        $stock->net_list_price = $os->net_list_price;
        $stock->distribution_margin = $os->distribution_margin;
        $stock->trade_price_before_tax = $os->trade_price_before_tax;
        $stock->fed_2 = $os->fed_2;
        $stock->sales_tax_3 = $os->sales_tax_3;
        $stock->net_trade_price = $os->net_trade_price;
        $stock->retailer_margin = $os->retailer_margin;
        $stock->consumer_price_before_tax = $os->consumer_price_before_tax;
        $stock->fed_5 = $os->fed_5;
        $stock->sales_tax_6 = $os->sales_tax_6;
        $stock->net_consumer_price = $os->net_consumer_price;
        $stock->unit_price = $os->unit_price;
        $stock->total_margin = $os->total_margin;
        
        $stock->save();
        echo "Restored Stock ID {$stock->id} with Qty: {$stock->quantity}\n";
    }
}

echo "Done.\n";
