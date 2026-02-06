<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when an API key is invalid or missing.
 * Rendered as RFC 7807 Problem Details by Handler.
 */
class InvalidApiKeyException extends Exception
{
    public function __construct(
        string $message = "Invalid or missing API key",
        protected readonly ?string $provider = null,
        int $code = 401
    ) {
        parent::__construct($message, $code);
    }

    /**
     * Get the provider that failed authentication
     */
    public function getProvider(): ?string
    {
        return $this->provider;
    }

    /**
     * Render is handled by Handler.php in RFC 7807 format
     * This method is kept for backward compatibility but not used
     */
    public function render()
    {
        // RFC 7807 rendering is handled by Handler.php
        return null;
    }
}
