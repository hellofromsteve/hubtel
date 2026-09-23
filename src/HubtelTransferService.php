<?php

namespace HelloFromSteve\Hubtel;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * Sends money out of a Hubtel merchant account: Send Money (mobile money), Send-to-Bank, and
 * balance transfers between a merchant's own Collection and Disbursement accounts.
 *
 * There is no sandbox for any of these endpoints. Every call moves real money or queries a real
 * account, in production, the moment it's called.
 */
class HubtelTransferService
{
    protected string $apiKey;
    protected string $apiSecret;

    public function __construct()
    {
        $this->apiKey = (string) config('hubtel.transfers.api_key');
        $this->apiSecret = (string) config('hubtel.transfers.api_secret');
    }

    protected function request(): PendingRequest
    {
        return Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->asJson()
            ->acceptJson()
            ->timeout((int) config('hubtel.transfers.timeout', 45));
    }

    protected function sendRequest(string $method, string $url, array $data = []): array
    {
        $response = $this->request()->{strtolower($method)}($url, $data);
        $response->throwIfClientError()->throwIfServerError();

        return $response->json();
    }

    /**
     * Sends money to a mobile money account. No sandbox; this moves real money immediately.
     *
     * @param array $payload ['recipientName', 'recipientMsisdn', 'customerEmail', 'channel', 'amount', 'primaryCallbackUrl', 'description', 'clientReference']
     * `clientReference` must be unique across every call ever made against this account, for all
     * time (max 36 characters); Hubtel rejects a reused reference outright, so it is not a safe
     * key to retry a failed request with.
     * @return array The raw Hubtel response. `responseCode` "0000" means it succeeded, "0001"
     * means accepted but not yet final (the real outcome arrives via the callback or
     * sendStatus()), anything else means it failed with `data.description` as the reason.
     */
    public function sendMoney(array $payload): array
    {
        return $this->sendRequest(
            'post',
            $this->disbursementUrl('send').'/send/mobilemoney',
            $this->buildSendMoneyPayload($payload)
        );
    }

    /**
     * Sends money to a bank account. Same real-money and non-reusable `clientReference` caveats,
     * and the same three-state `responseCode` semantics, as sendMoney().
     *
     * @param string $bankCode Hubtel's own bank code. Sent as part of the URL, not the body.
     * @param array $payload ['amount', 'primaryCallbackUrl', 'description', 'bankAccountNumber', 'bankAccountName', 'recipientPhoneNumber', 'bankName', 'bankBranch', 'bankBranchCode', 'clientReference']
     */
    public function sendToBank(string $bankCode, array $payload): array
    {
        if (trim($bankCode) === '') {
            throw new InvalidArgumentException('A bank code is required.');
        }

        return $this->sendRequest(
            'post',
            $this->disbursementUrl('send').'/send/bank/gh/'.rawurlencode($bankCode),
            $this->buildSendToBankPayload($payload)
        );
    }

    /**
     * Mandatory fallback for when a Send Money/Send-to-Bank callback never arrives (Hubtel does
     * not guarantee callback delivery). Requires the caller's outbound IP to be whitelisted with
     * Hubtel for this account.
     */
    public function sendStatus(string $clientReference): array
    {
        if (trim($clientReference) === '') {
            throw new InvalidArgumentException('A client reference is required.');
        }

        return $this->sendRequest(
            'get',
            $this->disbursementUrl('send_status').'/transactions/status',
            ['clientReference' => $clientReference]
        );
    }

    /** Returns the merchant's Collection account balance. */
    public function collectionBalance(): array
    {
        return $this->sendRequest('get', $this->collectionUrl());
    }

    /** Returns the merchant's Disbursement account balance. */
    public function disbursementBalance(): array
    {
        return $this->sendRequest(
            'get',
            $this->interTransfersUrl().'/prepaid/'.$this->accountSegment('disbursement_account_number')
        );
    }

    /**
     * Moves funds from the merchant's Collection account into its own Disbursement account.
     * Needed before Send Money/Send-to-Bank can succeed if the disbursement balance is
     * insufficient. No sandbox; this moves real money immediately.
     *
     * @param array $payload ['amount', 'description', 'clientReference', 'primaryCallbackUrl']
     * @return array The raw Hubtel response. `responseCode` "0000" or "200" both mean it
     * succeeded (Hubtel's own inconsistency across this endpoint); "0001" is still pending.
     */
    public function transferToDisbursement(array $payload): array
    {
        return $this->sendRequest('post', $this->collectionUrl(), $this->buildBalanceTransferPayload($payload));
    }

    /** Returns the status of a balance transfer. */
    public function balanceTransferStatus(string $clientReference): array
    {
        if (trim($clientReference) === '') {
            throw new InvalidArgumentException('A client reference is required.');
        }

        return $this->sendRequest(
            'get',
            $this->interTransfersUrl().'/status/'.$this->accountSegment('collection_account_number'),
            ['clientReference' => $clientReference]
        );
    }

