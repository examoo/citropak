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
        Schema::table('chillers', function (Blueprint $table) {
            $table->foreignId('distribution_id')->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->after('status')->constrained()->nullOnDelete();
        });

        Schema::table('shelves', function (Blueprint $table) {
            $table->foreignId('distribution_id')->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->after('status')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chillers', function (Blueprint $table) {
            $table->dropForeign(['distribution_id']);
            $table->dropColumn(['distribution_id', 'customer_id']);
        });

        Schema::table('shelves', function (Blueprint $table) {
            $table->dropForeign(['distribution_id']);
            $table->dropColumn(['distribution_id', 'customer_id']);
        });
    }
};
