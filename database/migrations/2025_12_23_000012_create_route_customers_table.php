<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ROUTE CUSTOMERS TABLE (DISTRIBUTION-SCOPED)
 * 
 * Pivot table linking routes to customers.
 * Each customer can be assigned to a route for delivery scheduling.
 * 
 * MANDATORY: distribution_id FK with index
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')                       // MANDATORY FK
                  ->constrained('distributions')
                  ->onDelete('cascade');
            $table->foreignId('route_id')
                  ->constrained('routes')
                  ->onDelete('cascade');
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->onDelete('cascade');
            $table->timestamps();

            // MANDATORY index for tenant filtering
            $table->index('distribution_id');
            $table->index('route_id');
            $table->index('customer_id');
            
            // Unique: one customer per route per distribution
            $table->unique(['distribution_id', 'route_id', 'customer_id'], 'rc_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_customers');
    }
};
