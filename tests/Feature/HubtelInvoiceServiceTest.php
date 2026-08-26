<?php

namespace HelloFromSteve\Hubtel\Tests\Feature;

use HelloFromSteve\Hubtel\HubtelInvoiceService;
use HelloFromSteve\Hubtel\HubtelService;
use HelloFromSteve\Hubtel\Tests\HubtelTestCase;
use Illuminate\Support\Facades\Http;

class HubtelInvoiceServiceTest extends HubtelTestCase
{
    public function test_it_issues_a_simple_invoice(): void
    {
        Http::fake([
            'invoicing.hubtel.com/*' => Http::response([
                'message' => 'Invoice has been created.',
                'code' => 201,
                'data' => [
                    'invoiceId' => 'invoice-1',
                    'paymentUrl' => 'https://approval.biz/invoice-1',
                ],
            ], 201),
        ]);

        $response = app(HubtelInvoiceService::class)->create($this->invoicePayload());

        $this->assertSame('invoice-1', $response['data']['invoiceId']);
        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://invoicing.hubtel.com/api/v2.0/invoice/11684/simple'
                && $request['invoiceNumber'] === 'INV-123'
                && $request['callbackUrl'] === 'http://test.com/invoice-callback'
                && $request->hasHeader(
                    'Authorization',
                    'Basic '.base64_encode('invoice_api_id:invoice_api_key')
                );
        });
    }

    public function test_it_issues_a_repeat_invoice(): void
    {
        Http::fake([
            'invoicing.hubtel.com/*' => Http::response([
                'message' => 'Invoice has been created.',
                'code' => 201,
                'data' => ['invoiceId' => 'repeat-1'],
            ], 201),
        ]);

        $payload = array_merge($this->invoicePayload(), [
            'frequency' => 'Daily',
            'shouldBeAutoDebited' => false,
        ]);
        app(HubtelInvoiceService::class)->repeat($payload);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://invoicing.hubtel.com/api/v2.0/invoice/11684/repeat'
                && $request['frequency'] === 'Daily'
                && $request['shouldBeAutoDebited'] === false;
        });
    }

    public function test_it_checks_invoice_transaction_status(): void
    {
        Http::fake([
            'invoicing.hubtel.com/*' => Http::response([
                'message' => 'Success',
                'code' => 200,
                'data' => [
                    'status' => 'Fully Paid',
                    'payments' => [
                        ['id' => 'payment-1', 'amountPaid' => 1, 'status' => 'Paid'],
                    ],
                ],
            ]),
        ]);

        $response = app(HubtelInvoiceService::class)->checkStatus('invoice / 1');

        $this->assertSame('Fully Paid', $response['data']['status']);
        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://invoicing.hubtel.com/api/v2.0/invoice/11684/invoice%20%2F%201/status-check';
        });
    }

    public function test_invoice_service_is_available_from_hubtel_service(): void
    {
        $this->assertInstanceOf(
            HubtelInvoiceService::class,
            app(HubtelService::class)->invoices()
        );
    }

    private function invoicePayload(): array
    {
        return [
            'invoiceNumber' => 'INV-123',
            'dueDate' => '2026-06-02T23:59:59Z',
            'createdBy' => 'John',
            'customerName' => 'John',
            'customerPhoneNumber' => '233555654321',
            'customerEmail' => 'john@example.com',
            'note' => 'Test invoice',
            'items' => [
                ['description' => 'Item', 'quantity' => 1, 'unitPrice' => 1],
            ],
        ];
    }
}
