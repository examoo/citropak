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
            // Add fed_percent column if not exists
            if (!Schema::hasColumn('products', 'fed_percent')) {
                $table->decimal('fed_percent', 8, 2)->nullable()->default(0)->after('fed_sales_tax');
            }
            
            // Rename retailer_margin to retail_margin if it exists
            if (Schema::hasColumn('products', 'retailer_margin') && !Schema::hasColumn('products', 'retail_margin')) {
                $table->renameColumn('retailer_margin', 'retail_margin');
            } elseif (!Schema::hasColumn('products', 'retail_margin') && !Schema::hasColumn('products', 'retailer_margin')) {
                $table->decimal('retail_margin', 8, 2)->nullable()->default(0)->after('fed_percent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'fed_percent')) {
                $table->dropColumn('fed_percent');
            }
            
            if (Schema::hasColumn('products', 'retail_margin') && !Schema::hasColumn('products', 'retailer_margin')) {
                $table->renameColumn('retail_margin', 'retailer_margin');
            }
        });
    }
};
