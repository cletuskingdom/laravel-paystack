<?php

namespace CletusKingdom\Paystack\Exceptions;

class ValidationException extends PaystackException
{
    /**
     * The validation errors
     *
     * @var array
     */
    protected array $errors = [];

    /**
     * Create a new validation exception instance
     *
     * @param string $message
     * @param array $errors
     * @param int $code
     */
    public function __construct(string $message = "Validation failed", array $errors = [], int $code = 422)
    {
        parent::__construct($message, $code);
        
        $this->errors = $errors;
    }

    /**
     * Get the validation errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if there are validation errors
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get a specific validation error
     *
     * @param string $field
     * @return string|null
     */
    public function getError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * Convert exception to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'errors' => $this->errors,
        ]);
    }
}