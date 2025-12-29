<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADD FBR SETTINGS TO DISTRIBUTIONS TABLE
 * 
 * Each distribution can have its own FBR credentials and settings.
 * This allows different NTN/STN numbers per distribution.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributions', function (Blueprint $table) {
            // Business Registration Details
            $table->string('ntn_number', 20)->nullable()->after('status')
                  ->comment('National Tax Number');
            $table->string('strn_number', 20)->nullable()->after('ntn_number')
                  ->comment('Sales Tax Registration Number');
            $table->string('business_name')->nullable()->after('strn_number')
                  ->comment('Registered business name for FBR');
            $table->text('business_address')->nullable()->after('business_name')
                  ->comment('Registered business address');
            $table->string('business_phone', 20)->nullable()->after('business_address')
                  ->comment('Business contact phone');
            $table->string('business_email')->nullable()->after('business_phone')
                  ->comment('Business contact email');
            
            // FBR POS Settings
            $table->string('pos_id', 50)->nullable()->after('business_email')
                  ->comment('FBR assigned POS ID');
            $table->text('fbr_username')->nullable()->after('pos_id')
                  ->comment('FBR API username (encrypted)');
            $table->text('fbr_password')->nullable()->after('fbr_username')
                  ->comment('FBR API password (encrypted)');
            $table->enum('fbr_environment', ['sandbox', 'production'])
                  ->default('sandbox')->after('fbr_password')
                  ->comment('FBR API environment');
            $table->boolean('fbr_enabled')->default(false)->after('fbr_environment')
                  ->comment('Enable/disable FBR integration');
            $table->date('fbr_integration_date')->nullable()->after('fbr_enabled')
                  ->comment('Date when FBR integration was activated');
            
            // Index for FBR-enabled distributions
            $table->index('fbr_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('distributions', function (Blueprint $table) {
            $table->dropIndex(['fbr_enabled']);
            
            $table->dropColumn([
                'ntn_number',
                'strn_number',
                'business_name',
                'business_address',
                'business_phone',
                'business_email',
                'pos_id',
                'fbr_username',
                'fbr_password',
                'fbr_environment',
                'fbr_enabled',
                'fbr_integration_date',
            ]);
        });
    }
};
