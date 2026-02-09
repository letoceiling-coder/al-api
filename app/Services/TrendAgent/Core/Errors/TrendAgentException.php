<?php

namespace App\Services\TrendAgent\Core\Errors;

/**
 * Базовое исключение для TrendAgent API
 */
abstract class TrendAgentException extends \Exception
{
    public function __construct(
        string $message,
        public readonly bool $retriable = false,
        public readonly array $context = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
