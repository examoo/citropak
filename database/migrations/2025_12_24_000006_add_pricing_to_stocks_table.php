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
        Schema::table('stocks', function (Blueprint $table) {
            // Add pricing columns from product
            $table->decimal('list_price_before_tax', 12, 2)->default(0)->after('unit_cost');
            $table->decimal('fed_tax_percent', 12, 2)->default(0)->after('list_price_before_tax');
            $table->decimal('fed_sales_tax', 12, 2)->default(0)->after('fed_tax_percent');
            $table->decimal('net_list_price', 12, 2)->default(0)->after('fed_sales_tax');
            $table->decimal('distribution_margin', 12, 2)->default(0)->after('net_list_price');
            $table->decimal('trade_price_before_tax', 12, 2)->default(0)->after('distribution_margin');
            $table->decimal('fed_2', 12, 2)->default(0)->after('trade_price_before_tax');
            $table->decimal('sales_tax_3', 12, 2)->default(0)->after('fed_2');
            $table->decimal('net_trade_price', 12, 2)->default(0)->after('sales_tax_3');
            $table->decimal('retailer_margin', 12, 2)->default(0)->after('net_trade_price');
            $table->decimal('consumer_price_before_tax', 12, 2)->default(0)->after('retailer_margin');
            $table->decimal('fed_5', 12, 2)->default(0)->after('consumer_price_before_tax');
            $table->decimal('sales_tax_6', 12, 2)->default(0)->after('fed_5');
            $table->decimal('net_consumer_price', 12, 2)->default(0)->after('sales_tax_6');
            $table->decimal('unit_price', 12, 2)->default(0)->after('net_consumer_price');
            $table->decimal('total_margin', 12, 2)->default(0)->after('unit_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn([
                'list_price_before_tax', 'fed_tax_percent', 'fed_sales_tax', 'net_list_price',
                'distribution_margin', 'trade_price_before_tax', 'fed_2', 'sales_tax_3',
                'net_trade_price', 'retailer_margin', 'consumer_price_before_tax',
                'fed_5', 'sales_tax_6', 'net_consumer_price', 'unit_price', 'total_margin'
            ]);
        });
    }
};
