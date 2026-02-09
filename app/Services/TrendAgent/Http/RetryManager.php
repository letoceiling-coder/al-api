<?php

namespace App\Services\TrendAgent\Http;

use App\Services\TrendAgent\Core\Errors\AuthExpiredError;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

/**
 * Менеджер повторных попыток для HTTP запросов
 * 
 * Ответственность:
 * - Retry логика для failed запросов
 * - Exponential backoff
 * - Определение retriable ошибок
 */
class RetryManager
{
    private const MAX_RETRIES = 3;
    private const INITIAL_DELAY_MS = 1000;
    private const MAX_DELAY_MS = 10000;

    /**
     * Выполнить запрос с retry логикой
     * 
     * @param callable $request Функция выполнения запроса
     * @param int $maxRetries Максимальное количество попыток
     * @return Response
     * @throws AuthExpiredError если все попытки неудачны
     */
    public function retry(callable $request, int $maxRetries = self::MAX_RETRIES): Response
    {
        $attempt = 0;
        $lastException = null;
        $response = null;

        while ($attempt < $maxRetries) {
            try {
                $response = $request();

                // Проверить, является ли ответ успешным
                if ($this->isSuccessful($response)) {
                    if ($attempt > 0) {
                        Log::info('RetryManager: Запрос успешен после retry', [
                            'attempt' => $attempt + 1,
                        ]);
                    }
                    return $response;
                }

                // Проверить, можно ли повторить
                if (!$this->isRetriable($response)) {
                    return $response;
                }

                $attempt++;

                if ($attempt < $maxRetries) {
                    $this->delay($attempt);
                }

            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt < $maxRetries && $this->isExceptionRetriable($e)) {
                    Log::warning('RetryManager: Исключение при запросе, повтор', [
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                    ]);
                    $this->delay($attempt);
                } else {
                    throw $e;
                }
            }
        }

        // Все попытки исчерпаны
        if ($lastException !== null) {
            throw $lastException;
        }

        return $response ?? throw new \RuntimeException('Retry failed without response');
    }

    /**
     * Проверить, успешен ли ответ
     */
    private function isSuccessful(Response $response): bool
    {
        return $response->successful();
    }

    /**
     * Проверить, можно ли повторить запрос на основе response
     */
    private function isRetriable(Response $response): bool
    {
        $status = $response->status();

        // 5xx ошибки — можно повторить
        if ($status >= 500 && $status < 600) {
            return true;
        }

        // 429 Too Many Requests — можно повторить
        if ($status === 429) {
            return true;
        }

        // 408 Request Timeout — можно повторить
        if ($status === 408) {
            return true;
        }

        // 401 Unauthorized — НЕ повторяем здесь, AuthTokenManager обработает
        return false;
    }

    /**
     * Проверить, можно ли повторить после exception
     */
    private function isExceptionRetriable(\Exception $exception): bool
    {
        // Network errors — повторяем
        if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
            return true;
        }

        // Timeout errors — повторяем
        if ($exception instanceof \Illuminate\Http\Client\RequestException) {
            return true;
        }

        return false;
    }

    /**
     * Задержка с exponential backoff
     */
    private function delay(int $attempt): void
    {
        $delayMs = min(
            self::INITIAL_DELAY_MS * pow(2, $attempt - 1),
            self::MAX_DELAY_MS
        );

        Log::debug('RetryManager: Задержка перед повтором', [
            'attempt' => $attempt,
            'delay_ms' => $delayMs,
        ]);

        usleep($delayMs * 1000);
    }
}
