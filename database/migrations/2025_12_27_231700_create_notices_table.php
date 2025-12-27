<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('distribution_id')->nullable();
            $table->string('title');
            $table->text('content');
            $table->enum('type', ['info', 'warning', 'success', 'danger'])->default('info');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('distribution_id')->references('id')->on('distributions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
