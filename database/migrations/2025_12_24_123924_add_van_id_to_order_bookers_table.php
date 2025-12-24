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
        Schema::table('order_bookers', function (Blueprint $table) {
            $table->foreignId('van_id')->nullable()->after('distribution_id')
                  ->constrained('vans')
                  ->onDelete('set null');
            $table->index('van_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_bookers', function (Blueprint $table) {
            $table->dropForeign(['van_id']);
            $table->dropIndex(['van_id']);
            $table->dropColumn('van_id');
        });
    }
};
