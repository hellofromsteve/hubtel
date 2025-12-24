# Laravel Hubtel Package

A simple  Laravel package for integrating Hubtel payments into your Laravel 11 and Above application.

## Installation

```bash
composer require hellofromsteve/hubtel
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=hubtel-config
```

Add your hubtel credentials to your `.env` file:

```env
HUBTEL_API_KEY=************
HUBTEL_API_SECRET=****************
HUBTEL_MERCHANT_ACCOUNT_ID=**************

# Local Testing Callback
LOCAL_HUBTEL_CALLBACK_URL="(can you webhook.site to get a url for local testing)"

#Recommended
HUBTEL_CALLBACK_URL="${APP_URL}/payment/callback"
HUBTEL_RETURN_URL="${APP_URL}/payment/return"
HUBTEL_CANCELLED_URL="${APP_URL}/payment/cancel"

```

### You can set the endpoints in the config after publishing there

## Usage

### Using the Helper Function

```php


// Or use helper function and chain methods directly
hubtel()


$transaction = hubtel()->initialize([
   'totalAmount' => 100,
   'description' => 'payment for service',

]);
```

// These defaults are set in the initialize() method and then merged to your $payload

// The can be overwritten 

```php
 $defaults = [
            'callbackUrl'           => config('hubtel.callback_url'),
            'returnUrl'             => config('hubtel.return_url'),
            'cancellationUrl'       => config('hubtel.cancelled_url'),
            'merchantAccountNumber' => config('hubtel.merchant_account_number'),
            'clientReference'       => (string) \Illuminate\Support\Str::uuid(),
        ];


```

```php



```


```php
// Or pass methods directly

$plans = paystack('getPlans');

$transaction = paystack('initializeTransaction', [
    'email' => 'stephen@solentik.com',
    'amount' => 10000, // amount in the lowest form of currency (pesewas/kobo)
]);
```



## Available Methods

- `initialize(array $finalPayload)` - Initiate a payment
- `status(array $payload)` - Get transaction status


## More Methods
More methods will be updated soon



## License

MIT

