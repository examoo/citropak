<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRODUCT VARIANTS TABLE (GLOBAL)
 * 
 * Product variations (size, flavor, color, etc.) shared across ALL distributions.
 * NO distribution_id - managed only by Super Admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')                            // FK to products
                  ->constrained('products')
                  ->onDelete('cascade');
            $table->string('variant_name');                            // e.g., "250ml", "Red", "Large"
            $table->enum('status', ['active', 'inactive'])
                  ->default('active');
            $table->timestamps();

            // Index for product lookups
            $table->index('product_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
