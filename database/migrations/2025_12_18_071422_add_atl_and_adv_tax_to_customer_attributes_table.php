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
        Schema::table('customer_attributes', function (Blueprint $table) {
            $table->string('atl')->default('active')->after('value'); // active, inactive
            $table->decimal('adv_tax_percent', 5, 2)->default(0.00)->after('atl');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_attributes', function (Blueprint $table) {
            $table->dropColumn(['atl', 'adv_tax_percent']);
        });
    }
};
