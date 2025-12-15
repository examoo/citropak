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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('dms_code')->nullable();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->decimal('list_price_before_tax', 12, 2)->default(0);
            $table->decimal('fed_tax_percent', 12, 2)->default(0);
            $table->decimal('fed_sales_tax', 12, 2)->default(0);
            $table->decimal('net_list_price', 12, 2)->default(0);
            $table->decimal('distribution_margin', 12, 2)->default(0);
            $table->decimal('distribution_manager_percent', 12, 2)->default(0);
            $table->decimal('trade_price_before_tax', 12, 2)->default(0);
            $table->decimal('fed_2', 12, 2)->default(0);
            $table->decimal('sales_tax_3', 12, 2)->default(0);
            $table->decimal('net_trade_price', 12, 2)->default(0);
            $table->decimal('retailer_margin', 12, 2)->default(0);
            $table->decimal('retailer_margin_4', 12, 2)->default(0);
            $table->decimal('consumer_price_before_tax', 12, 2)->default(0);
            $table->decimal('fed_5', 12, 2)->default(0);
            $table->decimal('sales_tax_6', 12, 2)->default(0);
            $table->decimal('net_consumer_price', 12, 2)->default(0);
            $table->decimal('total_margin', 12, 2)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->string('packing')->nullable();
            $table->string('packing_one')->nullable();
            $table->integer('reorder_level')->default(0);
            $table->string('type')->nullable();
            $table->integer('stock_quantity')->default(0); // Keeping stock_quantity as generic stock
            $table->string('sku')->nullable(); // Keeping SKU as alias or separate
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->decimal('price', 12, 2)->default(0); // Keeping generic price for backward compat or alias to unit_price
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
