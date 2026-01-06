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
        Schema::create('shelf_monthly_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shelf_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->tinyInteger('month'); // 1-12
            $table->year('year');
            $table->decimal('sales_amount', 12, 2)->default(0);
            $table->boolean('rent_paid')->default(false);
            $table->decimal('rent_amount', 10, 2)->nullable();
            $table->decimal('incentive_earned', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Unique constraint for one record per shelf per month
            $table->unique(['shelf_id', 'month', 'year']);
            
            // Index for faster queries
            $table->index(['distribution_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shelf_monthly_records');
    }
};
