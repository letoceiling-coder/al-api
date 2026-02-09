<?php

namespace App\Services\TrendAgent\Core\Errors;

/**
 * Ошибка "объект не найден"
 * 
 * Retriable: false - объект не существует, повтор не поможет
 */
class NotFoundError extends TrendAgentException
{
    public function __construct(string $message = 'Object not found', array $context = [])
    {
        parent::__construct($message, retriable: false, context: $context);
    }
}
