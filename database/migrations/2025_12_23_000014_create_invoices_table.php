<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * INVOICES TABLE (DISTRIBUTION-SCOPED)
 * 
 * Sales invoices for a distribution.
 * Supports multiple invoice types: sale, damage, shelf_rent
 * Supports tax types: food, juice
 * 
 * MANDATORY: distribution_id FK with index
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')                       // MANDATORY FK
                  ->constrained('distributions')
                  ->onDelete('cascade');
            $table->foreignId('order_booker_id')
                  ->constrained('order_bookers')
                  ->onDelete('restrict');
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->onDelete('restrict');
            $table->enum('invoice_type', ['sale', 'damage', 'shelf_rent'])
                  ->default('sale');
            $table->enum('tax_type', ['food', 'juice'])
                  ->default('food');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->boolean('is_credit')->default(false);              // Cash or Credit sale
            $table->date('invoice_date');
            $table->timestamps();

            // MANDATORY index for tenant filtering
            $table->index('distribution_id');
            $table->index('order_booker_id');
            $table->index('customer_id');
            $table->index('invoice_type');
            $table->index('invoice_date');
            $table->index('is_credit');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
