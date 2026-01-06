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
        Schema::table('chiller_movements', function (Blueprint $table) {
            $table->dropForeign(['distribution_id']);
        });
        
        Schema::table('chiller_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('distribution_id')->nullable()->change();
        });
        
        Schema::table('chiller_movements', function (Blueprint $table) {
            $table->foreign('distribution_id')->references('id')->on('distributions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chiller_movements', function (Blueprint $table) {
            $table->dropForeign(['distribution_id']);
            $table->unsignedBigInteger('distribution_id')->nullable(false)->change();
            $table->foreign('distribution_id')->references('id')->on('distributions')->cascadeOnDelete();
        });
    }
};
