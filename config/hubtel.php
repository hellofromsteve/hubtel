<?php

return [
    /* Hosted-payment Basic authentication credentials. */
    'api_key' => env('HUBTEL_API_KEY', 'username'),
    'api_secret' => env('HUBTEL_API_SECRET', 'password'),

    /*
     * HUBTEL_MERCHANT_ACCOUNT_ID remains a fallback for applications using
     * versions of this package that exposed the older environment name.
     */
    'merchant_account_number' => env(
        'HUBTEL_MERCHANT_ACCOUNT_NUMBER',
        env('HUBTEL_MERCHANT_ACCOUNT_ID', 'your-merchant-account-number')
    ),

    'callback_url' => env('HUBTEL_CALLBACK_URL', 'https://your-callback-url.com'),
    'local_callback_url' => env('LOCAL_HUBTEL_CALLBACK_URL'),
    'return_url' => env('HUBTEL_RETURN_URL', 'https://your-return-url.com'),
    'cancelled_url' => env('HUBTEL_CANCELLED_URL', 'https://your-cancelled-url.com'),
    'timeout' => (int) env('HUBTEL_TIMEOUT', 45),

    'endpoints' => [
        'initiate' => env('HUBTEL_INITIATE_URL', 'https://payproxyapi.hubtel.com/items/initiate'),
        'status' => env('HUBTEL_STATUS_URL', 'https://api-txnstatus.hubtel.com/transactions'),
    ],

    /* Hubtel Invoicing API uses separate credentials and a collection account. */
    'invoicing' => [
        'api_id' => env('HUBTEL_INVOICE_API_ID'),
        'api_key' => env('HUBTEL_INVOICE_API_KEY'),
        'collection_account_number' => env('HUBTEL_COLLECTION_ACCOUNT_NUMBER'),
        'callback_url' => env('HUBTEL_INVOICE_CALLBACK_URL'),
        'base_url' => env('HUBTEL_INVOICE_URL', 'https://invoicing.hubtel.com'),
    ],
];
