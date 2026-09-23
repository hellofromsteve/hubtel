<?php

namespace HelloFromSteve\Hubtel;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

class HubtelService
{
    protected string $apiKey;
    protected string $apiSecret;

    public function __construct()
    {
        $this->apiKey = (string) config('hubtel.api_key');
        $this->apiSecret = (string) config('hubtel.api_secret');
    }

    protected function request(): PendingRequest
    {
        return Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->asJson()
            ->acceptJson()
            ->timeout((int) config('hubtel.timeout', 45));
    }

    protected function sendRequest(string $method, string $url, array $data = []): array
    {
        $response = $this->request()->{strtolower($method)}($url, $data);
        $response->throwIfClientError()->throwIfServerError();

        return $response->json();
    }

    /**
     * Reads the merchant's Collection account number, falling back to the deprecated
     * 'merchant_account_number' key for applications that published this config before it existed.
     */
    protected function collectionAccountNumber(): string
    {
        return (string) (config('hubtel.collection_account_number') ?: config('hubtel.merchant_account_number'));
    }

    /** Initiate a Hubtel hosted-checkout payment. */
    public function initialize(array $payload = []): array
    {
        $callbackUrl = app()->environment('local') && config('hubtel.local_callback_url')
            ? config('hubtel.local_callback_url')
            : config('hubtel.callback_url');

        return $this->sendRequest('post', (string) config('hubtel.endpoints.initiate'), array_merge([
            'callbackUrl' => $callbackUrl,
            'returnUrl' => config('hubtel.return_url'),
            'cancellationUrl' => config('hubtel.cancelled_url'),
            'merchantAccountNumber' => $this->collectionAccountNumber(),
            'clientReference' => (string) Str::uuid(),
        ], $payload));
    }

    /** Check hosted-payment status by Hubtel transaction ID. */
    public function checkStatus(string $hubtelTransactionId): array
    {
        if (trim($hubtelTransactionId) === '') {
            throw new InvalidArgumentException('A Hubtel transaction ID is required.');
        }

        return $this->sendStatusRequest(['hubtelTransactionId' => $hubtelTransactionId]);
    }

    /** Check hosted-payment status by the merchant's client reference. */
    public function checkStatusByClientReference(string $clientReference): array
    {
        if (trim($clientReference) === '') {
            throw new InvalidArgumentException('A client reference is required.');
        }

        return $this->sendStatusRequest(['clientReference' => $clientReference]);
    }

    /** Access Hubtel's separate Invoicing API. */
    public function invoices(): HubtelInvoiceService
    {
        return app(HubtelInvoiceService::class);
    }

    /** Access Hubtel's separate Transfers API (Send Money, Send-to-Bank, balance transfers). */
    public function transfers(): HubtelTransferService
    {
        return app(HubtelTransferService::class);
    }

    /** Access Hubtel's separate account-verification API (bank account and mobile money name lookup). */
    public function verification(): HubtelVerificationService
    {
        return app(HubtelVerificationService::class);
    }

    private function sendStatusRequest(array $query): array
    {
        $accountNumber = trim($this->collectionAccountNumber());
        if ($accountNumber === '') {
            throw new InvalidArgumentException('A Hubtel collection account number is required.');
        }

        $baseUrl = rtrim((string) config('hubtel.endpoints.status'), '/');
        $url = $baseUrl.'/'.rawurlencode($accountNumber).'/status';

        return $this->sendRequest('get', $url, $query);
    }
}
