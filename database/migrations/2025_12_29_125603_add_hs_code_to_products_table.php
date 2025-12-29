<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADD HS CODE TO PRODUCTS TABLE
 * 
 * Stores default HS code and UOM code for each product.
 * These are copied to invoice items when creating invoices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // FBR Product Data
            $table->string('hs_code', 20)->nullable()->after('status')
                  ->comment('Harmonized System Code for FBR');
            $table->string('uom_code', 10)->default('PCS')->after('hs_code')
                  ->comment('Default Unit of Measure Code for FBR');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['hs_code', 'uom_code']);
        });
    }
};
