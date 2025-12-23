<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CATEGORIES TABLE (GLOBAL)
 * 
 * Hierarchical product categories shared across ALL distributions.
 * NO distribution_id - managed only by Super Admin.
 * Self-referencing parent_id for nested categories.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')                             // Self-reference for hierarchy
                  ->nullable()
                  ->constrained('categories')
                  ->onDelete('set null');
            $table->timestamps();

            // Index for parent lookups
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
