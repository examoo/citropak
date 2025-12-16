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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('cnic')->nullable()->after('ntn_number');
            $table->string('sales_tax_number')->nullable()->after('ntn_number'); // STRN usually near NTN
            $table->string('day')->nullable()->after('distribution');
            $table->decimal('percentage', 5, 2)->default(0.00)->after('adv_tax_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['cnic', 'sales_tax_number', 'day', 'percentage']);
        });
    }
};
