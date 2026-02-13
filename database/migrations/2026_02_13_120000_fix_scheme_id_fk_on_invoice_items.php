<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove scheme_id FK constraint so it accepts IDs from either table.
     */
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['scheme_id']);
        });
    }

    /**
     * Reverse: restore original FK to schemes table.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreign('scheme_id')
                  ->references('id')
                  ->on('schemes')
                  ->onDelete('set null');
        });
    }
};
