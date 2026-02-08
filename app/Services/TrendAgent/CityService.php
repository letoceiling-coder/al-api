<?php

namespace App\Services\TrendAgent;

use Illuminate\Support\Facades\Cache;

/**
 * Сервис для работы с городами TrendAgent
 * 
 * Предоставляет методы для получения информации о городах,
 * их ID, base URL и других параметрах
 */
class CityService
{
    /**
     * Список всех городов TrendAgent
     * 
     * @return array Массив городов с ключами: id, name, base_url, subdomain
     */
    public static function getAllCities(): array
    {
        return Cache::remember('trendagent_cities', 3600, function () {
            return [
                'msk' => [
                    'id' => '5a5cb42159042faa9a218d04',
                    'name' => 'Москва',
                    'base_url' => 'https://msk.trendagent.ru',
                    'subdomain' => 'msk',
                ],
                'spb' => [
                    'id' => '58c665588b6aa52311afa01b',
                    'name' => 'Санкт-Петербург',
                    'base_url' => 'https://spb.trendagent.ru',
                    'subdomain' => 'spb',
                ],
                'krd' => [
                    'id' => '604b5243f9760700074ac345',
                    'name' => 'Краснодарский край, Сочи, Республика Адыгея',
                    'base_url' => 'https://krasnodar.trendagent.ru',
                    'subdomain' => 'krasnodar',
                ],
                'rnd' => [
                    'id' => '61926fb5bb267a0008de132b',
                    'name' => 'Ростов-на-Дону',
                    'base_url' => 'https://rostov.trendagent.ru',
                    'subdomain' => 'rostov',
                ],
                'crimea' => [
                    'id' => '682700dd0e7daf77097d0779',
                    'name' => 'Крым',
                    'base_url' => 'https://crimea.trendagent.ru',
                    'subdomain' => 'crimea',
                ],
                'kzn' => [
                    'id' => '642157fca50429d21e3aa14f',
                    'name' => 'Казань',
                    'base_url' => 'https://kzn.trendagent.ru',
                    'subdomain' => 'kzn',
                ],
                'ufa' => [
                    'id' => '674eff862307c824cf56ced3',
                    'name' => 'Уфа',
                    'base_url' => 'https://ufa.trendagent.ru',
                    'subdomain' => 'ufa',
                ],
                'ekb' => [
                    'id' => '650974f78d34c0f790a012a9',
                    'name' => 'Екатеринбург',
                    'base_url' => 'https://ekb.trendagent.ru',
                    'subdomain' => 'ekb',
                ],
                'nsk' => [
                    'id' => '618120c1a56997000866c4d8',
                    'name' => 'Новосибирск',
                    'base_url' => 'https://nsk.trendagent.ru',
                    'subdomain' => 'nsk',
                ],
                'dubai' => [
                    'id' => '63d10e79a8975354f0d41c80',
                    'name' => 'Абу-Даби, Дубай, Рас-Эль-Хайма, Шарджа, Умм-эль-Кайвайн, Аджман',
                    'base_url' => 'https://trendagent.ae',
                    'subdomain' => 'dubai',
                ],
            ];
        });
    }

    /**
     * Получение информации о городе по ключу
     * 
     * @param string $key Ключ города (msk, spb, krd и т.д.)
     * @return array|null Массив с информацией о городе или null, если не найден
     */
    public static function getCityByKey(string $key): ?array
    {
        $cities = self::getAllCities();
        return $cities[$key] ?? null;
    }

    /**
     * Получение информации о городе по ID
     * 
     * @param string $id ID города (MongoDB ObjectId)
     * @return array|null Массив с информацией о городе или null, если не найден
     */
    public static function getCityById(string $id): ?array
    {
        $cities = self::getAllCities();
        
        foreach ($cities as $city) {
            if ($city['id'] === $id) {
                return $city;
            }
        }
        
        return null;
    }

    /**
     * Получение ключа города по ID
     * 
     * @param string $id ID города (MongoDB ObjectId)
     * @return string|null Ключ города (msk, spb и т.д.) или null, если не найден
     */
    public static function getCityKeyById(string $id): ?string
    {
        $cities = self::getAllCities();
        
        foreach ($cities as $key => $city) {
            if ($city['id'] === $id) {
                return $key;
            }
        }
        
        return null;
    }

    /**
     * Получение ключа города по subdomain или guid
     * 
     * @param string $subdomainOrGuid Subdomain (krasnodar) или guid (krasnodar)
     * @return string|null Ключ города (krd) или null, если не найден
     */
    public static function getCityKeyBySubdomain(string $subdomainOrGuid): ?string
    {
        $cities = self::getAllCities();
        
        // Маппинг subdomain/guid на ключ
        $subdomainMap = [
            'krasnodar' => 'krd',
            'rostovnadonu' => 'rnd',
            'rostov' => 'rnd',
        ];
        
        // Сначала проверяем маппинг
        if (isset($subdomainMap[$subdomainOrGuid])) {
            return $subdomainMap[$subdomainOrGuid];
        }
        
        // Затем ищем по subdomain в городах
        foreach ($cities as $key => $city) {
            if ($city['subdomain'] === $subdomainOrGuid || $key === $subdomainOrGuid) {
                return $key;
            }
        }
        
        return null;
    }

    /**
     * Получение ID города по ключу
     * 
     * @param string $key Ключ города
     * @return string|null ID города или null, если не найден
     */
    public static function getCityId(string $key): ?string
    {
        $city = self::getCityByKey($key);
        return $city['id'] ?? null;
    }

    /**
     * Получение base URL города по ключу
     * 
     * @param string $key Ключ города
     * @return string|null Base URL города или null, если не найден
     */
    public static function getCityBaseUrl(string $key): ?string
    {
        $city = self::getCityByKey($key);
        return $city['base_url'] ?? null;
    }

    /**
     * Получение названия города по ключу
     * 
     * @param string $key Ключ города
     * @return string|null Название города или null, если не найден
     */
    public static function getCityName(string $key): ?string
    {
        $city = self::getCityByKey($key);
        return $city['name'] ?? null;
    }

    /**
     * Проверка существования города по ключу
     * 
     * @param string $key Ключ города
     * @return bool
     */
    public static function cityExists(string $key): bool
    {
        return self::getCityByKey($key) !== null;
    }

    /**
     * Получение списка всех ключей городов
     * 
     * @return array Массив ключей городов
     */
    public static function getAllCityKeys(): array
    {
        return array_keys(self::getAllCities());
    }

    /**
     * Получение списка городов в формате для выбора (для форм)
     * 
     * @return array Массив в формате ['id' => 'name']
     */
    public static function getCitiesForSelect(): array
    {
        $cities = self::getAllCities();
        $result = [];
        
        foreach ($cities as $key => $city) {
            $result[$key] = $city['name'];
        }
        
        return $result;
    }

    /**
     * Очистка кэша городов
     * 
     * @return void
     */
    public static function clearCache(): void
    {
        Cache::forget('trendagent_cities');
    }
}



