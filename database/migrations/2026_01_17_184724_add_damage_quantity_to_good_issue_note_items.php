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
        Schema::table('good_issue_note_items', function (Blueprint $table) {
            $table->integer('damage_quantity')->default(0)->after('returned_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('good_issue_note_items', function (Blueprint $table) {
            $table->dropColumn('damage_quantity');
        });
    }
};
