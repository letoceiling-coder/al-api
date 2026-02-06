<?php

namespace App\Exceptions;

use Exception;

class RateLimitExceededException extends Exception
{
    public function __construct(
        string $message = "Rate limit exceeded",
        public readonly int $retryAfter = 60,
        int $code = 429
    ) {
        parent::__construct($message, $code);
    }

    public function render()
    {
        return response()->json([
            'success' => false,
            'error' => [
                'type' => 'RateLimitExceeded',
                'message' => $this->getMessage(),
                'retry_after' => $this->retryAfter,
            ],
        ], $this->code)->header('Retry-After', $this->retryAfter);
    }
}
