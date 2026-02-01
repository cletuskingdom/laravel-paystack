<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paystack Secret Key
    |--------------------------------------------------------------------------
    |
    | Your Paystack secret key from your Paystack Dashboard
    | https://dashboard.paystack.com/#/settings/developers
    |
    */
    'secretKey' => env('PAYSTACK_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Paystack Public Key
    |--------------------------------------------------------------------------
    |
    | Your Paystack public key from your Paystack Dashboard
    |
    */
    'publicKey' => env('PAYSTACK_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Paystack Payment URL
    |--------------------------------------------------------------------------
    |
    | The base URL for Paystack API requests
    |
    */
    'paymentUrl' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co/'),

    /*
    |--------------------------------------------------------------------------
    | Merchant Email
    |--------------------------------------------------------------------------
    |
    | Your merchant email address
    |
    */
    'merchantEmail' => env('MERCHANT_EMAIL'),

];