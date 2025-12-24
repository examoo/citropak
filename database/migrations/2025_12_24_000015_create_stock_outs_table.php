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
        // Stock Out header table
        Schema::create('stock_outs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')->constrained()->cascadeOnDelete();
            $table->string('bilty_number')->nullable();
            $table->date('date');
            $table->text('remarks')->nullable();
            $table->enum('status', ['draft', 'posted'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Stock Out items table
        Schema::create('stock_out_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_out_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('location')->nullable();
            $table->decimal('unit_cost', 12, 2)->default(0);
            // Pricing fields from product
            $table->decimal('list_price_before_tax', 12, 2)->default(0);
            $table->decimal('fed_tax_percent', 12, 2)->default(0);
            $table->decimal('fed_sales_tax', 12, 2)->default(0);
            $table->decimal('net_list_price', 12, 2)->default(0);
            $table->decimal('distribution_margin', 12, 2)->default(0);
            $table->decimal('trade_price_before_tax', 12, 2)->default(0);
            $table->decimal('fed_2', 12, 2)->default(0);
            $table->decimal('sales_tax_3', 12, 2)->default(0);
            $table->decimal('net_trade_price', 12, 2)->default(0);
            $table->decimal('retailer_margin', 12, 2)->default(0);
            $table->decimal('consumer_price_before_tax', 12, 2)->default(0);
            $table->decimal('fed_5', 12, 2)->default(0);
            $table->decimal('sales_tax_6', 12, 2)->default(0);
            $table->decimal('net_consumer_price', 12, 2)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_margin', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_out_items');
        Schema::dropIfExists('stock_outs');
    }
};
