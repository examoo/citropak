<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRODUCTS TABLE (GLOBAL)
 * 
 * Master product catalog shared across ALL distributions.
 * NO distribution_id - managed only by Super Admin.
 * 
 * IMPORTANT:
 * - Products are GLOBAL - same product can be sold by any distribution
 * - Distribution-specific pricing/stock is handled in other tables
 * - Only Super Admin can add/edit products
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop existing products table if it exists (will be recreated)
        Schema::dropIfExists('products');

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')                              // FK to brands
                  ->constrained('brands')
                  ->onDelete('restrict');
            $table->foreignId('category_id')                           // FK to categories
                  ->constrained('categories')
                  ->onDelete('restrict');
            $table->string('name');
            $table->string('sku')->unique();                           // Unique SKU across system
            $table->string('unit')->default('pcs');                    // Unit of measure (pcs, kg, ltr)
            $table->enum('status', ['active', 'inactive'])
                  ->default('active');
            $table->timestamps();

            // Indexes for common queries
            $table->index('brand_id');
            $table->index('category_id');
            $table->index('status');
            $table->index('sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
