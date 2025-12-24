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
        Schema::table('products', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('products', 'dms_code')) {
                $table->string('dms_code')->nullable()->after('id');
            }
            if (!Schema::hasColumn('products', 'list_price_before_tax')) {
                $table->decimal('list_price_before_tax', 12, 2)->default(0)->after('type_id');
            }
            if (!Schema::hasColumn('products', 'fed_tax_percent')) {
                $table->decimal('fed_tax_percent', 12, 2)->default(0)->after('list_price_before_tax');
            }
            if (!Schema::hasColumn('products', 'fed_sales_tax')) {
                $table->decimal('fed_sales_tax', 12, 2)->default(0)->after('fed_tax_percent');
            }
            if (!Schema::hasColumn('products', 'net_list_price')) {
                $table->decimal('net_list_price', 12, 2)->default(0)->after('fed_sales_tax');
            }
            if (!Schema::hasColumn('products', 'distribution_margin')) {
                $table->decimal('distribution_margin', 12, 2)->default(0)->after('net_list_price');
            }
            if (!Schema::hasColumn('products', 'distribution_manager_percent')) {
                $table->decimal('distribution_manager_percent', 12, 2)->default(0)->after('distribution_margin');
            }
            if (!Schema::hasColumn('products', 'trade_price_before_tax')) {
                $table->decimal('trade_price_before_tax', 12, 2)->default(0)->after('distribution_manager_percent');
            }
            if (!Schema::hasColumn('products', 'fed_2')) {
                $table->decimal('fed_2', 12, 2)->default(0)->after('trade_price_before_tax');
            }
            if (!Schema::hasColumn('products', 'sales_tax_3')) {
                $table->decimal('sales_tax_3', 12, 2)->default(0)->after('fed_2');
            }
            if (!Schema::hasColumn('products', 'net_trade_price')) {
                $table->decimal('net_trade_price', 12, 2)->default(0)->after('sales_tax_3');
            }
            if (!Schema::hasColumn('products', 'retailer_margin')) {
                $table->decimal('retailer_margin', 12, 2)->default(0)->after('net_trade_price');
            }
            if (!Schema::hasColumn('products', 'retailer_margin_4')) {
                $table->decimal('retailer_margin_4', 12, 2)->default(0)->after('retailer_margin');
            }
            if (!Schema::hasColumn('products', 'consumer_price_before_tax')) {
                $table->decimal('consumer_price_before_tax', 12, 2)->default(0)->after('retailer_margin_4');
            }
            if (!Schema::hasColumn('products', 'fed_5')) {
                $table->decimal('fed_5', 12, 2)->default(0)->after('consumer_price_before_tax');
            }
            if (!Schema::hasColumn('products', 'sales_tax_6')) {
                $table->decimal('sales_tax_6', 12, 2)->default(0)->after('fed_5');
            }
            if (!Schema::hasColumn('products', 'net_consumer_price')) {
                $table->decimal('net_consumer_price', 12, 2)->default(0)->after('sales_tax_6');
            }
            if (!Schema::hasColumn('products', 'total_margin')) {
                $table->decimal('total_margin', 12, 2)->default(0)->after('net_consumer_price');
            }
            if (!Schema::hasColumn('products', 'unit_price')) {
                $table->decimal('unit_price', 12, 2)->default(0)->after('total_margin');
            }
            if (!Schema::hasColumn('products', 'packing')) {
                $table->string('packing')->nullable()->after('unit_price');
            }
            if (!Schema::hasColumn('products', 'packing_one')) {
                $table->string('packing_one')->nullable()->after('packing');
            }
            if (!Schema::hasColumn('products', 'reorder_level')) {
                $table->integer('reorder_level')->default(0)->after('packing_one');
            }
            if (!Schema::hasColumn('products', 'stock_quantity')) {
                $table->integer('stock_quantity')->default(0)->after('reorder_level');
            }
            if (!Schema::hasColumn('products', 'description')) {
                $table->text('description')->nullable()->after('stock_quantity');
            }
            if (!Schema::hasColumn('products', 'price')) {
                $table->decimal('price', 12, 2)->default(0)->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = [
                'dms_code', 'list_price_before_tax', 'fed_tax_percent', 'fed_sales_tax',
                'net_list_price', 'distribution_margin', 'distribution_manager_percent',
                'trade_price_before_tax', 'fed_2', 'sales_tax_3', 'net_trade_price',
                'retailer_margin', 'retailer_margin_4', 'consumer_price_before_tax',
                'fed_5', 'sales_tax_6', 'net_consumer_price', 'total_margin', 'unit_price',
                'packing', 'packing_one', 'reorder_level', 'stock_quantity', 'description', 'price'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
