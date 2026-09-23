<?php

namespace HelloFromSteve\Hubtel\Tests\Feature;

use HelloFromSteve\Hubtel\Tests\HubtelTestCase;
use HelloFromSteve\Hubtel\HubtelService;
use Illuminate\Support\Facades\Http;

class HubtelServiceTest extends HubtelTestCase
{
    public function test_it_can_initialize_payment_with_default_config_urls()
    {
        // 1. Fake the HTTP response
        // Tell Laravel: "If anyone sends a request to Hubtel, return this JSON array with status 200"
        Http::fake([
            'payproxyapi.hubtel.com/*' => Http::response([
                'status' => 'Success',
                'data' => ['checkoutUrl' => 'https://hubtel.com/checkout/123']
            ], 200),
        ]);

        // 2. Instantiate your client
        $client = new HubtelService();

        // 3. Call the method
        $response = $client->initialize([
            'totalAmount' => 100,
            'description' => 'Test Item'
        ]);

        // 4. Assertions on the RESPONSE
        $this->assertEquals('Success', $response['status']);
        $this->assertEquals('https://hubtel.com/checkout/123', $response['data']['checkoutUrl']);

        // 5. Assertions on the REQUEST (The most important part!)
        // Verify that your code actually sent the correct data to Hubtel
        Http::assertSent(function ($request) {
            return 
                $request->url() == 'https://payproxyapi.hubtel.com/items/initiate' &&
                $request['totalAmount'] == 100 &&
                $request['callbackUrl'] == 'http://test.com/callback' && // Proves config merge worked
                !empty($request['clientReference']); // Proves UUID was generated
        });
    }

    public function test_it_checks_transaction_status_by_hubtel_transaction_id()
    {
        Http::fake([
            'api-txnstatus.hubtel.com/*' => Http::response([
                'status' => 'Success',
                'responseCode' => '0000',
                'data' => ['status' => 'Paid'],
            ]),
        ]);

        $response = app(HubtelService::class)->checkStatus('hubtel-123');

        $this->assertSame('Paid', $response['data']['status']);
        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://api-txnstatus.hubtel.com/transactions/2010000/status?hubtelTransactionId=hubtel-123'
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('fake_key:fake_secret'));
        });
    }

    public function test_it_checks_transaction_status_by_client_reference()
    {
        Http::fake([
            'api-txnstatus.hubtel.com/*' => Http::response([
                'status' => 'Success',
                'responseCode' => '0000',
                'data' => ['clientReference' => 'ORDER / 1', 'status' => 'Paid'],
            ]),
        ]);

        app(HubtelService::class)->checkStatusByClientReference('ORDER / 1');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api-txnstatus.hubtel.com/transactions/2010000/status?clientReference=ORDER%20%2F%201';
        });
    }

    public function test_it_falls_back_to_deprecated_merchant_account_number_key()
    {
        config(['hubtel.collection_account_number' => null, 'hubtel.merchant_account_number' => '2010000']);

        Http::fake([
            'api-txnstatus.hubtel.com/*' => Http::response([
                'status' => 'Success',
                'responseCode' => '0000',
                'data' => ['status' => 'Paid'],
            ]),
        ]);

        app(HubtelService::class)->checkStatus('hubtel-123');

        Http::assertSent(fn ($request) => $request->url() === 'https://api-txnstatus.hubtel.com/transactions/2010000/status?hubtelTransactionId=hubtel-123');
    }

    public function test_it_throws_error_on_failed_request()
    {
        // 1. Fake a 401 Unauthorized error
        Http::fake([
            '*' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        $client = new HubtelService();

        // 2. Expect an exception
        $this->expectException(\Illuminate\Http\Client\RequestException::class);

        // 3. This should explode
        $client->initialize(['amount' => 10]);
    }
}