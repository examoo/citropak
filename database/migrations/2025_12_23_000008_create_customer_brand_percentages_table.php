<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CUSTOMER BRAND PERCENTAGES TABLE (DISTRIBUTION-SCOPED)
 * 
 * Tracks brand-specific percentages/margins for each customer.
 * Used for calculating discounts or commissions per brand.
 * 
 * MANDATORY: distribution_id FK with index
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_brand_percentages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')                       // MANDATORY FK
                  ->constrained('distributions')
                  ->onDelete('cascade');
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->onDelete('cascade');
            $table->foreignId('brand_id')
                  ->constrained('brands')
                  ->onDelete('cascade');
            $table->decimal('percentage', 5, 2)->default(0);
            $table->timestamps();

            // MANDATORY index for tenant filtering
            $table->index('distribution_id');
            $table->index('customer_id');
            $table->index('brand_id');
            
            // Unique constraint: one percentage per customer per brand per distribution
            $table->unique(['distribution_id', 'customer_id', 'brand_id'], 'cbp_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_brand_percentages');
    }
};
