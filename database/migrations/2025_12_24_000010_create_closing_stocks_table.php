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
        Schema::create('closing_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('distribution_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('cartons')->default(0);
            $table->integer('pieces')->default(0);
            $table->integer('pieces_per_carton')->default(1);
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
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'posted'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('closing_stocks');
    }
};
