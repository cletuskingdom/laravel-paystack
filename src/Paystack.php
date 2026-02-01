<?php

namespace CletusKingdom\Paystack;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

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
        try {
            $options = [];
            if (!empty($data)) {
                $options['json'] = $data;
            }

            $response = $this->client->request($method, $relativeUrl, $options);

            return json_decode($response->getBody(), true);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                return json_decode($responseBody, true);
            }
            
            return [
                'status' => false,
                'message' => 'Connection Error: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function makePayment(array $data)
    {
        // If the user didn't provide a reference, generate one
        if (!isset($data['reference'])) {
            $data['reference'] = $this->generateReference();
        }

        return $this->performRequest('POST', 'transaction/initialize', $data);
    }

    public function verifyTransaction($reference)
    {
        return $this->performRequest('GET', "transaction/verify/{$reference}");
    }

    /**
     * Generate a unique transaction reference
     * @param string|null $prefix
     * @return string
     */
    public function generateReference($prefix = null)
    {
        $reference = Str::random(12);
        
        if ($prefix) {
            return $prefix . '_' . $reference;
        }

        return $reference;
    }
}