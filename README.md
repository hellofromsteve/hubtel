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
HUBTEL_API_KEY=your_api_username_here
HUBTEL_API_SECRET=your_api_password_here
HUBTEL_MERCHANT_ACCOUNT_ID=your_hubtel_merchand_pos_id_here
HUBTEL_CALLBACK_URL=your_callback_url_here
HUBTEL_RETURN_URL=your_return_url_here
HUBTEL_CANCELLED_URL=your_cancelled_url_here
HUBTEL_INITIATE_URL=optional_here(initiate transaction)
HUBTEL_STATUS_URL=optional_here_(check transaction status)

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

