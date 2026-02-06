<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when an AI provider (Gemini, OpenAI) returns an error.
 * Rendered as RFC 7807 Problem Details by Handler.
 */
class AIProviderException extends Exception
{
    public function __construct(
        string $message = "AI provider error",
        protected readonly ?string $provider = null,
        int $code = 502
    ) {
        parent::__construct($message, $code);
    }

    /**
     * Get the AI provider that caused the error
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
