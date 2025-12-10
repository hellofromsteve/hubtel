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