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
        Schema::create('discount_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('distribution_id')->nullable()->constrained()->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('scheme_type', ['product', 'brand'])->default('product');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('from_qty')->default(1);
            $table->integer('to_qty')->nullable();
            $table->integer('pieces')->nullable();
            $table->string('free_product_code')->nullable();
            $table->decimal('amount_less', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_schemes');
    }
};
