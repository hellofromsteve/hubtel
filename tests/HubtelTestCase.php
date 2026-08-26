<?php

namespace HelloFromSteve\Hubtel\Tests;

use HelloFromSteve\Hubtel\HubtelServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class HubtelTestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [HubtelServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('hubtel.api_key', 'fake_key');
        $app['config']->set('hubtel.api_secret', 'fake_secret');
        $app['config']->set('hubtel.merchant_account_number', '2010000');
        $app['config']->set('hubtel.timeout', 45);

        $app['config']->set('hubtel.endpoints.initiate', 'https://payproxyapi.hubtel.com/items/initiate');
        $app['config']->set('hubtel.endpoints.status', 'https://api-txnstatus.hubtel.com/transactions');
        $app['config']->set('hubtel.callback_url', 'http://test.com/callback');
        $app['config']->set('hubtel.return_url', 'http://test.com/return');
        $app['config']->set('hubtel.cancelled_url', 'http://test.com/cancel');

        $app['config']->set('hubtel.invoicing.api_id', 'invoice_api_id');
        $app['config']->set('hubtel.invoicing.api_key', 'invoice_api_key');
        $app['config']->set('hubtel.invoicing.collection_account_number', '11684');
        $app['config']->set('hubtel.invoicing.callback_url', 'http://test.com/invoice-callback');
        $app['config']->set('hubtel.invoicing.base_url', 'https://invoicing.hubtel.com');
    }
}
