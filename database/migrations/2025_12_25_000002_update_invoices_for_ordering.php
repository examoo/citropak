<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * UPDATE INVOICES AND INVOICE_ITEMS TABLES
 * 
 * Adds additional columns for comprehensive invoicing:
 * - Invoice number, van_id, subtotals, taxes
 * - Item-level cartons, pieces, scheme, tax details
 */
return new class extends Migration
{
    public function up(): void
    {
        // Update invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('invoice_number', 50)->unique()->after('id');
            $table->foreignId('van_id')
                  ->nullable()
                  ->after('distribution_id')
                  ->constrained('vans')
                  ->onDelete('set null');
            $table->decimal('subtotal', 15, 2)->default(0)->after('total_amount');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('subtotal');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('discount_amount');
            $table->decimal('fed_amount', 15, 2)->default(0)->after('tax_amount');
            $table->foreignId('created_by')
                  ->nullable()
                  ->after('invoice_date')
                  ->constrained('users')
                  ->onDelete('set null');
            $table->text('notes')->nullable()->after('created_by');
            
            $table->index('van_id');
            $table->index('invoice_number');
        });

        // Update invoice_items table
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->integer('cartons')->default(0)->after('product_id');
            $table->integer('pieces')->default(0)->after('cartons');
            $table->integer('total_pieces')->default(0)->after('pieces');
            $table->foreignId('scheme_id')
                  ->nullable()
                  ->after('discount')
                  ->constrained('schemes')
                  ->onDelete('set null');
            $table->decimal('scheme_discount', 12, 2)->default(0)->after('scheme_id');
            $table->decimal('tax_percent', 5, 2)->default(0)->after('tax');
            $table->decimal('fed_percent', 5, 2)->default(0)->after('tax_percent');
            $table->decimal('fed_amount', 12, 2)->default(0)->after('fed_percent');
            $table->decimal('line_total', 15, 2)->default(0)->after('fed_amount');
            
            $table->index('scheme_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['scheme_id']);
            $table->dropIndex(['scheme_id']);
            $table->dropColumn(['cartons', 'pieces', 'total_pieces', 'scheme_id', 'scheme_discount', 'tax_percent', 'fed_percent', 'fed_amount', 'line_total']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['van_id']);
            $table->dropForeign(['created_by']);
            $table->dropIndex(['van_id']);
            $table->dropIndex(['invoice_number']);
            $table->dropColumn(['invoice_number', 'van_id', 'subtotal', 'discount_amount', 'tax_amount', 'fed_amount', 'created_by', 'notes']);
        });
    }
};
