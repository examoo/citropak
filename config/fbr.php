<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FBR API URLs
    |--------------------------------------------------------------------------
    |
    | These are the base URLs for FBR POS integration API.
    | Sandbox is used for testing, Production for live invoices.
    |
    */
    'sandbox_url' => env('FBR_SANDBOX_URL', 'https://sandbox.fbr.gov.pk/ims-api'),
    'production_url' => env('FBR_PRODUCTION_URL', 'https://ims.fbr.gov.pk/ims-api'),

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    */
    'timeout' => env('FBR_TIMEOUT', 30),
    'retry_attempts' => env('FBR_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('FBR_RETRY_DELAY', 1000), // milliseconds

    /*
    |--------------------------------------------------------------------------
    | Invoice Types
    |--------------------------------------------------------------------------
    |
    | Mapping of internal invoice types to FBR invoice type codes.
    |
    */
    'invoice_types' => [
        'sale' => 1,        // Sale Invoice
        'return' => 2,      // Return Invoice
        'damage' => 3,      // Damage Invoice
        'shelf_rent' => 4,  // Other
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Modes
    |--------------------------------------------------------------------------
    |
    | FBR payment mode codes.
    |
    */
    'payment_modes' => [
        'cash' => 1,
        'card' => 2,
        'credit' => 3,
        'cheque' => 4,
        'other' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Unit of Measure Codes
    |--------------------------------------------------------------------------
    |
    | Standard UOM codes as per FBR specifications.
    |
    */
    'uom_codes' => [
        'PCS' => 'PCS',    // Pieces
        'CTN' => 'CTN',    // Cartons
        'KG' => 'KGS',     // Kilograms
        'LTR' => 'LTR',    // Liters
        'MTR' => 'MTR',    // Meters
        'BOX' => 'BOX',    // Box
        'PKT' => 'PKT',    // Packet
        'DOZ' => 'DOZ',    // Dozen
        'SET' => 'SET',    // Set
        'BTL' => 'BTL',    // Bottle
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax Types
    |--------------------------------------------------------------------------
    |
    | FBR tax type identifiers.
    |
    */
    'tax_types' => [
        'sales_tax' => 1,      // Sales Tax
        'fed' => 2,            // Federal Excise Duty
        'extra_tax' => 3,      // Extra Tax / WHT
        'advance_tax' => 4,    // Advance Tax
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Values
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'uom_code' => 'PCS',
        'country_code' => 'PK',
        'currency_code' => 'PKR',
    ],
];
