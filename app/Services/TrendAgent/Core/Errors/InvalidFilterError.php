<?php

namespace App\Services\TrendAgent\Core\Errors;

/**
 * Ошибка невалидного фильтра
 * 
 * Retriable: false - ошибка валидации, повтор не поможет
 */
class InvalidFilterError extends TrendAgentException
{
    public function __construct(string $message, array $context = [])
    {
        parent::__construct($message, retriable: false, context: $context);
    }
}
