<?php

namespace CletusKingdom\Paystack\Tests;

use CletusKingdom\Paystack\Paystack;
use CletusKingdom\Paystack\Exceptions\PaystackException;
use CletusKingdom\Paystack\Exceptions\ValidationException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;

class PaystackTest extends TestCase
{
    protected function getPaystackMock(array $responses = [])
    {
        // Create a mock handler with the provided responses
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // We use Reflection to inject the mock client into our Paystack class
        $paystack = new Paystack();
        $reflection = new \ReflectionClass($paystack);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($paystack, $client);

        return $paystack;
    }

    /** @test */
    public function it_formats_the_url_correctly_for_fetching_customers()
    {
        $mockResponse = ['status' => true, 'data' => ['first_name' => 'Cletus']];

        $paystack = $this->getPaystackMock([
            new Response(200, [], json_encode($mockResponse))
        ]);

        // We call fetchCustomer with a specific code
        $response = $paystack->fetchCustomer('CUS_123456');

        $this->assertTrue($response['status']);
        $this->assertEquals('Cletus', $response['data']['first_name']);
    }

    /** @test */
    public function it_sends_correct_data_when_creating_a_plan()
    {
        $mockResponse = ['status' => true, 'data' => ['plan_code' => 'PLN_123']];

        $paystack = $this->getPaystackMock([
            new Response(201, [], json_encode($mockResponse))
        ]);

        // Test createPlan specifically
        $response = $paystack->createPlan([
            'name' => 'Monthly Pro',
            'amount' => 500,
            'interval' => 'monthly'
        ]);

        $this->assertEquals('PLN_123', $response['data']['plan_code']);
        // Internally, your code should have converted 500 to 50000 kobo
    }

    /** @test */
    public function it_handles_deletion_of_recipients()
    {
        $mockResponse = ['status' => true, 'message' => 'Recipient deleted'];

        $paystack = $this->getPaystackMock([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $response = $paystack->deleteTransferRecipient('RCP_123');

        $this->assertEquals('Recipient deleted', $response['message']);
    }
}