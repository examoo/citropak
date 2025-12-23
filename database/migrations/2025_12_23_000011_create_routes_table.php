<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ROUTES TABLE (DISTRIBUTION-SCOPED)
 * 
 * Delivery routes for a distribution.
 * Routes group customers for organized delivery.
 * 
 * MANDATORY: distribution_id FK with index
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')                       // MANDATORY FK
                  ->constrained('distributions')
                  ->onDelete('cascade');
            $table->string('name');
            $table->timestamps();

            // MANDATORY index for tenant filtering
            $table->index('distribution_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
