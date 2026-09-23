<?php

namespace HelloFromSteve\Hubtel\Tests\Feature;

use HelloFromSteve\Hubtel\HubtelService;
use HelloFromSteve\Hubtel\HubtelVerificationService;
use HelloFromSteve\Hubtel\Tests\HubtelTestCase;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class HubtelVerificationServiceTest extends HubtelTestCase
{
    public function test_it_verifies_a_bank_account(): void
    {
        Http::fake([
            'rnv.hubtel.com/*' => Http::response([
                'message' => null,
                'responseCode' => '0000',
                'data' => ['name' => 'Jane Doe'],
            ]),
        ]);

        $response = app(HubtelVerificationService::class)->verifyBankAccount('300304', '1234567890');

        $this->assertTrue($response['success']);
        $this->assertSame('Jane Doe', $response['accountName']);
        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://rnv.hubtel.com/v2/merchantaccount/merchants/11684/bank/verify/300304/1234567890'
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('verification_key:verification_secret'));
        });
    }

    public function test_success_response_code_with_blank_name_is_treated_as_failure(): void
    {
        Http::fake([
            'rnv.hubtel.com/*' => Http::response([
                'message' => null,
                'responseCode' => '0000',
                'data' => ['name' => ''],
            ]),
        ]);

        $response = app(HubtelVerificationService::class)->verifyBankAccount('300304', '1234567890');

        $this->assertFalse($response['success']);
        $this->assertNull($response['accountName']);
    }

    public function test_soft_failure_on_non_2xx_status_does_not_throw_and_surfaces_message(): void
    {
        Http::fake([
            'rnv.hubtel.com/*' => Http::response([
                'message' => 'We could not fully verify this account, save anyway?',
                'responseCode' => '2001',
                'data' => null,
            ], 400),
        ]);

        $response = app(HubtelVerificationService::class)->verifyBankAccount('300304', '1234567890');

        $this->assertFalse($response['success']);
        $this->assertSame('We could not fully verify this account, save anyway?', $response['message']);
    }

    public function test_it_verifies_a_mobile_money_number(): void
    {
        Http::fake([
            'rnv.hubtel.com/*' => Http::response([
                'message' => null,
                'responseCode' => '0000',
                'data' => [
                    'isRegistered' => true,
                    'name' => 'Jane Doe',
                    'status' => 'Active',
                    'profile' => 'Subscriber',
                ],
            ]),
        ]);

        $response = app(HubtelVerificationService::class)->verifyMobileMoney('mtn-gh', '233249111411');

        $this->assertTrue($response['success']);
        $this->assertSame('Jane Doe', $response['accountName']);
        $this->assertSame('Subscriber', $response['data']['profile']);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://rnv.hubtel.com/v2/merchantaccount/merchants/11684/mobilemoney/verify?channel=mtn-gh&customerMsisdn=233249111411';
        });
    }

    public function test_verify_bank_account_rejects_missing_bank_code(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(HubtelVerificationService::class)->verifyBankAccount('', '1234567890');
    }

    public function test_verify_mobile_money_rejects_missing_channel(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(HubtelVerificationService::class)->verifyMobileMoney('', '233249111411');
    }

    public function test_verification_service_is_available_from_hubtel_service(): void
    {
        $this->assertInstanceOf(
            HubtelVerificationService::class,
            app(HubtelService::class)->verification()
        );
    }
}
