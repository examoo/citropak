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
            // Add foreign key columns if they don't exist
            if (!Schema::hasColumn('products', 'brand_id')) {
                $table->foreignId('brand_id')->nullable()->after('name')->constrained('brands')->nullOnDelete();
            }
            if (!Schema::hasColumn('products', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('brand_id')->constrained('product_categories')->nullOnDelete();
            }
            if (!Schema::hasColumn('products', 'type_id')) {
                $table->foreignId('type_id')->nullable()->after('category_id')->constrained('product_types')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['type_id']);
            $table->dropColumn(['brand_id', 'category_id', 'type_id']);
        });
    }
};
