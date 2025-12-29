<?php

namespace App\Services;

use App\Models\Distribution;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FBR INTEGRATION SERVICE
 * 
 * Handles all communication with FBR POS API for invoice fiscalization.
 * Supports different NTN/STN credentials per distribution.
 */
class FbrService
{
    /**
     * Sync an invoice with FBR.
     */
    public function syncInvoice(Invoice $invoice): array
    {
        $distribution = $invoice->distribution;
        
        if (!$distribution || !$distribution->isFbrEnabled()) {
            return [
                'success' => false,
                'message' => 'FBR integration is not enabled for this distribution.',
            ];
        }

        try {
            // Mark as pending
            $invoice->markFbrPending();
            
            // Build payload
            $payload = $this->buildInvoicePayload($invoice);
            
            // Send to FBR
            $response = $this->sendRequest($distribution, '/api/invoice', $payload);
            
            if ($response['success']) {
                // Update invoice with FBR data
                $invoice->markFbrSynced(
                    $response['data']['InvoiceNumber'] ?? $response['data']['fbr_invoice_number'] ?? '',
                    $response['data']['QRCode'] ?? $response['data']['qr_code'] ?? null,
                    $response['data']
                );
                
                return [
                    'success' => true,
                    'message' => 'Invoice synced successfully with FBR.',
                    'fbr_invoice_number' => $response['data']['InvoiceNumber'] ?? '',
                    'qr_code' => $response['data']['QRCode'] ?? null,
                ];
            } else {
                $errorMessage = $response['message'] ?? 'Unknown error from FBR API';
                $invoice->markFbrFailed($errorMessage, $response['data'] ?? null);
                
                return [
                    'success' => false,
                    'message' => $errorMessage,
                ];
            }
        } catch (\Exception $e) {
            Log::error('FBR Sync Error', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $invoice->markFbrFailed($e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to sync with FBR: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build invoice payload for FBR API.
     */
    public function buildInvoicePayload(Invoice $invoice): array
    {
        $distribution = $invoice->distribution;
        $customer = $invoice->customer;
        
        // Build items array
        $items = [];
        foreach ($invoice->items as $item) {
            $items[] = $this->buildItemPayload($item);
        }
        
        return [
            // Invoice Header
            'InvoiceNumber' => $invoice->invoice_number,
            'POSID' => $distribution->pos_id,
            'USIN' => $invoice->invoice_number, // Unique Sale Invoice Number
            'DateTime' => $invoice->invoice_date->format('Y-m-d H:i:s'),
            'BuyerNTN' => $invoice->buyer_ntn ?? $customer?->ntn ?? '',
            'BuyerCNIC' => $invoice->buyer_cnic ?? $customer?->cnic ?? '',
            'BuyerName' => $invoice->buyer_name ?? $customer?->shop_name ?? $customer?->owner_name ?? '',
            'BuyerPhoneNumber' => $invoice->buyer_phone ?? $customer?->mobile_number ?? '',
            'BuyerAddress' => $invoice->buyer_address ?? $customer?->address ?? '',
            
            // Payment Info
            'TotalBillAmount' => round($invoice->total_amount, 2),
            'TotalQuantity' => $invoice->items->sum('total_pieces'),
            'TotalSaleValue' => round($invoice->subtotal, 2),
            'TotalTaxCharged' => round($invoice->tax_amount + $invoice->fed_amount, 2),
            'Discount' => round($invoice->discount_amount, 2),
            'FurtherTax' => 0,
            'PaymentMode' => $invoice->is_credit ? 3 : 1, // 3 = Credit, 1 = Cash
            'RefUSIN' => '', // Reference for returns
            'InvoiceType' => config('fbr.invoice_types.' . $invoice->invoice_type, 1),
            
            // Items
            'Items' => $items,
        ];
    }

    /**
     * Build item payload for FBR API.
     */
    public function buildItemPayload(InvoiceItem $item): array
    {
        $product = $item->product;
        
        return [
            'ItemCode' => $product?->dms_code ?? $product?->sku ?? $item->product_id,
            'ItemName' => $product?->name ?? 'Product',
            'Quantity' => $item->total_pieces,
            'PCTCode' => $item->hs_code ?? $product?->hs_code ?? '',
            'TaxRate' => round($item->tax_percent ?? 0, 2),
            'SaleValue' => round($item->exclusive_amount ?? ($item->price * $item->total_pieces), 2),
            'TotalAmount' => round($item->line_total ?? 0, 2),
            'TaxCharged' => round(($item->tax ?? 0) + ($item->fed_amount ?? 0), 2),
            'Discount' => round($item->scheme_discount ?? 0, 2),
            'FurtherTax' => round($item->extra_tax_amount ?? 0, 2),
            'InvoiceType' => 1, // Sale
            'RefUSIN' => '',
        ];
    }

    /**
     * Send request to FBR API.
     */
    protected function sendRequest(Distribution $distribution, string $endpoint, array $payload): array
    {
        $baseUrl = $distribution->getFbrApiUrl();
        $url = rtrim($baseUrl, '/') . $endpoint;
        
        $username = $distribution->fbr_username_decrypted;
        $password = $distribution->fbr_password_decrypted;
        
        if (!$username || !$password) {
            return [
                'success' => false,
                'message' => 'FBR API credentials are not configured.',
            ];
        }

        try {
            $response = Http::withBasicAuth($username, $password)
                ->timeout(config('fbr.timeout', 30))
                ->retry(config('fbr.retry_attempts', 3), config('fbr.retry_delay', 1000))
                ->post($url, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => $response->body(),
                'status' => $response->status(),
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('FBR API Request Failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Test FBR connection for a distribution.
     */
    public function testConnection(Distribution $distribution): array
    {
        if (!$distribution->isFbrEnabled()) {
            return [
                'success' => false,
                'message' => 'FBR integration is not fully configured. Please ensure POS ID, username, and password are set.',
            ];
        }

        try {
            // Try to connect to the API (endpoint may vary based on FBR docs)
            $baseUrl = $distribution->getFbrApiUrl();
            $username = $distribution->fbr_username_decrypted;
            $password = $distribution->fbr_password_decrypted;
            
            $response = Http::withBasicAuth($username, $password)
                ->timeout(10)
                ->get($baseUrl . '/api/status');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Successfully connected to FBR API.',
                    'environment' => $distribution->fbr_environment,
                ];
            }

            // Even if we get an auth error, it means the server is reachable
            if ($response->status() === 401) {
                return [
                    'success' => false,
                    'message' => 'Connection successful but authentication failed. Please check your credentials.',
                ];
            }

            return [
                'success' => false,
                'message' => 'FBR API returned status: ' . $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to connect to FBR API: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get invoice status from FBR.
     */
    public function getInvoiceStatus(Invoice $invoice): array
    {
        if (!$invoice->fbr_invoice_number) {
            return [
                'success' => false,
                'message' => 'Invoice has not been synced with FBR.',
            ];
        }

        $distribution = $invoice->distribution;
        
        if (!$distribution || !$distribution->isFbrEnabled()) {
            return [
                'success' => false,
                'message' => 'FBR integration is not enabled.',
            ];
        }

        try {
            $baseUrl = $distribution->getFbrApiUrl();
            $username = $distribution->fbr_username_decrypted;
            $password = $distribution->fbr_password_decrypted;
            
            $response = Http::withBasicAuth($username, $password)
                ->timeout(config('fbr.timeout', 30))
                ->get($baseUrl . '/api/invoice/' . $invoice->fbr_invoice_number);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to get invoice status from FBR.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error checking invoice status: ' . $e->getMessage(),
            ];
        }
    }
}
