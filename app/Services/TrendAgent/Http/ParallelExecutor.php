<?php

namespace App\Services\TrendAgent\Http;

use Illuminate\Http\Client\Response;

/**
 * Исполнитель параллельных HTTP запросов
 * 
 * Ответственность:
 * - Параллельное выполнение множественных запросов
 * - Обработка частичных ошибок
 * - Различные стратегии (fail-fast vs all-settled)
 */
class ParallelExecutor
{
    public function __construct(
        private readonly HttpClient $httpClient
    ) {}

    /**
     * Выполнить все запросы параллельно
     * 
     * Fail-fast: если хотя бы один упал — вернуть ошибку
     * 
     * @param array<string, string> $requests [key => url]
     * @param array $headers Дополнительные headers
     * @return array<string, Response>
     * @throws \Exception если хотя бы один запрос неудачен
     */
    public function executeAll(array $requests, array $headers = []): array
    {
        $responses = $this->httpClient->getParallel($requests, $headers);

        // Проверить все ответы на ошибки
        foreach ($responses as $key => $response) {
            if (!$response->successful()) {
                throw new \RuntimeException(
                    "Request '{$key}' failed with status {$response->status()}: " . $response->body()
                );
            }
        }

        return $responses;
    }

    /**
     * Выполнить все запросы параллельно (all-settled)
     * 
     * Вернуть все ответы, включая неудачные
     * 
     * @param array<string, string> $requests [key => url]
     * @param array $headers Дополнительные headers
     * @return array<string, array{success: bool, response?: Response, error?: string, status?: int}>
     */
    public function executeAllSettled(array $requests, array $headers = []): array
    {
        $responses = $this->httpClient->getParallel($requests, $headers);
        $results = [];

        foreach ($responses as $key => $response) {
            if ($response->successful()) {
                $results[$key] = [
                    'success' => true,
                    'response' => $response,
                ];
            } else {
                $results[$key] = [
                    'success' => false,
                    'error' => $response->body(),
                    'status' => $response->status(),
                ];
            }
        }

        return $results;
    }

    /**
     * Получить только успешные ответы из all-settled результатов
     * 
     * @param array $settledResults Результаты executeAllSettled
     * @return array<string, Response>
     */
    public function getSuccessful(array $settledResults): array
    {
        $successful = [];

        foreach ($settledResults as $key => $result) {
            if ($result['success'] === true && isset($result['response'])) {
                $successful[$key] = $result['response'];
            }
        }

        return $successful;
    }

    /**
     * Получить только неудачные запросы из all-settled результатов
     * 
     * @param array $settledResults Результаты executeAllSettled
     * @return array<string, array{error: string, status?: int}>
     */
    public function getFailed(array $settledResults): array
    {
        $failed = [];

        foreach ($settledResults as $key => $result) {
            if ($result['success'] === false) {
                $failed[$key] = [
                    'error' => $result['error'] ?? 'Unknown error',
                    'status' => $result['status'] ?? null,
                ];
            }
        }

        return $failed;
    }

    /**
     * Проверить, все ли запросы успешны
     * 
     * @param array $settledResults Результаты executeAllSettled
     * @return bool
     */
    public function allSuccessful(array $settledResults): bool
    {
        foreach ($settledResults as $result) {
            if ($result['success'] === false) {
                return false;
            }
        }

        return true;
    }
}
