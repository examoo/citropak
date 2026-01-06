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
        Schema::table('shelves', function (Blueprint $table) {
            $table->string('shelf_code')->nullable()->after('id');
            $table->decimal('rent_amount', 10, 2)->nullable()->after('customer_id')->comment('Monthly rent');
            $table->integer('contract_months')->nullable()->after('rent_amount');
            $table->date('start_date')->nullable()->after('contract_months');
            $table->date('end_date')->nullable()->after('start_date');
            $table->decimal('incentive_amount', 10, 2)->nullable()->after('end_date');
            $table->foreignId('order_booker_id')->nullable()->after('incentive_amount')->constrained()->nullOnDelete();
            
            // Make distribution_id nullable
            $table->unsignedBigInteger('distribution_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shelves', function (Blueprint $table) {
            $table->dropForeign(['order_booker_id']);
            $table->dropColumn([
                'shelf_code',
                'rent_amount',
                'contract_months',
                'start_date',
                'end_date',
                'incentive_amount',
                'order_booker_id'
            ]);
        });
    }
};
