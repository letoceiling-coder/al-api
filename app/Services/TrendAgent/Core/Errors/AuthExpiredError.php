<?php

namespace App\Services\TrendAgent\Core\Errors;

/**
 * Ошибка истечения токена авторизации
 * 
 * Retriable: true - можно повторить запрос после обновления токена
 */
class AuthExpiredError extends TrendAgentException
{
    public function __construct(string $message = 'Auth token expired', array $context = [])
    {
        parent::__construct($message, retriable: true, context: $context);
    }
}
