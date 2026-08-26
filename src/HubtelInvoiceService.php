<?php

namespace HelloFromSteve\Hubtel;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class HubtelInvoiceService
{
    protected string $apiId;
    protected string $apiKey;

    public function __construct()
    {
        $this->apiId = (string) config('hubtel.invoicing.api_id');
        $this->apiKey = (string) config('hubtel.invoicing.api_key');
    }

    protected function request(): PendingRequest
    {
        return Http::withBasicAuth($this->apiId, $this->apiKey)
            ->asJson()
            ->acceptJson()
            ->timeout((int) config('hubtel.timeout', 45));
    }

    /** Issue a one-time simple invoice. */
    public function create(array $payload): array
    {
        return $this->sendRequest('post', $this->invoiceUrl('simple'), $this->withCallback($payload));
    }

    /** Issue a repeating invoice. */
    public function repeat(array $payload): array
    {
        return $this->sendRequest('post', $this->invoiceUrl('repeat'), $this->withCallback($payload));
    }

    /** Check an invoice's authoritative payment status. */
    public function checkStatus(string $invoiceId): array
    {
        if (trim($invoiceId) === '') {
            throw new InvalidArgumentException('An invoice ID is required.');
        }

        return $this->sendRequest(
            'get',
            $this->invoiceUrl(rawurlencode($invoiceId).'/status-check')
        );
    }

    protected function sendRequest(string $method, string $url, array $data = []): array
    {
        $response = $this->request()->{strtolower($method)}($url, $data);
        $response->throwIfClientError()->throwIfServerError();

        return $response->json();
    }

    private function invoiceUrl(string $path): string
    {
        $accountNumber = trim((string) config('hubtel.invoicing.collection_account_number'));
        if ($accountNumber === '') {
            throw new InvalidArgumentException('A Hubtel collection account number is required.');
        }

        return rtrim((string) config('hubtel.invoicing.base_url'), '/')
            .'/api/v2.0/invoice/'.rawurlencode($accountNumber).'/'.$path;
    }

    private function withCallback(array $payload): array
    {
        if (! array_key_exists('callbackUrl', $payload)) {
            $payload['callbackUrl'] = config('hubtel.invoicing.callback_url');
        }

        return $payload;
    }
}
