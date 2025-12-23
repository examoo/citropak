<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * INVOICE ITEMS TABLE (DISTRIBUTION-SCOPED)
 * 
 * Line items for invoices.
 * Each item links to a global product with distribution-specific pricing.
 * 
 * MANDATORY: distribution_id FK with index
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')                       // MANDATORY FK
                  ->constrained('distributions')
                  ->onDelete('cascade');
            $table->foreignId('invoice_id')
                  ->constrained('invoices')
                  ->onDelete('cascade');
            $table->foreignId('product_id')                            // References GLOBAL product
                  ->constrained('products')
                  ->onDelete('restrict');
            $table->integer('quantity');
            $table->decimal('price', 12, 2);                           // Unit price at time of sale
            $table->decimal('discount', 12, 2)->default(0);            // Line discount
            $table->decimal('tax', 12, 2)->default(0);                 // Tax amount
            $table->timestamps();

            // MANDATORY index for tenant filtering
            $table->index('distribution_id');
            $table->index('invoice_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