    protected function buildSendMoneyPayload(array $payload): array
    {
        $clientReference = $this->requireClientReference($payload);
        $amount = $this->requireAmount($payload);

        if (empty($payload['recipientMsisdn'])) {
            throw new InvalidArgumentException('A recipient mobile money number is required.');
        }

        if (empty($payload['channel'])) {
            throw new InvalidArgumentException('A network channel is required, e.g. "mtn-gh".');
        }

        if (empty($payload['primaryCallbackUrl'])) {
            throw new InvalidArgumentException('A primary callback URL is required.');
        }

        if (empty($payload['description'])) {
            throw new InvalidArgumentException('A description is required.');
        }

        return array_filter([
            'RecipientName' => $payload['recipientName'] ?? null,
            'RecipientMsisdn' => $payload['recipientMsisdn'],
            'CustomerEmail' => $payload['customerEmail'] ?? null,
            'Channel' => $payload['channel'],
            'Amount' => $amount,
            // Header-cased "URL" is Hubtel's own field name for this endpoint, not a typo to "fix".
            'PrimaryCallbackURL' => $payload['primaryCallbackUrl'],
            'Description' => $payload['description'],
            'ClientReference' => $clientReference,
        ], fn ($value) => $value !== null);
    }

    protected function buildSendToBankPayload(array $payload): array
    {
        $clientReference = $this->requireClientReference($payload);
        $amount = $this->requireAmount($payload);

        if (empty($payload['bankAccountNumber'])) {
            throw new InvalidArgumentException('A bank account number is required.');
        }

        if (empty($payload['primaryCallbackUrl'])) {
            throw new InvalidArgumentException('A primary callback URL is required.');
        }

        if (empty($payload['description'])) {
            throw new InvalidArgumentException('A description is required.');
        }

        return array_filter([
            'Amount' => $amount,
            'PrimaryCallbackURL' => $payload['primaryCallbackUrl'],
            'Description' => $payload['description'],
            'BankAccountNumber' => $payload['bankAccountNumber'],
            'BankAccountName' => $payload['bankAccountName'] ?? null,
            'RecipientPhoneNumber' => $payload['recipientPhoneNumber'] ?? null,
            'BankName' => $payload['bankName'] ?? null,
            'BankBranch' => $payload['bankBranch'] ?? null,
            'BankBranchCode' => $payload['bankBranchCode'] ?? null,
            'ClientReference' => $clientReference,
        ], fn ($value) => $value !== null);
    }

    protected function buildBalanceTransferPayload(array $payload): array
    {
        $clientReference = $this->requireClientReference($payload);
        $amount = $this->requireAmount($payload);

        if (empty($payload['description'])) {
            throw new InvalidArgumentException('A description is required.');
        }

        if (empty($payload['primaryCallbackUrl'])) {
            throw new InvalidArgumentException('A primary callback URL is required.');
        }

        return [
            'Description' => $payload['description'],
            'Amount' => $amount,
            'ClientReference' => $clientReference,
            'DestinationAccountNumber' => trim((string) $this->accountSegmentRaw('disbursement_account_number')),
            // Normal-cased, unlike Send Money's PrimaryCallbackURL; Hubtel's own inconsistency.
            'PrimaryCallbackUrl' => $payload['primaryCallbackUrl'],
        ];
    }

    protected function requireClientReference(array $payload): string
    {
        $clientReference = trim((string) ($payload['clientReference'] ?? ''));
        if ($clientReference === '') {
            throw new InvalidArgumentException('A client reference is required.');
        }

        if (strlen($clientReference) > 36) {
            throw new InvalidArgumentException('The client reference must be 36 characters or fewer.');
        }

        return $clientReference;
    }

    protected function requireAmount(array $payload): float
    {
        $amount = (float) ($payload['amount'] ?? 0);
        if ($amount <= 0) {
            throw new InvalidArgumentException('The transfer amount must be greater than zero.');
        }

        return round($amount, 2);
    }

    protected function disbursementUrl(string $endpointKey): string
    {
        return rtrim((string) config("hubtel.transfers.endpoints.$endpointKey"), '/')
            .'/'.$this->accountSegment('disbursement_account_number');
    }

    protected function collectionUrl(): string
    {
        return $this->interTransfersUrl().'/'.$this->accountSegment('collection_account_number');
    }

    protected function interTransfersUrl(): string
    {
        return rtrim((string) config('hubtel.transfers.endpoints.inter_transfers'), '/');
    }

    protected function accountSegment(string $configKey): string
    {
        return rawurlencode($this->accountSegmentRaw($configKey));
    }

    protected function accountSegmentRaw(string $configKey): string
    {
        $value = trim((string) config("hubtel.transfers.$configKey"));
        if ($value === '') {
            $label = $configKey === 'collection_account_number' ? 'collection' : 'disbursement';

            throw new InvalidArgumentException("A Hubtel $label account number is required.");
        }

        return $value;
    }
}
