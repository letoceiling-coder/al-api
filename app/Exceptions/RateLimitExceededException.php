<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when rate limit is exceeded.
 * Rendered as RFC 7807 Problem Details by Handler.
 */
class RateLimitExceededException extends Exception
{
    public function __construct(
        string $message = "Rate limit exceeded",
        protected readonly int $retryAfter = 60,
        int $code = 429
    ) {
        parent::__construct($message, $code);
    }

    /**
     * Get the retry-after value in seconds
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
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
