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
        Schema::table('distributions', function (Blueprint $table) {
            $table->string('address')->nullable()->after('status');
            $table->string('phone_number')->nullable()->after('address');
            $table->string('stn_number')->nullable()->after('strn_number');
            $table->enum('sales_tax_status', ['active', 'inactive'])->default('active')->after('stn_number');
            $table->enum('filer_status', ['filer', 'non_filer'])->default('filer')->after('sales_tax_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distributions', function (Blueprint $table) {
            $table->dropColumn(['address', 'phone_number', 'stn_number', 'sales_tax_status', 'filer_status']);
        });
    }
};
