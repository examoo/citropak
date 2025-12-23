<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SCHEMES TABLE (DISTRIBUTION-SCOPED)
 * 
 * Discount/promotion schemes for a distribution.
 * Can be applied at brand or product level.
 * 
 * MANDATORY: distribution_id FK with index
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')                       // MANDATORY FK
                  ->constrained('distributions')
                  ->onDelete('cascade');
            $table->enum('scheme_type', ['brand', 'product']);         // Scheme applies to brand or product
            $table->foreignId('brand_id')                              // Nullable - used if scheme_type = brand
                  ->nullable()
                  ->constrained('brands')
                  ->onDelete('cascade');
            $table->foreignId('product_id')                            // Nullable - used if scheme_type = product
                  ->nullable()
                  ->constrained('products')
                  ->onDelete('cascade');
            $table->enum('discount_type', ['percentage', 'fixed']);    // % discount or fixed amount
            $table->decimal('discount_value', 10, 2);                  // Discount amount
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // MANDATORY index for tenant filtering
            $table->index('distribution_id');
            $table->index('scheme_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schemes');
    }
};
