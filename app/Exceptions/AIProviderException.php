<?php

namespace App\Exceptions;

use Exception;

class AIProviderException extends Exception
{
    public function __construct(
        string $message = "AI provider error",
        public readonly ?string $provider = null,
        int $code = 500
    ) {
        parent::__construct($message, $code);
    }

    public function render()
    {
        return response()->json([
            'success' => false,
            'error' => [
                'type' => 'AIProviderError',
                'message' => $this->getMessage(),
                'provider' => $this->provider,
            ],
        ], $this->code);
    }
}
