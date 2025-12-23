<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * VANS TABLE (DISTRIBUTION-SCOPED)
 * 
 * Delivery vehicles for a distribution.
 * 
 * MANDATORY: distribution_id FK with index
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop existing vans table
        Schema::dropIfExists('vans');

        Schema::create('vans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')                       // MANDATORY FK
                  ->constrained('distributions')
                  ->onDelete('cascade');
            $table->string('code');                                    // e.g., VAN01, VAN02
            $table->enum('status', ['active', 'inactive'])
                  ->default('active');
            $table->timestamps();

            // MANDATORY index for tenant filtering
            $table->index('distribution_id');
            
            // Unique code within distribution
            $table->unique(['distribution_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vans');
    }
};
