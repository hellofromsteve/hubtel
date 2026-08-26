# Laravel Hubtel

Hubtel hosted payments, transaction status, and invoicing for Laravel 11, 12, and 13.

## Requirements

- PHP 8.1+
- Laravel 11, 12, or 13

## Installation

```bash
composer require hellofromsteve/hubtel
php artisan vendor:publish --tag=hubtel-config
```

## Configuration

```dotenv
HUBTEL_API_KEY=your-application-key
HUBTEL_API_SECRET=your-application-secret
HUBTEL_MERCHANT_ACCOUNT_NUMBER=your-merchant-account-number
HUBTEL_CALLBACK_URL=https://api.example.com/hubtel/callback
HUBTEL_RETURN_URL=https://example.com/payment-complete
HUBTEL_CANCELLED_URL=https://example.com/payment-cancelled

HUBTEL_INVOICE_API_ID=your-invoicing-api-id
HUBTEL_INVOICE_API_KEY=your-invoicing-api-key
HUBTEL_COLLECTION_ACCOUNT_NUMBER=11684
HUBTEL_INVOICE_CALLBACK_URL=https://api.example.com/hubtel/invoices/callback
```

## Hosted payments

```php
$response = hubtel()->initialize([
    'totalAmount' => 100,
    'description' => 'Order ORD-123',
    'clientReference' => 'ORD-123',
]);
```

## Transaction status

```php
$byTransactionId = hubtel()->checkStatus('hubtel-transaction-id');
$byReference = hubtel()->checkStatusByClientReference('ORD-123');
```

> **IP whitelist required:** Status requests must originate from a stable public outbound IP whitelisted by Hubtel. Provide that IP to your Hubtel Retail Engineer before using transaction-status APIs.

## Invoicing

```php
$invoice = hubtel()->invoices()->create($payload);
$repeatInvoice = hubtel()->invoices()->repeat($repeatPayload);
$status = hubtel()->invoices()->checkStatus($invoiceId);
```

Invoicing uses separate API credentials and a collection account number. Invoice status checks have the same outbound-IP whitelist requirement.

See `hubtel-docs` in the repository for complete Laravel and .NET documentation.

## License

MIT
