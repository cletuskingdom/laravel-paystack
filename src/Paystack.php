<?php

namespace CletusKingdom\Paystack;

use GuzzleHttp\Client;

class Paystack
{
    protected $baseUrl;
    protected $secretKey;
    protected $client;

    public function __construct()
    {
        $this->setBaseUrl();
        $this->setKey();
        $this->client = new Client(['base_uri' => $this->baseUrl]);
    }

    protected function setHttpResponse($url, $method, $body = [])
    {
        $response = $this->client->{strtolower($method)}($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'json' => $body
        ]);

        return json_decode($response->getBody(), true);
    }

    public function makePayment(array $data)
    {
        return $this->setHttpResponse('/transaction/initialize', 'POST', $data);
    }

    public function verifyTransaction($reference)
    {
        return $this->setHttpResponse("/transaction/verify/{$reference}", 'GET');
    }

    public function setBaseUrl()
    {
        $this->baseUrl = config('paystack.base_url');
    }

    /**
     * Get secret key from Paystack config file
     */
    public function setKey()
    {
        $this->secretKey = config('paystack.secret_key');
    }

    /**
     * Set options for making the Client request
     */
    private function setRequestOptions()
    {
        $authBearer = 'Bearer ' . $this->secretKey;

        $this->client = new Client(
            [
                'base_uri' => $this->baseUrl,
                'headers' => [
                    'Authorization' => $authBearer,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json'
                ]
            ]
        );
    }
}