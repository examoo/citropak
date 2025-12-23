<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CUSTOMER ATTRIBUTES TABLE (DISTRIBUTION-SCOPED)
 * 
 * Distribution-specific customer attributes.
 * Types: van, category, channel, sub_distribution, sub_address
 * 
 * NOTE: 'sub_distribution' is a CUSTOMER ATTRIBUTE, NOT the tenant distribution.
 * The tenant distribution is stored in the 'distribution_id' FK column.
 * 
 * MANDATORY: distribution_id FK with index
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop existing customer_attributes table
        Schema::dropIfExists('customer_attributes');

        Schema::create('customer_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')                       // MANDATORY FK to tenant distribution
                  ->constrained('distributions')
                  ->onDelete('cascade');
            $table->string('type');                                    // van, category, channel, sub_distribution, sub_address
            $table->string('value');
            $table->boolean('atl')->default(false);
            $table->decimal('adv_tax_percent', 5, 2)->default(0);
            $table->timestamps();

            // MANDATORY index for tenant filtering
            $table->index('distribution_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_attributes');
    }
};
