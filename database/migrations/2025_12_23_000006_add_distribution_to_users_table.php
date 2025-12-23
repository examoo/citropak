<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * USERS TABLE (DISTRIBUTION-SCOPED)
 * 
 * Users with distribution scope.
 * 
 * IMPORTANT:
 * - distribution_id is NULLABLE for Super Admin
 * - Super Admin (distribution_id = NULL) can access all distributions
 * - Regular users are scoped to their distribution
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add distribution_id after id column
            $table->foreignId('distribution_id')
                  ->nullable()                                         // NULL = Super Admin
                  ->after('id')
                  ->constrained('distributions')
                  ->onDelete('cascade');
            
            // Add role column
            $table->string('role')->default('user')->after('email');

            // Index for distribution filtering
            $table->index('distribution_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['distribution_id']);
            $table->dropIndex(['distribution_id']);
            $table->dropColumn(['distribution_id', 'role']);
        });
    }
};
