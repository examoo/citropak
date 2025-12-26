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
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'distribution_id')) {
                $table->foreignId('distribution_id')->nullable()->after('id')->constrained('distributions')->nullOnDelete();
            }
            if (!Schema::hasColumn('categories', 'status')) {
                $table->string('status')->default('active')->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'distribution_id')) {
                $table->dropForeign(['distribution_id']);
                $table->dropColumn('distribution_id');
            }
            if (Schema::hasColumn('categories', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
