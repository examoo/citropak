<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Simplify stock tables to match new product field structure.
     */
    public function up(): void
    {
        // Update stocks table
        Schema::table('stocks', function (Blueprint $table) {
            // Add new fields
            if (!Schema::hasColumn('stocks', 'pieces_per_packing')) {
                $table->integer('pieces_per_packing')->nullable()->default(1)->after('location');
            }
            if (!Schema::hasColumn('stocks', 'fed_percent')) {
                $table->decimal('fed_percent', 8, 2)->nullable()->default(0)->after('fed_sales_tax');
            }
            if (!Schema::hasColumn('stocks', 'tp_rate')) {
                $table->decimal('tp_rate', 12, 4)->nullable()->default(0)->after('distribution_margin');
            }
            if (!Schema::hasColumn('stocks', 'invoice_price')) {
                $table->decimal('invoice_price', 12, 4)->nullable()->default(0)->after('tp_rate');
            }
            
            // Rename retailer_margin to retail_margin if exists
            if (Schema::hasColumn('stocks', 'retailer_margin') && !Schema::hasColumn('stocks', 'retail_margin')) {
                $table->renameColumn('retailer_margin', 'retail_margin');
            } elseif (!Schema::hasColumn('stocks', 'retail_margin') && !Schema::hasColumn('stocks', 'retailer_margin')) {
                $table->decimal('retail_margin', 8, 2)->nullable()->default(0)->after('fed_percent');
            }
        });

        // Remove old unnecessary fields from stocks
        Schema::table('stocks', function (Blueprint $table) {
            $columnsToRemove = [
                'fed_tax_percent', 'net_list_price', 'trade_price_before_tax',
                'fed_2', 'sales_tax_3', 'net_trade_price', 'consumer_price_before_tax',
                'fed_5', 'sales_tax_6', 'net_consumer_price', 'total_margin'
            ];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('stocks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Update stock_in_items table
        Schema::table('stock_in_items', function (Blueprint $table) {
            // Add new fields
            if (!Schema::hasColumn('stock_in_items', 'pieces_per_packing')) {
                $table->integer('pieces_per_packing')->nullable()->default(1)->after('location');
            }
            if (!Schema::hasColumn('stock_in_items', 'fed_percent')) {
                $table->decimal('fed_percent', 8, 2)->nullable()->default(0)->after('fed_sales_tax');
            }
            if (!Schema::hasColumn('stock_in_items', 'tp_rate')) {
                $table->decimal('tp_rate', 12, 4)->nullable()->default(0)->after('distribution_margin');
            }
            if (!Schema::hasColumn('stock_in_items', 'invoice_price')) {
                $table->decimal('invoice_price', 12, 4)->nullable()->default(0)->after('tp_rate');
            }
            
            // Rename retailer_margin to retail_margin if exists
            if (Schema::hasColumn('stock_in_items', 'retailer_margin') && !Schema::hasColumn('stock_in_items', 'retail_margin')) {
                $table->renameColumn('retailer_margin', 'retail_margin');
            } elseif (!Schema::hasColumn('stock_in_items', 'retail_margin') && !Schema::hasColumn('stock_in_items', 'retailer_margin')) {
                $table->decimal('retail_margin', 8, 2)->nullable()->default(0)->after('fed_percent');
            }
        });

        // Remove old unnecessary fields from stock_in_items
        Schema::table('stock_in_items', function (Blueprint $table) {
            $columnsToRemove = [
                'fed_tax_percent', 'net_list_price', 'trade_price_before_tax',
                'fed_2', 'sales_tax_3', 'net_trade_price', 'consumer_price_before_tax',
                'fed_5', 'sales_tax_6', 'net_consumer_price', 'total_margin'
            ];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('stock_in_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Update stock_out_items table
        Schema::table('stock_out_items', function (Blueprint $table) {
            // Add new fields
            if (!Schema::hasColumn('stock_out_items', 'pieces_per_packing')) {
                $table->integer('pieces_per_packing')->nullable()->default(1)->after('location');
            }
            if (!Schema::hasColumn('stock_out_items', 'fed_percent')) {
                $table->decimal('fed_percent', 8, 2)->nullable()->default(0)->after('fed_sales_tax');
            }
            if (!Schema::hasColumn('stock_out_items', 'tp_rate')) {
                $table->decimal('tp_rate', 12, 4)->nullable()->default(0)->after('distribution_margin');
            }
            if (!Schema::hasColumn('stock_out_items', 'invoice_price')) {
                $table->decimal('invoice_price', 12, 4)->nullable()->default(0)->after('tp_rate');
            }
            
            // Rename retailer_margin to retail_margin if exists
            if (Schema::hasColumn('stock_out_items', 'retailer_margin') && !Schema::hasColumn('stock_out_items', 'retail_margin')) {
                $table->renameColumn('retailer_margin', 'retail_margin');
            } elseif (!Schema::hasColumn('stock_out_items', 'retail_margin') && !Schema::hasColumn('stock_out_items', 'retailer_margin')) {
                $table->decimal('retail_margin', 8, 2)->nullable()->default(0)->after('fed_percent');
            }
        });

        // Remove old unnecessary fields from stock_out_items
        Schema::table('stock_out_items', function (Blueprint $table) {
            $columnsToRemove = [
                'fed_tax_percent', 'net_list_price', 'trade_price_before_tax',
                'fed_2', 'sales_tax_3', 'net_trade_price', 'consumer_price_before_tax',
                'fed_5', 'sales_tax_6', 'net_consumer_price', 'total_margin'
            ];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('stock_out_items', $column)) {
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
        // Note: This is a simplification migration - full rollback would require recreating all old columns
        // For simplicity, we just remove the new columns added
        $tables = ['stocks', 'stock_in_items', 'stock_out_items'];
        $newColumns = ['pieces_per_packing', 'fed_percent', 'tp_rate', 'invoice_price'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $newColumns) {
                foreach ($newColumns as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
                // Rename retail_margin back to retailer_margin
                if (Schema::hasColumn($tableName, 'retail_margin')) {
                    $table->renameColumn('retail_margin', 'retailer_margin');
                }
            });
        }
    }
};
