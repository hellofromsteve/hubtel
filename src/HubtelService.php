<?php

namespace HelloFromSteve\Hubtel;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;

class HubtelService
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $baseUrl = 'https://payproxyapi.hubtel.com/items/';

    public function __construct()
    {
        // Load from config, but allow setters if needed later
        $this->apiKey = config('hubtel.api_key');
        $this->apiSecret = config('hubtel.api_secret');
    }

    /**
     * Build the base HTTP client with Auth and Headers.
     * Returns a PendingRequest so you can chain methods later.
     */
    protected function request(): PendingRequest
    {
        // Removed ->baseUrl() to allow flexibility per request
        return Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->asJson()
            ->acceptJson()
            ->timeout(45);
    }

    /**
     * The dynamic handler
     * @param string $method (get, post, put, delete)
     * @param string $url (Full URL required)
     * @param array $data
     */
    protected function sendRequest(string $method, string $url, array $data = [])
    {
        $method = strtolower($method);

        // Laravel Http client handles full URLs automatically here
        $response = $this->request()->$method($url, $data);

        if ($response->failed()) {
            return $response->throw(); 
        }

        return $response->json();
    }


  /**
     * Initialize Payment
     * Uses the 'initiate' endpoint from config
     */
    public function initialize(array $payload = [])
    {
        // 1. Fetch URL from config
        $url = config('hubtel.endpoints.initiate');

        // 2. Merge defaults (same as before)
      $callbackUrl = (app()->environment('local') && config('hubtel.local_callback_url'))
        ? config('hubtel.local_callback_url')
        : config('hubtel.callback_url');

        // 3. Merge defaults
        $defaults = [
            'callbackUrl'           => $callbackUrl,
            'returnUrl'             => config('hubtel.return_url'),
            'cancellationUrl'       => config('hubtel.cancelled_url'),
            'merchantAccountNumber' => config('hubtel.merchant_account_id'), 
            'clientReference'       => (string) \Illuminate\Support\Str::uuid(),
        ];

        $finalPayload = array_merge($defaults, $payload);

        // 3. Send Request
        return $this->sendRequest('post', $url, $finalPayload);
    }

    /**
     * Example of a DIFFERENT URL in the same class
     * This checks the status of a transaction
     */
    public function checkStatus(string $hubtelTransactionId)
    {
        // Hubtel Status Check often uses a different domain/structure (e.g., api.hubtel.com)
        // We build the full URL dynamically here.
        $accountId = config('hubtel.merchant_account_number');
        $baseUrl   = config('hubtel.endpoints.status'); // e.g., https://api.hubtel.com/v1/...
        
        // Full URL: https://api.hubtel.com/v1/merchantaccount/merchants/{id}/transactions/status
        $url = "{$baseUrl}{$accountId}/transactions/status";

        return $this->sendRequest('get', $url, [
            'hubtelTransactionId' => $hubtelTransactionId
        ]);
    }
}