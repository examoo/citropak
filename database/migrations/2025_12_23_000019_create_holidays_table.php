<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HOLIDAYS TABLE (OPTIONALLY DISTRIBUTION-SCOPED)
 * 
 * Distribution-specific or global holidays.
 * - distribution_id = NULL: Holiday applies to ALL distributions
 * - distribution_id = ID: Holiday applies only to that distribution
 * 
 * Used for operations and delivery scheduling.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')                       // NULL = global holiday
                  ->nullable()
                  ->constrained('distributions')
                  ->onDelete('cascade');
            $table->date('date');
            $table->string('description')->nullable();
            $table->timestamps();

            // Index for filtering
            $table->index('distribution_id');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
