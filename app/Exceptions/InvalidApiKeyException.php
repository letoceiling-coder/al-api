<?php

namespace App\Exceptions;

use Exception;

class InvalidApiKeyException extends Exception
{
    public function __construct(string $message = "Invalid or missing API key", int $code = 401)
    {
        parent::__construct($message, $code);
    }

    public function render()
    {
        return response()->json([
            'success' => false,
            'error' => [
                'type' => 'InvalidApiKey',
                'message' => $this->getMessage(),
            ],
        ], $this->code);
    }
}
