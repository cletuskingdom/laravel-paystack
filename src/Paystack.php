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

    public function generateReference($prefix = null)
    {
        $reference = Str::random(12);
        
        if ($prefix) {
            return $prefix . '_' . $reference;
        }

        return $reference;
    }
    
    public function redirectToGateway()
    {
        $data = [
            "amount" => request()->amount,
            "email" => request()->email,
            "reference" => $this->generateReference(),
            "callback_url" => request()->callback_url,
            // Add other fields as needed
        ];

        $response = $this->makePayment($data);

        // If the API call was successful, redirect the user
        if ($response['status']) {
            return redirect()->away($response['data']['authorization_url']);
        }

        return back()->withErrors(['message' => 'Paystack is currently unavailable.']);
    }

    public function getAllPlans()
    {
        return $this->performRequest('GET', '/plan');
    }

    public function createPlan(array $data)
    {
        return $this->performRequest('POST', '/plan', $data);
    }

    public function createCustomer(array $data)
    {
        return $this->performRequest('POST', '/customer', $data);
    }

    public function getPaymentData()
    {
        $reference = request()->query('reference');

        if (!$reference) {
            return [
                'status' => false,
                'message' => 'No transaction reference found in the request.'
            ];
        }

        return $this->verifyTransaction($reference);
    }

    public function getAllTransactions() {
        return $this->performRequest('GET', 'transaction');
    }

    public function getTransaction($id) {
        return $this->performRequest('GET', "transaction/{$id}");
    }

    public function isSuccessful($response) {
        return isset($response['status']) && $response['status'] === true;
    }
}