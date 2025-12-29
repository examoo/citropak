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
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('extra_tax_percent', 8, 4)->default(0)->after('tax_percent');
            $table->decimal('extra_tax_amount', 10, 2)->default(0)->after('extra_tax_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['extra_tax_percent', 'extra_tax_amount']);
        });
    }
};
