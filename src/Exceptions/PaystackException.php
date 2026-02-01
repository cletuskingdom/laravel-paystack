<?php

namespace CletusKingdom\Paystack\Exceptions;

use Exception;

class PaystackException extends Exception
{
    /**
     * The response from Paystack API (if available)
     *
     * @var array|null
     */
    protected ?array $response = null;

    /**
     * The HTTP status code (if available)
     *
     * @var int|null
     */
    protected ?int $statusCode = null;

    /**
     * Create a new Paystack exception instance
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     * @param array|null $response
     * @param int|null $statusCode
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?Exception $previous = null,
        ?array $response = null,
        ?int $statusCode = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->response = $response;
        $this->statusCode = $statusCode;
    }

    /**
     * Get the Paystack API response
     *
     * @return array|null
     */
    public function getResponse(): ?array
    {
        return $this->response;
    }

    /**
     * Get the HTTP status code
     *
     * @return int|null
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Check if exception has a response
     *
     * @return bool
     */
    public function hasResponse(): bool
    {
        return $this->response !== null;
    }

    /**
     * Get error message from response or exception message
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        if ($this->hasResponse() && isset($this->response['message'])) {
            return $this->response['message'];
        }

        return $this->getMessage();
    }

    /**
     * Convert exception to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'status_code' => $this->statusCode,
            'response' => $this->response,
        ];
    }

    /**
     * Convert exception to JSON
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}