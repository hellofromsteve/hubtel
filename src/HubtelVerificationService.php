<?php

namespace HelloFromSteve\Hubtel;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * Verifies a payout account before sending money to it: bank account name lookup and mobile money
 * registration/name lookup.
 *
 * Hubtel does not publish a "list bank/momo channels" API, so bank codes (e.g. "300304") and network
 * channels (e.g. "mtn-gh") are application-managed data you maintain yourself from Hubtel's own
 * documentation; this package does not ship or look up a code table.
 *
 * Unlike every other service in this package, a non-2xx HTTP response from these two endpoints does
 * NOT throw. Hubtel sometimes returns a soft-failure message (e.g. "we couldn't fully verify this,
 * save it anyway?") on a non-2xx status, and that message is worth showing to whoever is entering the
 * payout account rather than losing it to a generic exception.
 */
class HubtelVerificationService
{
    protected string $apiKey;
    protected string $apiSecret;

    public function __construct()
    {
        $this->apiKey = (string) config('hubtel.verification.api_key');
        $this->apiSecret = (string) config('hubtel.verification.api_secret');
    }

    protected function request(): PendingRequest
    {
        return Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->asJson()
            ->acceptJson()
            ->timeout((int) config('hubtel.verification.timeout', 45));
    }

    /**
     * Verifies a bank account number and returns the registered account holder's name.
     *
     * @param string $bankCode Hubtel's own bank code, e.g. "300304".
     * @param string $accountNumber The bank account number to verify.
     * @return array The raw Hubtel response (message, responseCode, data.name), plus two convenience
     *   keys: 'success' (true only when responseCode is "0000" AND data.name is non-empty; Hubtel has
     *   been observed to report "0000" with no name populated, which is still a failure) and
     *   'accountName' (data.name when successful, null otherwise).
     */
    public function verifyBankAccount(string $bankCode, string $accountNumber): array
    {
        if (trim($bankCode) === '') {
            throw new InvalidArgumentException('A bank code is required.');
        }

        if (trim($accountNumber) === '') {
            throw new InvalidArgumentException('An account number is required.');
        }

        $url = $this->baseUrl().'/bank/verify/'.rawurlencode($bankCode).'/'.rawurlencode($accountNumber);

        return $this->sendVerificationRequest($url);
    }

    /**
     * Verifies a mobile money number is registered on the given network and returns the registered
     * account holder's name. Same response shape and soft-failure behavior as verifyBankAccount():
     * the raw response also carries 'data.isRegistered', 'data.status', and 'data.profile'.
     *
     * @param string $channel Hubtel's own network slug, e.g. "mtn-gh".
     * @param string $customerMsisdn The mobile money number to verify.
     */
    public function verifyMobileMoney(string $channel, string $customerMsisdn): array
    {
        if (trim($channel) === '') {
            throw new InvalidArgumentException('A network channel is required, e.g. "mtn-gh".');
        }

        if (trim($customerMsisdn) === '') {
            throw new InvalidArgumentException('A mobile money number is required.');
        }

        $url = $this->baseUrl().'/mobilemoney/verify';

        return $this->sendVerificationRequest($url, [
            'channel' => $channel,
            'customerMsisdn' => $customerMsisdn,
        ]);
    }

    protected function sendVerificationRequest(string $url, array $query = []): array
    {
        $response = $this->request()->get($url, $query);
        $data = $response->json() ?? [];

        $name = data_get($data, 'data.name');
        $success = ($data['responseCode'] ?? null) === '0000' && filled($name);

        return array_merge($data, [
            'success' => $success,
            'accountName' => $success ? $name : null,
        ]);
    }

    protected function baseUrl(): string
    {
        $accountNumber = trim((string) config('hubtel.verification.collection_account_number'));
        if ($accountNumber === '') {
            throw new InvalidArgumentException('A Hubtel collection account number is required.');
        }

        $base = rtrim((string) config('hubtel.verification.base_url'), '/');

        return $base.'/v2/merchantaccount/merchants/'.rawurlencode($accountNumber);
    }
}
