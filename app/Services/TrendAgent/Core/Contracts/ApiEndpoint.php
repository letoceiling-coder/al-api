<?php

namespace App\Services\TrendAgent\Core\Contracts;

/**
 * Описание одного API endpoint'а
 * 
 * Хранит:
 * - URL pattern
 * - HTTP метод
 * - Обязательные и опциональные параметры
 */
class ApiEndpoint
{
    /**
     * @param string $domain Домен API (api.trendagent.ru, parkings-api.trendagent.ru, и т.д.)
     * @param string|null $version Версия API (v4_29, v1) или null
     * @param string $path Путь endpoint'а (/blocks/search/, /apartments/{id}/)
     * @param string $method HTTP метод (GET, POST)
     * @param array<string> $requiredParams Обязательные параметры
     * @param array<string> $optionalParams Опциональные параметры
     * @param array<string> $pathParams Параметры в пути ({id}, {year}, и т.д.)
     */
    public function __construct(
        public readonly string $domain,
        public readonly ?string $version,
        public readonly string $path,
        public readonly string $method = 'GET',
        public readonly array $requiredParams = [],
        public readonly array $optionalParams = [],
        public readonly array $pathParams = []
    ) {}

    /**
     * Получить полный базовый URL
     * 
     * Например: https://api.trendagent.ru/v4_29
     */
    public function getBaseUrl(): string
    {
        $url = "https://{$this->domain}";
        
        if ($this->version !== null) {
            $url .= "/{$this->version}";
        }
        
        return $url;
    }

    /**
     * Получить полный path с подставленными параметрами
     * 
     * @param array $pathParams Значения для параметров пути
     */
    public function buildPath(array $pathParams = []): string
    {
        $path = $this->path;
        
        foreach ($pathParams as $key => $value) {
            $path = str_replace("{{$key}}", (string) $value, $path);
        }
        
        return $path;
    }
}
