<?php

namespace App\Services\TrendAgent\Http;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

/**
 * Низкоуровневый HTTP клиент
 * 
 * ОГРАНИЧЕНИЯ:
 * ❌ НЕ знает про ObjectType
 * ❌ НЕ знает про фильтры
 * ❌ НЕ нормализует ответы
 * ❌ НЕ содержит бизнес-логики
 * ❌ НЕ обрабатывает retry
 * ❌ НЕ управляет auth_token
 * 
 * ТОЛЬКО:
 * ✅ Выполняет HTTP запросы
 * ✅ Возвращает сырой Response
 * ✅ Устанавливает базовые headers
 */
class HttpClient
{
    private const DEFAULT_TIMEOUT = 30;
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36';

    /**
     * Выполнить GET запрос
     * 
     * @param string $url Полный URL
     * @param array $headers Дополнительные headers
     * @param int $timeout Таймаут в секундах
     * @return Response
     */
    public function get(string $url, array $headers = [], int $timeout = self::DEFAULT_TIMEOUT): Response
    {
        return Http::withHeaders($this->buildHeaders($headers))
            ->timeout($timeout)
            ->get($url);
    }

    /**
     * Выполнить POST запрос
     * 
     * @param string $url Полный URL
     * @param array $data Данные для отправки
     * @param array $headers Дополнительные headers
     * @param int $timeout Таймаут в секундах
     * @return Response
     */
    public function post(string $url, array $data = [], array $headers = [], int $timeout = self::DEFAULT_TIMEOUT): Response
    {
        return Http::withHeaders($this->buildHeaders($headers))
            ->timeout($timeout)
            ->post($url, $data);
    }

    /**
     * Выполнить параллельные GET запросы
     * 
     * @param array<string, string> $requests Массив [key => url]
     * @param array $headers Дополнительные headers
     * @param int $timeout Таймаут в секундах
     * @return array<string, Response>
     */
    public function getParallel(array $requests, array $headers = [], int $timeout = self::DEFAULT_TIMEOUT): array
    {
        $responses = Http::pool(function ($pool) use ($requests, $headers, $timeout) {
            $results = [];
            
            foreach ($requests as $key => $url) {
                $results[$key] = $pool->withHeaders($this->buildHeaders($headers))
                    ->timeout($timeout)
                    ->get($url);
            }
            
            return $results;
        });

        return $responses;
    }

    /**
     * Построить финальные headers
     * 
     * Объединяет базовые и переданные headers
     */
    private function buildHeaders(array $customHeaders = []): array
    {
        $baseHeaders = [
            'User-Agent' => self::USER_AGENT,
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
        ];

        return array_merge($baseHeaders, $customHeaders);
    }
}
