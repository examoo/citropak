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
        // First drop the foreign key constraint
        Schema::table('chillers', function (Blueprint $table) {
            $table->dropForeign(['distribution_id']);
        });
        
        // Make column nullable
        Schema::table('chillers', function (Blueprint $table) {
            $table->unsignedBigInteger('distribution_id')->nullable()->change();
        });
        
        // Re-add foreign key with nullOnDelete
        Schema::table('chillers', function (Blueprint $table) {
            $table->foreign('distribution_id')->references('id')->on('distributions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chillers', function (Blueprint $table) {
            $table->dropForeign(['distribution_id']);
            $table->foreignId('distribution_id')->nullable(false)->change();
            $table->foreign('distribution_id')->references('id')->on('distributions')->cascadeOnDelete();
        });
    }
};
