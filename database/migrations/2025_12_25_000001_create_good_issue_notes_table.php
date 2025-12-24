<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GOOD ISSUE NOTES TABLE (DISTRIBUTION-SCOPED)
 * 
 * Tracks goods issued from warehouse to VANs for delivery.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('good_issue_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')
                  ->constrained('distributions')
                  ->onDelete('cascade');
            $table->foreignId('van_id')
                  ->constrained('vans')
                  ->onDelete('restrict');
            $table->string('gin_number', 50)->unique();  // GIN-YYYYMMDD-XXX
            $table->date('issue_date');
            $table->enum('status', ['draft', 'issued', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('issued_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index('distribution_id');
            $table->index('van_id');
            $table->index('issue_date');
            $table->index('status');
        });

        Schema::create('good_issue_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('good_issue_note_id')
                  ->constrained('good_issue_notes')
                  ->onDelete('cascade');
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('restrict');
            $table->foreignId('stock_id')
                  ->nullable()
                  ->constrained('stocks')
                  ->onDelete('set null');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->timestamps();

            // Indexes
            $table->index('good_issue_note_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('good_issue_note_items');
        Schema::dropIfExists('good_issue_notes');
    }
};
