<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add simplified product fields to invoice_items table.
     */
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            // Add new simplified product fields
            if (!Schema::hasColumn('invoice_items', 'pieces_per_packing')) {
                $table->integer('pieces_per_packing')->nullable()->default(1)->after('quantity');
            }
            if (!Schema::hasColumn('invoice_items', 'list_price_before_tax')) {
                $table->decimal('list_price_before_tax', 12, 4)->nullable()->default(0)->after('price');
            }
            if (!Schema::hasColumn('invoice_items', 'retail_margin')) {
                $table->decimal('retail_margin', 8, 2)->nullable()->default(0)->after('list_price_before_tax');
            }
            if (!Schema::hasColumn('invoice_items', 'tp_rate')) {
                $table->decimal('tp_rate', 12, 4)->nullable()->default(0)->after('retail_margin');
            }
            if (!Schema::hasColumn('invoice_items', 'distribution_margin')) {
                $table->decimal('distribution_margin', 8, 2)->nullable()->default(0)->after('tp_rate');
            }
            if (!Schema::hasColumn('invoice_items', 'invoice_price')) {
                $table->decimal('invoice_price', 12, 4)->nullable()->default(0)->after('distribution_margin');
            }
            if (!Schema::hasColumn('invoice_items', 'unit_price')) {
                $table->decimal('unit_price', 12, 4)->nullable()->default(0)->after('invoice_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $columnsToRemove = [
                'pieces_per_packing', 'list_price_before_tax', 'retail_margin',
                'tp_rate', 'distribution_margin', 'invoice_price', 'unit_price'
            ];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('invoice_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
