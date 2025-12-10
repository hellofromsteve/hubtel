<?php

return [



    /*
    |--------------------------------------------------------------------------
    | Hubtel API Username and Password
    |--------------------------------------------------------------------------
    |  |
    | Here you may specify your Hubtel API credentials. These credentials
    | will be used to authenticate requests made to the Hubtel API.
    | You can set these values in your .env file.
    |
    */
    'api_key' => env('HUBTEL_API_KEY', 'username'),


    'api_secret' => env('HUBTEL_API_SECRET', 'password'),





    /*
    |--------------------------------------------------------------------------
    | Hubtel Merchant Account ID
    |--------------------------------------------------------------------------
    |
    | Here you may specify your Hubtel Merchant Account ID. This ID
    | will be used to identify your merchant account when processing
    | payments through the Hubtel API. You can set this value in your .env file.
    |
    */
    'merchant_account_id' => env('HUBTEL_MERCHANT_ACCOUNT_ID', 'your-merchant-account-id'),




    /*
    |--------------------------------------------------------------------------
    | Hubtel Payment URLs
    |--------------------------------------------------------------------------
    |
    | Here you may specify the URLs for handling payment callbacks,
    | returns, and cancellations. These URLs will be used by Hubtel
    | to redirect users after payment actions. You can set these values
    | in your .env file.
    |
    */
    'callback_url' => env('HUBTEL_CALLBACK_URL', 'https://your-callback-url.com'),


    'return_url' => env('HUBTEL_RETURN_URL', 'https://your-return-url.com'),


    'cancelled_url' => env('HUBTEL_CANCELLED_URL', 'https://your-cancelled-url.com'),




    /*|--------------------------------------------------------------------------
    | Hubtel API Endpoints
    |--------------------------------------------------------------------------
    | Here you may specify the API endpoints for various Hubtel
    | services. These endpoints will be used to make requests to
    | the Hubtel API. You can customize these endpoints if needed.
    |*/
   'endpoints' => [
        'initiate' => env('HUBTEL_INITIATE_URL', 'https://payproxyapi.hubtel.com/items/initiate'),
        'status'   => env('HUBTEL_STATUS_URL', 'https://api-txnstatus.hubtel.com/transactions'),
    ],
    





]; 