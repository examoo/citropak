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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->nullable();
            $table->string('van')->nullable();
            $table->string('shop_name');
            $table->text('address')->nullable();
            $table->string('sub_address')->nullable();
            $table->string('phone')->nullable(); // Telephone
            $table->string('category')->nullable();
            $table->string('channel')->nullable();
            $table->string('ntn_number')->nullable();
            $table->string('cnic')->nullable();
            $table->string('sales_tax_number')->nullable();
            $table->string('distribution')->nullable();
            $table->string('day')->nullable();
            $table->string('status')->default('active');
            $table->decimal('adv_tax_percent', 5, 2)->default(0.00);
            $table->decimal('percentage', 5, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
