<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update products table to 4 decimal precision
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('list_price_before_tax', 15, 4)->nullable()->default(0)->change();
            $table->decimal('retail_margin', 15, 4)->nullable()->default(0)->change();
            $table->decimal('tp_rate', 15, 4)->nullable()->default(0)->change();
            $table->decimal('distribution_margin', 15, 4)->nullable()->default(0)->change();
            $table->decimal('invoice_price', 15, 4)->nullable()->default(0)->change();
            $table->decimal('fed_sales_tax', 15, 4)->nullable()->default(0)->change();
            $table->decimal('fed_percent', 15, 4)->nullable()->default(0)->change();
            $table->decimal('unit_price', 15, 4)->nullable()->default(0)->change();
        });

        // Update invoice_items table to 4 decimal precision
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('price', 15, 4)->nullable()->default(0)->change();
            $table->decimal('discount', 15, 4)->nullable()->default(0)->change();
            $table->decimal('scheme_discount', 15, 4)->nullable()->default(0)->change();
            $table->decimal('tax', 15, 4)->nullable()->default(0)->change();
            $table->decimal('tax_percent', 15, 4)->nullable()->default(0)->change();
            $table->decimal('fed_percent', 15, 4)->nullable()->default(0)->change();
            $table->decimal('fed_amount', 15, 4)->nullable()->default(0)->change();
            $table->decimal('extra_tax_percent', 15, 4)->nullable()->default(0)->change();
            $table->decimal('extra_tax_amount', 15, 4)->nullable()->default(0)->change();
            $table->decimal('adv_tax_percent', 15, 4)->nullable()->default(0)->change();
            $table->decimal('adv_tax_amount', 15, 4)->nullable()->default(0)->change();
            $table->decimal('exclusive_amount', 15, 4)->nullable()->default(0)->change();
            $table->decimal('gross_amount', 15, 4)->nullable()->default(0)->change();
            $table->decimal('line_total', 15, 4)->nullable()->default(0)->change();
            $table->decimal('list_price_before_tax', 15, 4)->nullable()->default(0)->change();
            $table->decimal('retail_margin', 15, 4)->nullable()->default(0)->change();
            $table->decimal('tp_rate', 15, 4)->nullable()->default(0)->change();
            $table->decimal('distribution_margin', 15, 4)->nullable()->default(0)->change();
            $table->decimal('invoice_price', 15, 4)->nullable()->default(0)->change();
            $table->decimal('unit_price', 15, 4)->nullable()->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to 2 decimal precision
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('list_price_before_tax', 12, 2)->nullable()->default(0)->change();
            $table->decimal('retail_margin', 8, 2)->nullable()->default(0)->change();
            $table->decimal('tp_rate', 12, 4)->nullable()->default(0)->change();
            $table->decimal('distribution_margin', 8, 2)->nullable()->default(0)->change();
            $table->decimal('invoice_price', 12, 4)->nullable()->default(0)->change();
            $table->decimal('fed_sales_tax', 8, 2)->nullable()->default(0)->change();
            $table->decimal('fed_percent', 8, 2)->nullable()->default(0)->change();
            $table->decimal('unit_price', 12, 2)->nullable()->default(0)->change();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->nullable()->default(0)->change();
            $table->decimal('discount', 12, 2)->nullable()->default(0)->change();
            $table->decimal('scheme_discount', 12, 2)->nullable()->default(0)->change();
            $table->decimal('tax', 12, 2)->nullable()->default(0)->change();
            $table->decimal('line_total', 12, 2)->nullable()->default(0)->change();
        });
    }
};
