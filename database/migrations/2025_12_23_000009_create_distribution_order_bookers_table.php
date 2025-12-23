<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ORDER BOOKERS TABLE (DISTRIBUTION-SCOPED)
 * 
 * Sales representatives who book orders for a distribution.
 * 
 * MANDATORY: distribution_id FK with index
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop existing order_bookers table
        Schema::dropIfExists('order_bookers');

        Schema::create('order_bookers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')                       // MANDATORY FK
                  ->constrained('distributions')
                  ->onDelete('cascade');
            $table->string('code');                                    // e.g., OB01, OB02
            $table->string('name');
            $table->timestamps();

            // MANDATORY index for tenant filtering
            $table->index('distribution_id');
            
            // Unique code within distribution
            $table->unique(['distribution_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_bookers');
    }
};
