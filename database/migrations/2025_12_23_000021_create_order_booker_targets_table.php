<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ORDER BOOKER TARGETS TABLE (DISTRIBUTION-SCOPED)
 * 
 * Monthly sales targets for order bookers.
 * Used for performance tracking and reporting.
 * 
 * MANDATORY: distribution_id FK with index
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_booker_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')                       // MANDATORY FK
                  ->constrained('distributions')
                  ->onDelete('cascade');
            $table->foreignId('order_booker_id')
                  ->constrained('order_bookers')
                  ->onDelete('cascade');
            $table->string('month', 7);                                // Format: YYYY-MM
            $table->decimal('target_amount', 15, 2);                   // Monthly target
            $table->timestamps();

            // MANDATORY index for tenant filtering
            $table->index('distribution_id');
            $table->index('order_booker_id');
            $table->index('month');
            
            // Unique: one target per order booker per month per distribution
            $table->unique(['distribution_id', 'order_booker_id', 'month'], 'obt_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_booker_targets');
    }
};
