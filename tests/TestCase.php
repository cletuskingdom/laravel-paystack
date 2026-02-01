<?php 
namespace CletusKingdom\Paystack\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use CletusKingdom\Paystack\PaystackServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        // Any additional setup like migrations or factory loading goes here
    }

    protected function getPackageProviders($app)
    {
        return [
            PaystackServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Set up the testing environment (like fake API keys)
        $app['config']->set('paystack.secretKey', 'sk_test_mock_key');
    }
}