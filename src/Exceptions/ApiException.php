<?php

namespace CletusKingdom\Paystack\Exceptions;

class ApiException extends PaystackException
{
    /**
     * Create a new API exception instance
     *
     * @param string $message
     * @param int $statusCode
     * @param array|null $response
     */
    public function __construct(string $message, int $statusCode = 500, ?array $response = null)
    {
        parent::__construct($message, 0, null, $response, $statusCode);
    }

    /**
     * Create exception for unauthorized access
     *
     * @return static
     */
    public static function unauthorized(): static
    {
        return new static('Unauthorized: Invalid API key', 401);
    }

    /**
     * Create exception for not found
     *
     * @param string $resource
     * @return static
     */
    public static function notFound(string $resource = 'Resource'): static
    {
        return new static("{$resource} not found", 404);
    }

    /**
     * Create exception for bad request
     *
     * @param string $message
     * @return static
     */
    public static function badRequest(string $message = 'Bad request'): static
    {
        return new static($message, 400);
    }

    /**
     * Create exception for rate limit exceeded
     *
     * @return static
     */
    public static function rateLimitExceeded(): static
    {
        return new static('Rate limit exceeded. Please try again later', 429);
    }

    /**
     * Create exception for server error
     *
     * @return static
     */
    public static function serverError(): static
    {
        return new static('Internal server error. Please try again later', 500);
    }
}