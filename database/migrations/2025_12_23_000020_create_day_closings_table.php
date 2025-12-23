<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DAY CLOSINGS TABLE (DISTRIBUTION-SCOPED)
 * 
 * Daily closing records for a distribution.
 * Marks when each business day is officially closed.
 * 
 * MANDATORY: distribution_id FK with index
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')                       // MANDATORY FK
                  ->constrained('distributions')
                  ->onDelete('cascade');
            $table->date('date');
            $table->foreignId('closed_by')                             // User who closed the day
                  ->constrained('users')
                  ->onDelete('restrict');
            $table->timestamps();

            // MANDATORY index for tenant filtering
            $table->index('distribution_id');
            $table->index('date');
            
            // Unique: one closing per date per distribution
            $table->unique(['distribution_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_closings');
    }
};
