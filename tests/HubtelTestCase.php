<?php

namespace HelloFromSteve\Hubtel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

class HubtelTestCase extends Orchestra
{
    protected function getEnvironmentSetUp($app)
    {
        // 1. Setup default config for the package
        // This simulates having these in config/hubtel.php
        $app['config']->set('hubtel.api_key', 'fake_key');
        $app['config']->set('hubtel.api_secret', 'fake_secret');
        $app['config']->set('hubtel.merchant_account_number', '2010000');
        
        // URLs
        $app['config']->set('hubtel.endpoints.initiate', 'https://payproxyapi.hubtel.com/items/initiate');
        $app['config']->set('hubtel.callback_url', 'http://test.com/callback');
        $app['config']->set('hubtel.return_url', 'http://test.com/return');
        $app['config']->set('hubtel.cancelled_url', 'http://test.com/cancel');
    }
}