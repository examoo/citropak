<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STOCK LEDGERS TABLE (DISTRIBUTION-SCOPED)
 * 
 * Inventory movement ledger for a distribution.
 * Tracks all stock in/out movements with running balance.
 * 
 * MANDATORY: distribution_id FK with index
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')                       // MANDATORY FK
                  ->constrained('distributions')
                  ->onDelete('cascade');
            $table->foreignId('product_id')                            // References GLOBAL product
                  ->constrained('products')
                  ->onDelete('restrict');
            $table->integer('in_qty')->default(0);                     // Quantity received
            $table->integer('out_qty')->default(0);                    // Quantity issued
            $table->integer('balance')->default(0);                    // Running balance
            $table->enum('type', ['sale', 'purchase', 'damage', 'adjustment', 'return']);
            $table->timestamps();

            // MANDATORY index for tenant filtering
            $table->index('distribution_id');
            $table->index('product_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledgers');
    }
};
