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
        Schema::table('shop_visits', function (Blueprint $table) {
            $table->renameColumn('visited_at', 'check_in_at');
        });
        
        Schema::table('shop_visits', function (Blueprint $table) {
            $table->dateTime('check_out_at')->nullable()->after('check_in_at');
            $table->decimal('check_out_latitude', 10, 8)->nullable()->after('longitude');
            $table->decimal('check_out_longitude', 11, 8)->nullable()->after('check_out_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_visits', function (Blueprint $table) {
            $table->renameColumn('check_in_at', 'visited_at');
            $table->dropColumn(['check_out_at', 'check_out_latitude', 'check_out_longitude']);
        });
    }
};
