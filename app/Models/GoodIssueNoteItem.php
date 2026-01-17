<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GOOD ISSUE NOTE ITEM MODEL
 * 
 * Individual line items within a Good Issue Note.
 */
class GoodIssueNoteItem extends Model
{
    protected $fillable = [
        'good_issue_note_id',
        'product_id',
        'stock_id',
        'quantity',
        'returned_quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Get the parent GIN.
     */
    public function goodIssueNote(): BelongsTo
    {
        return $this->belongsTo(GoodIssueNote::class);
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the stock.
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
