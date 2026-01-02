<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_scheme_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_scheme_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['discount_scheme_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_scheme_products');
    }
};
