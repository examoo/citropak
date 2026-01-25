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
            $table->decimal('manual_discount_amount', 12, 2)->default(0)->after('discount');
            $table->decimal('manual_discount_percentage', 5, 2)->default(0)->after('manual_discount_amount');
            $table->decimal('scheme_discount_amount', 12, 2)->default(0)->after('manual_discount_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['manual_discount_amount', 'manual_discount_percentage', 'scheme_discount_amount']);
        });
    }
};
