<?php

namespace CletusKingdom\Paystack\Exceptions;

class ConnectionException extends PaystackException
{
    /**
     * Create a new connection exception instance
     *
     * @param string $message
     */
    public function __construct(string $message = "Unable to connect to Paystack API")
    {
        parent::__construct($message, 0);
    }

    /**
     * Create exception for timeout
     *
     * @return static
     */
    public static function timeout(): static
    {
        return new static('Request timeout: Unable to connect to Paystack API');
    }

    /**
     * Create exception for DNS failure
     *
     * @return static
     */
    public static function dnsFailure(): static
    {
        return new static('DNS resolution failed: Unable to reach Paystack API');
    }

    /**
     * Create exception for SSL error
     *
     * @return static
     */
    public static function sslError(): static
    {
        return new static('SSL certificate verification failed');
    }
}