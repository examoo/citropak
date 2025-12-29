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
            $table->decimal('adv_tax_percent', 8, 4)->default(0)->after('extra_tax_amount');
            $table->decimal('adv_tax_amount', 10, 2)->default(0)->after('adv_tax_percent');
            $table->decimal('gross_amount', 12, 2)->default(0)->after('adv_tax_amount');
            $table->decimal('exclusive_amount', 12, 2)->default(0)->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['adv_tax_percent', 'adv_tax_amount', 'gross_amount', 'exclusive_amount']);
        });
    }
};
