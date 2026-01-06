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
            $table->string('chiller_code')->after('distribution_id')->nullable();
            $table->foreignId('chiller_type_id')->nullable()->after('chiller_code')->constrained()->nullOnDelete();
            $table->foreignId('order_booker_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            
            // Index for faster lookups
            $table->index(['distribution_id', 'chiller_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chillers', function (Blueprint $table) {
            $table->dropIndex(['distribution_id', 'chiller_code']);
            $table->dropForeign(['chiller_type_id']);
            $table->dropForeign(['order_booker_id']);
            $table->dropColumn(['chiller_code', 'chiller_type_id', 'order_booker_id']);
        });
    }
};
