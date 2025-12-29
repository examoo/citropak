<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADD FBR FIELDS TO INVOICE ITEMS TABLE
 * 
 * Stores HS code and UOM code for FBR compliance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            // FBR Item Data
            $table->string('hs_code', 20)->nullable()->after('is_free')
                  ->comment('Harmonized System Code for FBR');
            $table->string('uom_code', 10)->nullable()->after('hs_code')
                  ->comment('Unit of Measure Code for FBR (PCS, CTN, KG, etc.)');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['hs_code', 'uom_code']);
        });
    }
};
