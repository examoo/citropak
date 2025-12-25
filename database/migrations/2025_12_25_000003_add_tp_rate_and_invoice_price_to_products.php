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
        Schema::table('products', function (Blueprint $table) {
            // Add tp_rate column if not exists
            if (!Schema::hasColumn('products', 'tp_rate')) {
                $table->decimal('tp_rate', 12, 4)->nullable()->default(0)->after('retail_margin');
            }
            
            // Add invoice_price column if not exists
            if (!Schema::hasColumn('products', 'invoice_price')) {
                $table->decimal('invoice_price', 12, 4)->nullable()->default(0)->after('distribution_margin');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'tp_rate')) {
                $table->dropColumn('tp_rate');
            }
            if (Schema::hasColumn('products', 'invoice_price')) {
                $table->dropColumn('invoice_price');
            }
        });
    }
};
