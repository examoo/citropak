<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MONTHLY STOCK CLOSING TABLE (DISTRIBUTION-SCOPED)
 * 
 * Monthly opening/closing stock snapshots per product.
 * Used for period-end reporting and reconciliation.
 * 
 * MANDATORY: distribution_id FK with index
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_stock_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')                       // MANDATORY FK
                  ->constrained('distributions')
                  ->onDelete('cascade');
            $table->foreignId('product_id')                            // References GLOBAL product
                  ->constrained('products')
                  ->onDelete('restrict');
            $table->string('month', 7);                                // Format: YYYY-MM
            $table->integer('opening')->default(0);                    // Opening stock for month
            $table->integer('closing')->default(0);                    // Closing stock for month
            $table->timestamps();

            // MANDATORY index for tenant filtering
            $table->index('distribution_id');
            $table->index('product_id');
            $table->index('month');
            
            // Unique: one record per product per month per distribution
            $table->unique(['distribution_id', 'product_id', 'month'], 'msc_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_stock_closings');
    }
};
