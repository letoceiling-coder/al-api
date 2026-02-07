<?php

namespace App\Services\TrendAgent;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * TrendAgent API Client
 * 
 * Клиент для работы с API TrendAgent через прокси-сервер.
 * Использует внутренние Laravel маршруты /api/trendagent/v1/*
 */
class TrendAgentApiClient
{
    private string $baseUrl;
    private string $bearerToken;
    private string $phone;
    private string $password;
    private int $timeout;
    private int $retryTimes;
    private int $retryDelay;

    public function __construct()
    {
        $this->baseUrl = config('app.url') . '/api/trendagent/v1';
        $this->bearerToken = '8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF';
        $this->phone = '+79045393434';
        $this->password = 'nwBvh4q';
        $this->timeout = 120; // 2 минуты
        $this->retryTimes = 3;
        $this->retryDelay = 2000; // 2 секунды
    }

    /**
     * Аутентификация в TrendAgent
     */
    public function authenticate(string $city = 'spb'): array
    {
        return $this->post('/authenticate', [
            'phone' => $this->phone,
            'password' => $this->password,
            'city' => $city,
        ]);
    }

    /**
     * Получить список городов
     */
    public function getCities(): array
    {
        return $this->post('/cities', [
            'phone' => $this->phone,
            'password' => $this->password,
        ]);
    }

    /**
     * Получить список объектов (комплексов/блоков)
     */
    public function getObjectsList(string $city, ?string $objectType = null, int $count = 100, int $offset = 0): array
    {
        return $this->post('/objects/list', [
            'phone' => $this->phone,
            'password' => $this->password,
            'city' => $city,
            'object_type' => $objectType,
            'count' => $count,
            'offset' => $offset,
        ]);
    }

    /**
     * Получить список квартир
     */
    public function getApartments(string $city, array $filters = [], int $count = 100, int $offset = 0): array
    {
        $params = array_merge([
            'phone' => $this->phone,
            'password' => $this->password,
            'city' => $city,
            'count' => $count,
            'offset' => $offset,
        ], $filters);

        return $this->post('/apartments', $params);
    }

    /**
     * Получить детали комплекса/квартир
     */
    public function getApartmentDetails(string $id, array $options = []): array
    {
        $defaultOptions = [
            'unified' => true,
            'buildings' => true,
            'apartments' => true,
            'plans' => true,
            'progress' => true,
            'finishings' => true,
            'advantages' => true,
            'nearby_places' => true,
            'min_price' => true,
            'videos' => true,
            'files' => true,
        ];

        return $this->post("/apartments/{$id}", [
            'phone' => $this->phone,
            'password' => $this->password,
            'options' => array_merge($defaultOptions, $options),
        ]);
    }

    /**
     * Получить детали конкретной квартиры
     */
    public function getFlatDetails(string $complexId, string $apartmentId): array
    {
        return $this->post("/apartments/{$complexId}/flat/{$apartmentId}", [
            'phone' => $this->phone,
            'password' => $this->password,
        ]);
    }

    /**
     * Получить шахматку корпусов
     */
    public function getCheckerboardBuildings(string $id): array
    {
        return $this->post("/apartments/{$id}/checkerboard/buildings", [
            'phone' => $this->phone,
            'password' => $this->password,
        ]);
    }

    /**
     * Получить шахматку квартир
     */
    public function getCheckerboardApartments(string $id, array $filters = []): array
    {
        return $this->post("/apartments/{$id}/checkerboard/apartments", array_merge([
            'phone' => $this->phone,
            'password' => $this->password,
        ], $filters));
    }

    /**
     * Получить директорию поэтажных планов
     */
    public function getFloorPlanDirectory(string $id): array
    {
        return $this->post("/apartments/{$id}/floor-plan/directory", [
            'phone' => $this->phone,
            'password' => $this->password,
        ]);
    }

    /**
     * Получить поэтажный план
     */
    public function getFloorPlan(string $id, array $params = []): array
    {
        return $this->post("/apartments/{$id}/floor-plan", array_merge([
            'phone' => $this->phone,
            'password' => $this->password,
        ], $params));
    }

    /**
     * Получить список паркингов
     */
    public function getParkings(string $city, array $filters = [], int $count = 100, int $offset = 0): array
    {
        return $this->post('/parkings', array_merge([
            'phone' => $this->phone,
            'password' => $this->password,
            'city' => $city,
            'count' => $count,
            'offset' => $offset,
        ], $filters));
    }

    /**
     * Получить детали паркинга
     */
    public function getParkingDetails(string $id): array
    {
        return $this->post("/parkings/{$id}", [
            'phone' => $this->phone,
            'password' => $this->password,
        ]);
    }

    /**
     * Получить места в паркинге
     */
    public function getParkingPlaces(string $id, array $filters = []): array
    {
        return $this->post("/parkings/{$id}/places", array_merge([
            'phone' => $this->phone,
            'password' => $this->password,
        ], $filters));
    }

    /**
     * Получить список домов
     */
    public function getHouses(string $city, array $filters = [], int $count = 100, int $offset = 0): array
    {
        return $this->post('/houses', array_merge([
            'phone' => $this->phone,
            'password' => $this->password,
            'city' => $city,
            'count' => $count,
            'offset' => $offset,
        ], $filters));
    }

    /**
     * Получить детали дома
     */
    public function getHouseDetails(string $id): array
    {
        return $this->post("/houses/{$id}", [
            'phone' => $this->phone,
            'password' => $this->password,
        ]);
    }

    /**
     * Получить список участков
     */
    public function getPlots(string $city, array $filters = [], int $count = 100, int $offset = 0): array
    {
        return $this->post('/plots', array_merge([
            'phone' => $this->phone,
            'password' => $this->password,
            'city' => $city,
            'count' => $count,
            'offset' => $offset,
        ], $filters));
    }

    /**
     * Получить детали поселка
     */
    public function getPlotDetails(string $id): array
    {
        return $this->post("/plots/{$id}", [
            'phone' => $this->phone,
            'password' => $this->password,
        ]);
    }

    /**
     * Получить детали конкретного участка
     */
    public function getSpecificPlotDetails(string $settlementId, string $plotId): array
    {
        return $this->post("/plots/{$settlementId}/plot/{$plotId}", [
            'phone' => $this->phone,
            'password' => $this->password,
        ]);
    }

    /**
     * Получить список коммерческой недвижимости
     */
    public function getCommercial(string $city, array $filters = [], int $count = 100, int $offset = 0): array
    {
        return $this->post('/commercial', array_merge([
            'phone' => $this->phone,
            'password' => $this->password,
            'city' => $city,
            'count' => $count,
            'offset' => $offset,
        ], $filters));
    }

    /**
     * Получить детали коммерческого объекта
     */
    public function getCommercialDetails(string $id): array
    {
        return $this->post("/commercial/{$id}", [
            'phone' => $this->phone,
            'password' => $this->password,
        ]);
    }

    /**
     * Выполнить POST запрос к API
     */
    private function post(string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;
        
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->bearerToken}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->retry($this->retryTimes, $this->retryDelay, function ($exception) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException;
            })
            ->post($url, $data);

            if ($response->failed()) {
                Log::error("TrendAgent API request failed", [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new Exception("API request failed with status {$response->status()}");
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error("TrendAgent API error", [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Установить таймаут запросов
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Установить количество попыток retry
     */
    public function setRetryTimes(int $times): self
    {
        $this->retryTimes = $times;
        return $this;
    }

    /**
     * Установить задержку между retry
     */
    public function setRetryDelay(int $delay): self
    {
        $this->retryDelay = $delay;
        return $this;
    }
}
