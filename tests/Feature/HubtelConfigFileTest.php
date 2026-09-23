<?php

namespace HelloFromSteve\Hubtel\Tests\Feature;

use HelloFromSteve\Hubtel\Tests\HubtelTestCase;

class HubtelConfigFileTest extends HubtelTestCase
{
    protected function tearDown(): void
    {
        foreach (['HUBTEL_COLLECTION_ACCOUNT_NUMBER', 'HUBTEL_DISBURSEMENT_ACCOUNT_NUMBER', 'HUBTEL_MERCHANT_ACCOUNT_NUMBER', 'HUBTEL_TRANSFER_COLLECTION_ACCOUNT_NUMBER'] as $variable) {
            putenv($variable);
        }

        parent::tearDown();
    }

    public function test_shared_account_numbers_propagate_to_every_feature(): void
    {
        putenv('HUBTEL_COLLECTION_ACCOUNT_NUMBER=shared-collection-999');
        putenv('HUBTEL_DISBURSEMENT_ACCOUNT_NUMBER=shared-disbursement-888');

        $config = require __DIR__.'/../../config/hubtel.php';

        $this->assertSame('shared-collection-999', $config['collection_account_number']);
        $this->assertSame('shared-collection-999', $config['merchant_account_number']);
        $this->assertSame('shared-disbursement-888', $config['disbursement_account_number']);
        $this->assertSame('shared-collection-999', $config['invoicing']['collection_account_number']);
        $this->assertSame('shared-collection-999', $config['transfers']['collection_account_number']);
        $this->assertSame('shared-disbursement-888', $config['transfers']['disbursement_account_number']);
        $this->assertSame('shared-collection-999', $config['verification']['collection_account_number']);
    }

    public function test_legacy_merchant_account_number_env_var_still_seeds_the_shared_value(): void
    {
        putenv('HUBTEL_MERCHANT_ACCOUNT_NUMBER=legacy-merchant-777');

        $config = require __DIR__.'/../../config/hubtel.php';

        $this->assertSame('legacy-merchant-777', $config['collection_account_number']);
        $this->assertSame('legacy-merchant-777', $config['invoicing']['collection_account_number']);
    }

    public function test_per_feature_env_var_still_overrides_the_shared_value(): void
    {
        putenv('HUBTEL_COLLECTION_ACCOUNT_NUMBER=shared-collection-999');
        putenv('HUBTEL_TRANSFER_COLLECTION_ACCOUNT_NUMBER=transfers-own-111');

        $config = require __DIR__.'/../../config/hubtel.php';

        $this->assertSame('shared-collection-999', $config['collection_account_number']);
        $this->assertSame('transfers-own-111', $config['transfers']['collection_account_number']);
    }
}
