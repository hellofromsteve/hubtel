<?php

/*
 * Every Hubtel feature revolves around the same two account numbers for a given merchant: the
 * Collection account (Payments, Invoicing, Verification, and the Collection side of Transfers) and
 * the Disbursement account (the Transfers payout side). Set them once here and every feature below
 * picks them up automatically; a feature can still set its own value to override this shared default.
 *
 * HUBTEL_MERCHANT_ACCOUNT_NUMBER and HUBTEL_MERCHANT_ACCOUNT_ID remain supported fallbacks for
 * applications using an older environment name.
 */
$collectionAccountNumber = env(
    'HUBTEL_COLLECTION_ACCOUNT_NUMBER',
    env('HUBTEL_MERCHANT_ACCOUNT_NUMBER', env('HUBTEL_MERCHANT_ACCOUNT_ID', 'your-collection-account-number'))
);
$disbursementAccountNumber = env('HUBTEL_DISBURSEMENT_ACCOUNT_NUMBER', 'your-disbursement-account-number');

return [
    /* Hosted-payment Basic authentication credentials. */
    'api_key' => env('HUBTEL_API_KEY', 'username'),
    'api_secret' => env('HUBTEL_API_SECRET', 'password'),

    'collection_account_number' => $collectionAccountNumber,
    'disbursement_account_number' => $disbursementAccountNumber,

    /* Deprecated alias for collection_account_number, kept so existing code reading this key still works. */
    'merchant_account_number' => $collectionAccountNumber,

    'callback_url' => env('HUBTEL_CALLBACK_URL', 'https://your-callback-url.com'),
    'local_callback_url' => env('LOCAL_HUBTEL_CALLBACK_URL'),
    'return_url' => env('HUBTEL_RETURN_URL', 'https://your-return-url.com'),
    'cancelled_url' => env('HUBTEL_CANCELLED_URL', 'https://your-cancelled-url.com'),
    'timeout' => (int) env('HUBTEL_TIMEOUT', 45),

    'endpoints' => [
        'initiate' => env('HUBTEL_INITIATE_URL', 'https://payproxyapi.hubtel.com/items/initiate'),
        'status' => env('HUBTEL_STATUS_URL', 'https://api-txnstatus.hubtel.com/transactions'),
    ],

    /* Hubtel Invoicing API uses separate credentials, and the shared Collection account above. */
    'invoicing' => [
        'api_id' => env('HUBTEL_INVOICE_API_ID'),
        'api_key' => env('HUBTEL_INVOICE_API_KEY'),
        'collection_account_number' => $collectionAccountNumber,
        'callback_url' => env('HUBTEL_INVOICE_CALLBACK_URL'),
        'base_url' => env('HUBTEL_INVOICE_URL', 'https://invoicing.hubtel.com'),
    ],

    /*
     * Hubtel's disbursement APIs (Send Money, Send-to-Bank, balance transfers) use separate
     * credentials and the shared account numbers above. There is no sandbox for any of these
     * endpoints; every call moves real money or queries a real account, in production, the moment
     * it's called.
     */
    'transfers' => [
        'api_key' => env('HUBTEL_TRANSFER_API_KEY'),
        'api_secret' => env('HUBTEL_TRANSFER_API_SECRET'),
        'collection_account_number' => env('HUBTEL_TRANSFER_COLLECTION_ACCOUNT_NUMBER', $collectionAccountNumber),
        'disbursement_account_number' => $disbursementAccountNumber,
        'timeout' => (int) env('HUBTEL_TRANSFER_TIMEOUT', 45),

        'endpoints' => [
            'send' => env('HUBTEL_SEND_MONEY_URL', 'https://smp.hubtel.com/api/merchants'),
            'send_status' => env('HUBTEL_SEND_STATUS_URL', 'https://smrsc.hubtel.com/api/merchants'),
            'inter_transfers' => env('HUBTEL_INTER_TRANSFERS_URL', 'https://trnf.hubtel.com/api/inter-transfers'),
        ],
    ],

    /*
     * Hubtel's account-verification API (bank account and mobile money name lookup) uses separate
     * credentials and the shared Collection account above.
     */
    'verification' => [
        'api_key' => env('HUBTEL_VERIFICATION_API_KEY'),
        'api_secret' => env('HUBTEL_VERIFICATION_API_SECRET'),
        'collection_account_number' => env('HUBTEL_VERIFICATION_COLLECTION_ACCOUNT_NUMBER', $collectionAccountNumber),
        'timeout' => (int) env('HUBTEL_VERIFICATION_TIMEOUT', 45),
        'base_url' => env('HUBTEL_VERIFICATION_URL', 'https://rnv.hubtel.com'),
    ],
];
