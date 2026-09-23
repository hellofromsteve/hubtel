# Laravel Hubtel

Hubtel hosted payments, transaction status, invoicing, transfers, and account verification for Laravel 11, 12, and 13.

## Requirements

- PHP 8.1+
- Laravel 11, 12, or 13

## Installation

```bash
composer require hellofromsteve/hubtel
php artisan vendor:publish --tag=hubtel-config
```

## Configuration

Every Hubtel feature revolves around the same two account numbers for a given merchant: the **Collection** account (Payments, Invoicing, Verification, and the Collection side of Transfers) and the **Disbursement** account (the Transfers payout side). Set them once and every feature picks them up automatically:

```dotenv
HUBTEL_API_KEY=your-application-key
HUBTEL_API_SECRET=your-application-secret
HUBTEL_COLLECTION_ACCOUNT_NUMBER=your-collection-account-number
HUBTEL_DISBURSEMENT_ACCOUNT_NUMBER=your-disbursement-account-number
HUBTEL_CALLBACK_URL=https://api.example.com/hubtel/callback
HUBTEL_RETURN_URL=https://example.com/payment-complete
HUBTEL_CANCELLED_URL=https://example.com/payment-cancelled

HUBTEL_INVOICE_API_ID=your-invoicing-api-id
HUBTEL_INVOICE_API_KEY=your-invoicing-api-key
HUBTEL_INVOICE_CALLBACK_URL=https://api.example.com/hubtel/invoices/callback

HUBTEL_TRANSFER_API_KEY=your-disbursement-api-key
HUBTEL_TRANSFER_API_SECRET=your-disbursement-api-secret

HUBTEL_VERIFICATION_API_KEY=your-verification-api-key
HUBTEL_VERIFICATION_API_SECRET=your-verification-api-secret
```

Each feature can still set its own `collection_account_number` (`HUBTEL_TRANSFER_COLLECTION_ACCOUNT_NUMBER`, `HUBTEL_VERIFICATION_COLLECTION_ACCOUNT_NUMBER`) to override the shared value, for the rare merchant whose account numbers genuinely differ per product. Each feature keeps its own API credentials regardless, since Hubtel issues separate keys per product.

::: tip Migrating from an older config
`HUBTEL_MERCHANT_ACCOUNT_NUMBER` still works; it seeds the same shared value as `HUBTEL_COLLECTION_ACCOUNT_NUMBER` and can be renamed at your convenience. Nothing breaks if you keep using it.
:::

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

## Transfers

**There is no sandbox for any transfer endpoint.** Every call below moves real money or queries a real account, in production, the moment it's called.

```php
$response = hubtel()->transfers()->sendMoney([
    'recipientName' => 'Jane Doe',
    'recipientMsisdn' => '233249111411',
    'channel' => 'mtn-gh',
    'amount' => 150,
    'primaryCallbackUrl' => 'https://api.example.com/webhooks/hubtel-send-money',
    'description' => 'October payroll',
    'clientReference' => 'PAY-2026-10-001',
]);

if ($response['responseCode'] === '0000') {
    // succeeded
} elseif ($response['responseCode'] === '0001') {
    // accepted but not yet final; wait for the callback, or poll sendStatus()
} else {
    // failed; $response['data']['description'] has Hubtel's reason
}
```

`hubtel()->transfers()` also provides:

- `sendToBank(string $bankCode, array $payload)`: same `responseCode` semantics as `sendMoney()`.
- `sendStatus(string $clientReference)`: the mandatory fallback for a missed callback (requires your outbound IP to be whitelisted with Hubtel).
- `collectionBalance()` / `disbursementBalance()`.
- `transferToDisbursement(array $payload)`: moves funds from Collection into Disbursement so Send Money/Send-to-Bank has funds to pay out from. `responseCode` "0000" **or** "200" both mean success (Hubtel's own inconsistency on this endpoint).
- `balanceTransferStatus(string $clientReference)`.

**`clientReference` is not a retry key.** Every mutating call (`sendMoney`, `sendToBank`, `transferToDisbursement`) takes a `clientReference` (max 36 characters) that Hubtel treats as a permanent, never-reusable value across this account, for all time. Resubmitting the same reference after a timeout does not safely retry the operation; Hubtel rejects it outright. Generate a fresh reference for every attempt and use `sendStatus()`/`balanceTransferStatus()` to find out what actually happened to a request whose outcome is unknown.

These methods return Hubtel's raw response array; inspect `responseCode` (and `data.description` on failure) yourself, the same way `checkStatus()` and `invoices()->checkStatus()` do elsewhere in this package. An HTTP-level failure (4xx/5xx) still throws `Illuminate\Http\Client\RequestException`.

## Verification

Verify a payout account before sending money to it:

```php
$result = hubtel()->verification()->verifyBankAccount('300304', '1234567890');

if ($result['success']) {
    $accountName = $result['accountName'];
} else {
    // $result['message'] may still be a useful, user-facing reason
}

$momoResult = hubtel()->verification()->verifyMobileMoney('mtn-gh', '233249111411');
```

Hubtel does not publish a "list bank/momo channels" API, so bank codes (e.g. `"300304"`) and network channels (e.g. `"mtn-gh"`) are application-managed data you maintain yourself from Hubtel's own documentation.

**Unlike every other method in this package, a non-2xx HTTP response here does not throw.** Both methods return `['success' => bool, 'accountName' => ?string, 'message' => ?string, ...]` (merged with Hubtel's raw response) instead; Hubtel sometimes returns a soft-failure message (e.g. "we couldn't fully verify this, save it anyway?") on a non-2xx status, and that's worth showing to whoever is entering the payout account rather than losing to a generic exception. `success` is only `true` when `responseCode` is `"0000"` **and** a name was actually returned; Hubtel has been observed to report `"0000"` with a blank name, which still counts as a failure here.

See `hubtel-docs` in the repository for complete Laravel and .NET documentation.

## License

MIT
