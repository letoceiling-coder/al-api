<?php

namespace App\Services\TrendAgent\Core\Errors;

/**
 * Ошибка частичной агрегации данных
 * 
 * Retriable: true - часть endpoint'ов упала, но есть частичные данные
 */
class PartialAggregationError extends TrendAgentException
{
    public function __construct(
        string $message,
        public readonly array $successfulResponses,
        public readonly array $failedEndpoints
    ) {
        parent::__construct($message, retriable: true, context: [
            'successful_count' => count($successfulResponses),
            'failed_count' => count($failedEndpoints),
            'failed_endpoints' => $failedEndpoints,
        ]);
    }
    
    /**
     * Проверить, есть ли частичные данные
     */
    public function hasPartialData(): bool
    {
        return !empty($this->successfulResponses);
    }
    
    /**
     * Получить успешные ответы
     */
    public function getSuccessfulResponses(): array
    {
        return $this->successfulResponses;
    }
    
    /**
     * Получить список неудачных endpoint'ов
     */
    public function getFailedEndpoints(): array
    {
        return $this->failedEndpoints;
    }
}
