<?php

use App\Models\ClosingStock;
use App\Models\Stock;

echo "Moving Closing Stocks back to Inventory...\n";

// Get all Closing Stocks (Draft or Posted, depending on what user assumes is 'trapped')
// Since user said 'reverted', they might be Draft now.
$closingStocks = ClosingStock::where('quantity', '>', 0)->get();

foreach ($closingStocks as $cs) {
    // Find corresponding Stock
    $stock = Stock::where('product_id', $cs->product_id)
        ->where('distribution_id', $cs->distribution_id)
        // ->where('batch_number', $cs->batch_number) // Batch might be null or matching
        ->first();

    if ($stock) {
        $oldQty = $stock->quantity;
        $stock->quantity += $cs->quantity; // Add it back
        $stock->save();
        
        echo "Restored {$cs->quantity} to Stock ID {$stock->id}. (Old: $oldQty, New: {$stock->quantity})\n";
        
        // Delete the Closing Stock since we 'moved' it back?
        // Or just leave it? User said "revert... to stocks". 
        // Likely implies un-doing the transaction.
        $cs->delete();
        echo "Deleted Closing Stock ID {$cs->id}.\n";
    } else {
        echo "Stock not found for Closing Stock ID {$cs->id}\n";
    }
}

echo "Done.\n";
