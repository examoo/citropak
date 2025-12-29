<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADD FBR FIELDS TO INVOICES TABLE
 * 
 * Stores FBR fiscalization data for each invoice.
 * Includes FBR invoice number, QR code, and sync status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // FBR Invoice Data
            $table->string('fbr_invoice_number', 100)->nullable()->after('notes')
                  ->comment('FBR assigned invoice number');
            $table->string('fbr_pos_id', 50)->nullable()->after('fbr_invoice_number')
                  ->comment('POS ID used for this invoice');
            $table->text('fbr_qr_code')->nullable()->after('fbr_pos_id')
                  ->comment('QR code data/URL for verification');
            $table->json('fbr_response')->nullable()->after('fbr_qr_code')
                  ->comment('Full FBR API response (for debugging)');
            $table->enum('fbr_status', ['pending', 'synced', 'failed', 'not_required'])
                  ->default('not_required')->after('fbr_response')
                  ->comment('FBR sync status');
            $table->timestamp('fbr_synced_at')->nullable()->after('fbr_status')
                  ->comment('When invoice was synced with FBR');
            $table->text('fbr_error_message')->nullable()->after('fbr_synced_at')
                  ->comment('Error message if sync failed');
            
            // Buyer Information (snapshot from customer at invoice time)
            $table->string('buyer_ntn', 20)->nullable()->after('fbr_error_message')
                  ->comment('Buyer NTN (from customer)');
            $table->string('buyer_cnic', 15)->nullable()->after('buyer_ntn')
                  ->comment('Buyer CNIC (from customer)');
            $table->string('buyer_name')->nullable()->after('buyer_cnic')
                  ->comment('Buyer name (from customer)');
            $table->string('buyer_phone', 20)->nullable()->after('buyer_name')
                  ->comment('Buyer phone (from customer)');
            $table->string('buyer_address')->nullable()->after('buyer_phone')
                  ->comment('Buyer address (from customer)');
            
            // Indexes
            $table->index('fbr_invoice_number');
            $table->index('fbr_status');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['fbr_invoice_number']);
            $table->dropIndex(['fbr_status']);
            
            $table->dropColumn([
                'fbr_invoice_number',
                'fbr_pos_id',
                'fbr_qr_code',
                'fbr_response',
                'fbr_status',
                'fbr_synced_at',
                'fbr_error_message',
                'buyer_ntn',
                'buyer_cnic',
                'buyer_name',
                'buyer_phone',
                'buyer_address',
            ]);
        });
    }
};
