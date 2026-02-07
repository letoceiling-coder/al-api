<?php

namespace App\Services\TrendAgent;

use App\Services\TrendAgent\TrendSsoApiAuth;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Клиент для работы с TrendAgent API
 * Использует TrendSsoApiAuth для авторизации и запросов к внешнему API
 */
class TrendAgentApiClient
{
    protected TrendSsoApiAuth $auth;
    protected string $phone;
    protected string $password;
    protected bool $authenticated = false;

    public function __construct()
    {
        // Получаем данные из .env или конфигурации
        $this->phone = env('TRENDAGENT_PHONE', '+7 999 637 11 82');
        $this->password = env('TRENDAGENT_PASSWORD', 'Tanya123qwe');
        $this->auth = new TrendSsoApiAuth();
    }

    /**
     * Аутентификация
     */
    public function authenticate(): array
    {
        try {
            Log::info('TrendAgentApiClient: Начинаю аутентификацию');
            
            $authData = $this->auth->authenticate($this->phone, $this->password);
            
            if (!($authData['authenticated'] ?? false)) {
                throw new Exception('Авторизация не удалась');
            }
            
            $this->authenticated = true;
            
            Log::info('TrendAgentApiClient: Аутентификация успешна');
            
            return [
                'success' => true,
                'message' => 'Аутентификация успешна',
                'token' => $authData['token'] ?? null,
            ];
            
        } catch (Exception $e) {
            Log::error('TrendAgentApiClient: Ошибка аутентификации', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Получить список городов
     */
    public function getCities(): array
    {
        $this->ensureAuthenticated();
        
        try {
            // Используем метод TrendSsoApiAuth
            $result = $this->auth->sendRequest('cities/', []);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? [],
            ];
            
        } catch (Exception $e) {
            Log::error('TrendAgentApiClient: Ошибка получения городов', [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить список комплексов/объектов
     */
    public function getObjectsList(string $city = 'spb', ?string $objectType = null, int $count = 100, int $offset = 0): array
    {
        $this->ensureAuthenticated();
        
        try {
            $params = [
                'city' => $this->getCityId($city),
                'count' => $count,
                'offset' => $offset,
                'show_type' => 'list',
            ];
            
            if ($objectType) {
                $params['object_type'] = $objectType;
            }
            
            $result = $this->auth->getBlocksSearch($params);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? [],
                'total' => $result['blocks_count'] ?? 0,
            ];
            
        } catch (Exception $e) {
            Log::error('TrendAgentApiClient: Ошибка получения списка объектов', [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить список квартир
     */
    public function getApartments(array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $defaultParams = [
                'city' => $this->getCityId($params['city'] ?? 'spb'),
                'count' => $params['count'] ?? 100,
                'offset' => $params['offset'] ?? 0,
                'show_type' => 'list',
            ];
            
            $apiParams = array_merge($defaultParams, $params);
            $result = $this->auth->getBlocksSearch($apiParams);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? [],
                'total' => $result['apartments_count'] ?? $result['blocks_count'] ?? 0,
            ];
            
        } catch (Exception $e) {
            Log::error('TrendAgentApiClient: Ошибка получения квартир', [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить детали комплекса/объекта
     */
    public function getApartmentDetails(string $id, array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $result = $this->auth->sendRequest("blocks/{$id}/", $params);
            
            return [
                'success' => true,
                'data' => $result,
            ];
            
        } catch (Exception $e) {
            Log::error("TrendAgentApiClient: Ошибка получения деталей комплекса {$id}", [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить детали конкретной квартиры
     */
    public function getApartmentFlatDetails(string $blockId, string $flatId, array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $result = $this->auth->sendRequest("blocks/{$blockId}/flat/{$flatId}/", $params);
            
            return [
                'success' => true,
                'data' => $result,
            ];
            
        } catch (Exception $e) {
            Log::error("TrendAgentApiClient: Ошибка получения деталей квартиры {$flatId} в комплексе {$blockId}", [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить корпуса комплекса для шахматки
     */
    public function getApartmentCheckerboardBuildings(string $id, array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $result = $this->auth->sendRequest("blocks/{$id}/checkerboard/buildings/", $params);
            
            return [
                'success' => true,
                'data' => $result,
            ];
            
        } catch (Exception $e) {
            Log::error("TrendAgentApiClient: Ошибка получения корпусов для шахматки {$id}", [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить квартиры для шахматки
     */
    public function getApartmentCheckerboardApartments(string $id, array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $result = $this->auth->sendRequest("blocks/{$id}/checkerboard/apartments/", $params);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? $result,
                'total' => $result['total'] ?? count($result['data'] ?? []),
            ];
            
        } catch (Exception $e) {
            Log::error("TrendAgentApiClient: Ошибка получения квартир для шахматки {$id}", [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить справочник планировок
     */
    public function getApartmentFloorPlanDirectory(string $id, array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $result = $this->auth->sendRequest("blocks/{$id}/floor-plan/directory/", $params);
            
            return [
                'success' => true,
                'data' => $result,
            ];
            
        } catch (Exception $e) {
            Log::error("TrendAgentApiClient: Ошибка получения справочника планировок {$id}", [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить поэтажный план
     */
    public function getApartmentFloorPlan(string $id, array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $result = $this->auth->sendRequest("blocks/{$id}/floor-plan/", $params);
            
            return [
                'success' => true,
                'data' => $result,
            ];
            
        } catch (Exception $e) {
            Log::error("TrendAgentApiClient: Ошибка получения поэтажного плана {$id}", [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить список паркингов
     */
    public function getParkings(array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $defaultParams = [
                'city' => $this->getCityId($params['city'] ?? 'spb'),
                'count' => $params['count'] ?? 100,
                'offset' => $params['offset'] ?? 0,
                'object_type' => 'parking',
            ];
            
            $apiParams = array_merge($defaultParams, $params);
            $result = $this->auth->getBlocksSearch($apiParams);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? [],
                'total' => $result['parkings_count'] ?? $result['blocks_count'] ?? 0,
            ];
            
        } catch (Exception $e) {
            Log::error('TrendAgentApiClient: Ошибка получения паркингов', [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить детали парковки
     */
    public function getParkingDetails(string $id, array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $result = $this->auth->sendRequest("parkings/{$id}/", $params);
            
            return [
                'success' => true,
                'data' => $result,
            ];
            
        } catch (Exception $e) {
            Log::error("TrendAgentApiClient: Ошибка получения деталей парковки {$id}", [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить места парковки
     */
    public function getParkingPlaces(string $id, array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $result = $this->auth->sendRequest("parkings/{$id}/places/", $params);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? $result,
                'total' => $result['total'] ?? count($result['data'] ?? []),
            ];
            
        } catch (Exception $e) {
            Log::error("TrendAgentApiClient: Ошибка получения мест парковки {$id}", [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить список домов
     */
    public function getHouses(array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $defaultParams = [
                'city' => $this->getCityId($params['city'] ?? 'spb'),
                'count' => $params['count'] ?? 100,
                'offset' => $params['offset'] ?? 0,
                'object_type' => 'house',
            ];
            
            $apiParams = array_merge($defaultParams, $params);
            $result = $this->auth->getBlocksSearch($apiParams);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? [],
                'total' => $result['houses_count'] ?? $result['blocks_count'] ?? 0,
            ];
            
        } catch (Exception $e) {
            Log::error('TrendAgentApiClient: Ошибка получения домов', [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить детали дома
     */
    public function getHouseDetails(string $id, array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $result = $this->auth->sendRequest("houses/{$id}/", $params);
            
            return [
                'success' => true,
                'data' => $result,
            ];
            
        } catch (Exception $e) {
            Log::error("TrendAgentApiClient: Ошибка получения деталей дома {$id}", [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить список участков
     */
    public function getPlots(array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $defaultParams = [
                'city' => $this->getCityId($params['city'] ?? 'spb'),
                'count' => $params['count'] ?? 100,
                'offset' => $params['offset'] ?? 0,
                'object_type' => 'land_plot',
            ];
            
            $apiParams = array_merge($defaultParams, $params);
            $result = $this->auth->getBlocksSearch($apiParams);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? [],
                'total' => $result['plots_count'] ?? $result['blocks_count'] ?? 0,
            ];
            
        } catch (Exception $e) {
            Log::error('TrendAgentApiClient: Ошибка получения участков', [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить детали поселка
     */
    public function getPlotDetails(string $id, array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $result = $this->auth->sendRequest("land_plots/{$id}/", $params);
            
            return [
                'success' => true,
                'data' => $result,
            ];
            
        } catch (Exception $e) {
            Log::error("TrendAgentApiClient: Ошибка получения деталей поселка {$id}", [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить детали конкретного участка
     */
    public function getPlotSpecificDetails(string $plotId, string $specificPlotId, array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $result = $this->auth->sendRequest("land_plots/{$plotId}/plot/{$specificPlotId}/", $params);
            
            return [
                'success' => true,
                'data' => $result,
            ];
            
        } catch (Exception $e) {
            Log::error("TrendAgentApiClient: Ошибка получения деталей участка {$specificPlotId} в поселке {$plotId}", [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить список коммерческой недвижимости
     */
    public function getCommercial(array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $defaultParams = [
                'city' => $this->getCityId($params['city'] ?? 'spb'),
                'count' => $params['count'] ?? 100,
                'offset' => $params['offset'] ?? 0,
                'object_type' => 'commercial',
            ];
            
            $apiParams = array_merge($defaultParams, $params);
            $result = $this->auth->getBlocksSearch($apiParams);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? [],
                'total' => $result['commercial_count'] ?? $result['blocks_count'] ?? 0,
            ];
            
        } catch (Exception $e) {
            Log::error('TrendAgentApiClient: Ошибка получения коммерческой недвижимости', [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить детали коммерческой недвижимости
     */
    public function getCommercialDetails(string $id, array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $result = $this->auth->sendRequest("commercial/{$id}/", $params);
            
            return [
                'success' => true,
                'data' => $result,
            ];
            
        } catch (Exception $e) {
            Log::error("TrendAgentApiClient: Ошибка получения деталей коммерческой недвижимости {$id}", [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Проверка аутентификации
     */
    protected function ensureAuthenticated(): void
    {
        // Просто проверяем флаг, т.к. аутентификация уже выполнена в ParseCommand
        if (!$this->authenticated) {
            // Проверяем, возможно TrendSsoApiAuth уже аутентифицирован
            if ($this->auth->isAuthenticated()) {
                $this->authenticated = true;
                return;
            }
            
            // Если нет - выполняем аутентификацию
            $result = $this->authenticate();
            if (!$result['success']) {
                throw new Exception('Требуется аутентификация: ' . ($result['message'] ?? 'Unknown error'));
            }
        }
    }

    /**
     * Получить ID города по коду
     */
    protected function getCityId(string $cityCode): string
    {
        $cityMap = [
            'spb' => '58c665588b6aa52311afa01b', // Санкт-Петербург
            'msk' => '58c665598b6aa55612afa068', // Москва
            'ekb' => '58c665598b6aa52e0fafa05e', // Екатеринбург
            'nsk' => '58c665598b6aa53611afa072', // Новосибирск
        ];
        
        return $cityMap[$cityCode] ?? $cityMap['spb'];
    }
}
