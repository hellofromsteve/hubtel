<?php

namespace HelloFromSteve\Hubtel\Tests\Feature;

use HelloFromSteve\Hubtel\HubtelService;
use HelloFromSteve\Hubtel\HubtelTransferService;
use HelloFromSteve\Hubtel\Tests\HubtelTestCase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class HubtelTransferServiceTest extends HubtelTestCase
{
    public function test_it_sends_money_with_pascal_cased_payload(): void
    {
        Http::fake([
            'smp.hubtel.com/*' => Http::response([
                'responseCode' => '0000',
                'data' => [
                    'amountDebited' => 150.00,
                    'transactionId' => 'abc123',
                    'clientReference' => 'PAY-2026-10-001',
                    'recipientName' => 'Jane Doe',
                ],
            ]),
        ]);

        $response = app(HubtelTransferService::class)->sendMoney([
            'recipientName' => 'Jane Doe',
            'recipientMsisdn' => '233249111411',
            'channel' => 'mtn-gh',
            'amount' => 150,
            'primaryCallbackUrl' => 'https://api.example.com/webhooks/hubtel-send-money',
            'description' => 'October payroll',
            'clientReference' => 'PAY-2026-10-001',
        ]);

        $this->assertSame('0000', $response['responseCode']);
        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://smp.hubtel.com/api/merchants/disbursement-456/send/mobilemoney'
                && $request['RecipientName'] === 'Jane Doe'
                && $request['RecipientMsisdn'] === '233249111411'
                && $request['Channel'] === 'mtn-gh'
                && $request['Amount'] === 150.0
                && $request['PrimaryCallbackURL'] === 'https://api.example.com/webhooks/hubtel-send-money'
                && $request['ClientReference'] === 'PAY-2026-10-001'
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('transfer_key:transfer_secret'));
        });
    }

    public function test_send_money_rejects_client_reference_over_36_characters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(HubtelTransferService::class)->sendMoney(array_merge($this->sendMoneyPayload(), [
            'clientReference' => str_repeat('x', 37),
        ]));
    }

    public function test_send_money_rejects_non_positive_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(HubtelTransferService::class)->sendMoney(array_merge($this->sendMoneyPayload(), [
            'amount' => 0,
        ]));
    }

    public function test_it_sends_to_bank_with_bank_code_in_url_not_body(): void
    {
        Http::fake([
            'smp.hubtel.com/*' => Http::response(['responseCode' => '0000', 'data' => []]),
        ]);

        app(HubtelTransferService::class)->sendToBank('300304', [
            'amount' => 150,
            'primaryCallbackUrl' => 'https://api.example.com/webhooks/hubtel-send-money',
            'description' => 'October payroll',
            'bankAccountNumber' => '1234567890',
            'clientReference' => 'PAY-2026-10-002',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://smp.hubtel.com/api/merchants/disbursement-456/send/bank/gh/300304'
                && ! array_key_exists('BankCode', $request->data())
                && $request['BankAccountNumber'] === '1234567890';
        });
    }

    public function test_it_checks_send_status_keeping_created_at_raw(): void
    {
        Http::fake([
            'smrsc.hubtel.com/*' => Http::response([
                'responseCode' => '0000',
                'data' => [
                    'transactionId' => 'abc123',
                    'clientReference' => 'PAY-2026-10-001',
                    'transactionStatus' => 'Paid',
                    'createdAt' => '2026-10-01 09:15:00',
                ],
            ]),
        ]);

        $response = app(HubtelTransferService::class)->sendStatus('PAY-2026-10-001');

        $this->assertSame('2026-10-01 09:15:00', $response['data']['createdAt']);
        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://smrsc.hubtel.com/api/merchants/disbursement-456/transactions/status?clientReference=PAY-2026-10-001';
        });
    }

    public function test_it_gets_collection_balance(): void
    {
        Http::fake([
            'trnf.hubtel.com/*' => Http::response(['responseCode' => '0000', 'data' => ['amount' => 542.30]]),
        ]);

        $response = app(HubtelTransferService::class)->collectionBalance();

        $this->assertSame(542.30, $response['data']['amount']);
        Http::assertSent(fn ($request) => $request->url() === 'https://trnf.hubtel.com/api/inter-transfers/collection-123');
    }

    public function test_it_gets_disbursement_balance(): void
    {
        Http::fake([
            'trnf.hubtel.com/*' => Http::response(['responseCode' => '0000', 'data' => ['amount' => 1200]]),
        ]);

        app(HubtelTransferService::class)->disbursementBalance();

        Http::assertSent(fn ($request) => $request->url() === 'https://trnf.hubtel.com/api/inter-transfers/prepaid/disbursement-456');
    }

    public function test_it_transfers_to_disbursement_using_configured_destination(): void
    {
        Http::fake([
            'trnf.hubtel.com/*' => Http::response([
                'responseCode' => '200',
                'data' => ['clientReference' => 'XFER-2026-10-001', 'amount' => 5000.00],
            ]),
        ]);

        app(HubtelTransferService::class)->transferToDisbursement([
            'amount' => 5000,
            'description' => 'Fund payroll disbursement',
            'clientReference' => 'XFER-2026-10-001',
            'primaryCallbackUrl' => 'https://api.example.com/webhooks/hubtel-transfer',
        ]);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://trnf.hubtel.com/api/inter-transfers/collection-123'
                && $request['DestinationAccountNumber'] === 'disbursement-456'
                && $request['PrimaryCallbackUrl'] === 'https://api.example.com/webhooks/hubtel-transfer';
        });
    }

    public function test_it_checks_balance_transfer_status(): void
    {
        Http::fake([
            'trnf.hubtel.com/*' => Http::response([
                'responseCode' => '0000',
                'data' => [
                    'clientReference' => 'XFER-2026-10-001',
                    'status' => 'failed',
                    'failureReason' => 'Insufficient balance',
                ],
            ]),
        ]);

        $response = app(HubtelTransferService::class)->balanceTransferStatus('XFER-2026-10-001');

        $this->assertSame('failed', $response['data']['status']);
        Http::assertSent(fn ($request) => $request->url() === 'https://trnf.hubtel.com/api/inter-transfers/status/collection-123?clientReference=XFER-2026-10-001');
    }

    public function test_it_throws_on_failed_request(): void
    {
        Http::fake([
            '*' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        $this->expectException(RequestException::class);

        app(HubtelTransferService::class)->sendMoney($this->sendMoneyPayload());
    }

    public function test_transfer_service_is_available_from_hubtel_service(): void
    {
        $this->assertInstanceOf(
            HubtelTransferService::class,
            app(HubtelService::class)->transfers()
        );
    }

    private function sendMoneyPayload(): array
    {
        return [
            'recipientName' => 'Jane Doe',
            'recipientMsisdn' => '233249111411',
            'channel' => 'mtn-gh',
            'amount' => 150,
            'primaryCallbackUrl' => 'https://api.example.com/webhooks/hubtel-send-money',
            'description' => 'October payroll',
            'clientReference' => 'PAY-2026-10-001',
        ];
    }
}
