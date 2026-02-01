<?php

namespace CletusKingdom\Paystack;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;

class Paystack
{
    protected $client;
    protected $secretKey;

    public function __construct()
    {
        $this->secretKey = Config::get('paystack.secretKey');
        $baseUrl = Config::get('paystack.paymentUrl', 'https://api.paystack.co/');

        $this->client = new Client([
            'base_uri' => $baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ]
        ]);
    }

    private function performRequest($method, $relativeUrl, $data = [])
    {
        $options = [];
        if (!empty($data)) {
            $options['json'] = $data;
        }

        $response = $this->client->request($method, $relativeUrl, $options);

        return json_decode($response->getBody(), true);
    }

    public function makePayment(array $data)
    {
        return $this->performRequest('POST', 'transaction/initialize', $data);
    }

    public function verifyTransaction($reference)
    {
        return $this->performRequest('GET', "transaction/verify/{$reference}");
    }
}