<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Simplify opening_stocks and closing_stocks tables to match new product field structure.
     */
    public function up(): void
    {
        // Update opening_stocks table
        Schema::table('opening_stocks', function (Blueprint $table) {
            // Add new fields
            if (!Schema::hasColumn('opening_stocks', 'pieces_per_packing')) {
                $table->integer('pieces_per_packing')->nullable()->default(1)->after('location');
            }
            if (!Schema::hasColumn('opening_stocks', 'fed_percent')) {
                $table->decimal('fed_percent', 8, 2)->nullable()->default(0)->after('fed_sales_tax');
            }
            if (!Schema::hasColumn('opening_stocks', 'tp_rate')) {
                $table->decimal('tp_rate', 12, 4)->nullable()->default(0)->after('distribution_margin');
            }
            if (!Schema::hasColumn('opening_stocks', 'invoice_price')) {
                $table->decimal('invoice_price', 12, 4)->nullable()->default(0)->after('tp_rate');
            }
            
            // Rename retailer_margin to retail_margin if exists
            if (Schema::hasColumn('opening_stocks', 'retailer_margin') && !Schema::hasColumn('opening_stocks', 'retail_margin')) {
                $table->renameColumn('retailer_margin', 'retail_margin');
            } elseif (!Schema::hasColumn('opening_stocks', 'retail_margin') && !Schema::hasColumn('opening_stocks', 'retailer_margin')) {
                $table->decimal('retail_margin', 8, 2)->nullable()->default(0)->after('fed_percent');
            }
        });

        // Remove old unnecessary fields from opening_stocks
        Schema::table('opening_stocks', function (Blueprint $table) {
            $columnsToRemove = [
                'fed_tax_percent', 'net_list_price', 'trade_price_before_tax',
                'fed_2', 'sales_tax_3', 'net_trade_price', 'consumer_price_before_tax',
                'fed_5', 'sales_tax_6', 'net_consumer_price', 'total_margin'
            ];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('opening_stocks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Update closing_stocks table
        Schema::table('closing_stocks', function (Blueprint $table) {
            // Add new fields
            if (!Schema::hasColumn('closing_stocks', 'pieces_per_packing')) {
                $table->integer('pieces_per_packing')->nullable()->default(1)->after('location');
            }
            if (!Schema::hasColumn('closing_stocks', 'fed_percent')) {
                $table->decimal('fed_percent', 8, 2)->nullable()->default(0)->after('fed_sales_tax');
            }
            if (!Schema::hasColumn('closing_stocks', 'tp_rate')) {
                $table->decimal('tp_rate', 12, 4)->nullable()->default(0)->after('distribution_margin');
            }
            if (!Schema::hasColumn('closing_stocks', 'invoice_price')) {
                $table->decimal('invoice_price', 12, 4)->nullable()->default(0)->after('tp_rate');
            }
            
            // Rename retailer_margin to retail_margin if exists
            if (Schema::hasColumn('closing_stocks', 'retailer_margin') && !Schema::hasColumn('closing_stocks', 'retail_margin')) {
                $table->renameColumn('retailer_margin', 'retail_margin');
            } elseif (!Schema::hasColumn('closing_stocks', 'retail_margin') && !Schema::hasColumn('closing_stocks', 'retailer_margin')) {
                $table->decimal('retail_margin', 8, 2)->nullable()->default(0)->after('fed_percent');
            }
        });

        // Remove old unnecessary fields from closing_stocks
        Schema::table('closing_stocks', function (Blueprint $table) {
            $columnsToRemove = [
                'fed_tax_percent', 'net_list_price', 'trade_price_before_tax',
                'fed_2', 'sales_tax_3', 'net_trade_price', 'consumer_price_before_tax',
                'fed_5', 'sales_tax_6', 'net_consumer_price', 'total_margin'
            ];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('closing_stocks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['opening_stocks', 'closing_stocks'];
        $newColumns = ['pieces_per_packing', 'fed_percent', 'tp_rate', 'invoice_price'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $newColumns) {
                foreach ($newColumns as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
                if (Schema::hasColumn($tableName, 'retail_margin')) {
                    $table->renameColumn('retail_margin', 'retailer_margin');
                }
            });
        }
    }
};
