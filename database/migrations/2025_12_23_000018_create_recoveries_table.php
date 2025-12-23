<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RECOVERIES TABLE (DISTRIBUTION-SCOPED)
 * 
 * Credit recovery tracking for invoices.
 * When customers pay against credit invoices.
 * 
 * MANDATORY: distribution_id FK with index
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recoveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')                       // MANDATORY FK
                  ->constrained('distributions')
                  ->onDelete('cascade');
            $table->foreignId('invoice_id')
                  ->constrained('invoices')
                  ->onDelete('cascade');
            $table->decimal('amount', 15, 2);                          // Amount recovered
            $table->date('recovery_date');
            $table->timestamps();

            // MANDATORY index for tenant filtering
            $table->index('distribution_id');
            $table->index('invoice_id');
            $table->index('recovery_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recoveries');
    }
};
