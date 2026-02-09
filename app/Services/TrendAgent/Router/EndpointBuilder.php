<?php

namespace App\Services\TrendAgent\Router;

use App\Services\TrendAgent\Core\Contracts\ApiEndpoint;
use App\Services\TrendAgent\Auth\AuthTokenManager;
use App\Services\TrendAgent\Core\Errors\AuthExpiredError;
use Illuminate\Support\Facades\Log;

/**
 * Построитель URL для API endpoint'ов
 * 
 * Ответственность:
 * - Построение полного URL из ApiEndpoint конфигурации
 * - Подстановка параметров пути ({id}, {year})
 * - Добавление обязательных параметров (city, lang, auth_token)
 * - Формирование query string
 */
class EndpointBuilder
{
    public function __construct(
        private readonly AuthTokenManager $authTokenManager
    ) {}

    /**
     * Построить полный URL для endpoint'а
     * 
     * @param ApiEndpoint $endpoint Конфигурация endpoint'а
     * @param array $pathParams Параметры для подстановки в путь
     * @param array $queryParams Query параметры
     * @param string $city ID города
     * @param string $lang Язык
     * @return string Полный URL с query параметрами
     */
    public function build(
        ApiEndpoint $endpoint,
        array $pathParams = [],
        array $queryParams = [],
        string $city = '',
        string $lang = 'ru'
    ): string {
        // Базовый URL + path с подстановкой параметров
        $url = $endpoint->getBaseUrl() . $endpoint->buildPath($pathParams);

        // Добавить обязательные параметры
        $finalParams = $this->addMandatoryParams($queryParams, $city, $lang);

        // Добавить auth_token если требуется
        $finalParams = $this->addAuthToken($endpoint, $finalParams);

        // Построить query string
        if (!empty($finalParams)) {
            $url .= '?' . $this->buildQueryString($finalParams);
        }

        return $url;
    }

    /**
     * Добавить обязательные параметры (city, lang)
     */
    private function addMandatoryParams(array $params, string $city, string $lang): array
    {
        if (!empty($city)) {
            $params['city'] = $city;
        }

        $params['lang'] = $lang;

        return $params;
    }

    /**
     * Добавить auth_token если он в requiredParams endpoint'а
     */
    private function addAuthToken(ApiEndpoint $endpoint, array $params): array
    {
        if (!in_array('auth_token', $endpoint->requiredParams, true)) {
            return $params;
        }

        try {
            $params['auth_token'] = $this->authTokenManager->getValidToken();
        } catch (AuthExpiredError $e) {
            Log::warning('TrendAgent: auth_token не получен, запрос уйдёт без токена', [
                'error' => $e->getMessage(),
            ]);
            // Не добавляем пустой токен, пусть запрос уйдёт без него
        }

        return $params;
    }

    /**
     * Построить query string с поддержкой множественных значений
     * 
     * Пример: room=30&room=40
     */
    private function buildQueryString(array $params): string
    {
        $parts = [];

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                // Множественные значения
                foreach ($value as $v) {
                    $parts[] = urlencode($key) . '=' . urlencode((string) $v);
                }
            } else {
                $parts[] = urlencode($key) . '=' . urlencode((string) $value);
            }
        }

        return implode('&', $parts);
    }
}
