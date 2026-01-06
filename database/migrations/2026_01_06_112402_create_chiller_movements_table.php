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
        Schema::create('chiller_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chiller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('to_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->enum('movement_type', ['assign', 'move', 'return'])->default('assign');
            $table->date('movement_date');
            $table->foreignId('order_booker_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Index for faster queries
            $table->index(['distribution_id', 'chiller_id', 'movement_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chiller_movements');
    }
};
