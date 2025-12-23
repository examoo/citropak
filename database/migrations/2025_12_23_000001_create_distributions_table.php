<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DISTRIBUTIONS TABLE
 * 
 * Root tenant table for multi-tenant architecture.
 * Each distribution (DMS, TMS, LMS) operates as an isolated business unit.
 * All distribution-scoped tables reference this table via distribution_id FK.
 * 
 * IMPORTANT: This is the ROOT of the tenant hierarchy.
 * - Only Super Admin can manage distributions
 * - Distribution code must be unique (DMS, TMS, LMS)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distributions', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                    // Human-readable name
            $table->string('code', 10)->unique();                      // DMS, TMS, LMS
            $table->enum('status', ['active', 'inactive'])             // Distribution status
                  ->default('active');
            $table->timestamps();

            // Index for status filtering
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distributions');
    }
};
