<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CUSTOMERS TABLE (DISTRIBUTION-SCOPED)
 * 
 * Distribution-specific customers.
 * Each distribution has its own customer base.
 * 
 * MANDATORY: distribution_id FK with index
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop existing customers table
        Schema::dropIfExists('customers');

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')                       // MANDATORY FK
                  ->constrained('distributions')
                  ->onDelete('cascade');
            $table->string('name');
            $table->string('area')->nullable();
            $table->string('contact')->nullable();
            $table->enum('status', ['active', 'inactive'])
                  ->default('active');
            $table->timestamps();

            // MANDATORY index for tenant filtering
            $table->index('distribution_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
