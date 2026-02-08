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
        $this->phone = env('TRENDAGENT_PHONE', '+79045393434');
        $this->password = env('TRENDAGENT_PASSWORD', 'nwBvh4q');
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

            $result = $this->executeWithRetry(function() use ($params) {
                return $this->auth->getBlocksSearch($params);
            });
            
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
     * Получить список квартир через правильный эндпоинт /v4_29/apartments/search/
     */
    public function getApartments(array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            // Получаем и удаляем city из params, чтобы использовать MongoID
            $cityCode = $params['city'] ?? 'spb';
            unset($params['city']);
            
            $defaultParams = [
                'city' => $this->getCityId($cityCode),
                'count' => $params['count'] ?? 50,
                'offset' => $params['offset'] ?? 0,
                'sort' => 'price',
                'sort_order' => 'asc',
            ];
            
            $apiParams = array_merge($defaultParams, $params);
            $result = $this->auth->getApartmentsSearch($apiParams);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? [],
                'total' => $result['total'] ?? 0,
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
            $result = $this->executeWithRetry(function() use ($id, $params) {
                return $this->auth->getBlockUnified($id, $params);
            });
            
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
            $result = $this->auth->getApartmentDetail($flatId, $blockId, $params);
            
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
            $result = $this->auth->getCheckerboardBuildings($id, $params);
            
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
     * Метод возвращает список всех квартир комплекса (не требует buildingId)
     */
    public function getApartmentCheckerboardApartments(string $id, array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            // Сначала получаем список зданий
            $buildings = $this->auth->getCheckerboardBuildings($id, $params);
            
            $allApartments = [];
            $buildingsList = $buildings['buildings'] ?? $buildings['data'] ?? [];
            
            // Для каждого здания получаем квартиры
            foreach ($buildingsList as $building) {
                $buildingId = $building['_id'] ?? $building['id'] ?? null;
                if ($buildingId) {
                    try {
                        $apartmentsData = $this->auth->getCheckerboardApartments($id, $buildingId, $params);
                        $apartments = $apartmentsData['apartments'] ?? $apartmentsData['data'] ?? [];
                        $allApartments = array_merge($allApartments, $apartments);
                    } catch (Exception $e) {
                        Log::warning("TrendAgentApiClient: Ошибка получения квартир для здания {$buildingId}", [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
            
            return [
                'success' => true,
                'data' => $allApartments,
                'total' => count($allApartments),
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
     * Получить список паркингов (ЖК с паркингами)
     * Для получения машиномест используйте getParkingPlaces()
     */
    public function getParkings(array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $cityCode = $params['city'] ?? 'spb';
            unset($params['city']);
            
            $defaultParams = [
                'city' => $this->getCityId($cityCode),
                'count' => $params['count'] ?? 20,
                'offset' => $params['offset'] ?? 0,
                'sort' => 'price',
                'sort_order' => 'asc',
            ];
            
            $apiParams = array_merge($defaultParams, $params);
            $result = $this->auth->getParkingsSearch($apiParams);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? [],
                'total' => $result['blocksCount'] ?? $result['total'] ?? 0,
                'places_count' => $result['placesCount'] ?? 0, // Общее количество машиномест
            ];
            
        } catch (Exception $e) {
            Log::error('TrendAgentApiClient: Ошибка получения паркингов', [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить список всех машиномест (places) через parkings-api
     */
    public function getParkingPlacesList(array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $cityCode = $params['city'] ?? 'spb';
            unset($params['city']);
            
            $defaultParams = [
                'city' => $this->getCityId($cityCode),
                'count' => $params['count'] ?? 50,
                'offset' => $params['offset'] ?? 0,
                'sort' => 'price',
                'sort_order' => 'asc',
                'number' => '',
            ];
            
            $apiParams = array_merge($defaultParams, $params);
            $result = $this->auth->getParkingPlacesSearch($apiParams);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? [],
                'total' => $result['total'] ?? 0,
            ];
            
        } catch (Exception $e) {
            Log::error('TrendAgentApiClient: Ошибка получения списка машиномест', [
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
     * Получить список домов через /v4_29/apartments/search/ с фильтром room=30&room=40
     */
    public function getHouses(array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $cityCode = $params['city'] ?? 'spb';
            unset($params['city']);
            
            $defaultParams = [
                'city' => $this->getCityId($cityCode),
                'count' => $params['count'] ?? 50,
                'offset' => $params['offset'] ?? 0,
                'sort' => 'price',
                'sort_order' => 'asc',
                'room' => [30, 40], // 30=Коттеджи, 40=Таунхаусы
            ];
            
            $apiParams = array_merge($defaultParams, $params);
            
            // Если room не передан, используем фильтр по умолчанию
            if (!isset($apiParams['room'])) {
                $apiParams['room'] = [30, 40];
            }
            
            $result = $this->auth->getApartmentsSearch($apiParams);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? [],
                'total' => $result['total'] ?? 0,
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
     * Получить список поселков (villages) через house-api.trendagent.ru/v1/search/villages
     * Возвращает поселки, в которых есть участки
     */
    public function getPlots(array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $cityCode = $params['city'] ?? 'spb';
            unset($params['city']);
            
            $defaultParams = [
                'city' => $this->getCityId($cityCode),
                'count' => $params['count'] ?? 20,
                'offset' => $params['offset'] ?? 0,
                'sort_type' => 'price',
                'sort_order' => 'asc',
            ];
            
            $apiParams = array_merge($defaultParams, $params);
            $result = $this->auth->getVillagesSearch($apiParams);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? [],
                'total' => $result['total'] ?? 0, // Количество поселков
                'plots_count' => $result['plots_count'] ?? 0, // Общее количество участков
            ];
            
        } catch (Exception $e) {
            Log::error('TrendAgentApiClient: Ошибка получения поселков', [
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
        
        return $this->executeWithRetry(function() use ($id, $params) {
            return $this->auth->getPlotDetail($id, $params);
        });
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
     * Получить список коммерческих помещений через commerce-api.trendagent.ru/search/premises
     */
    public function getCommercial(array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $cityCode = $params['city'] ?? 'spb';
            unset($params['city']);
            
            $defaultParams = [
                'city' => $this->getCityId($cityCode),
                'count' => $params['count'] ?? 50,
                'offset' => $params['offset'] ?? 0,
                'sort' => 'price',
                'sort_order' => 'asc',
                'number' => '',
            ];
            
            $apiParams = array_merge($defaultParams, $params);
            $result = $this->auth->getCommercePremisesSearch($apiParams);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? [],
                'total' => $result['total'] ?? 0, // Количество помещений
                'blocks_count' => $result['blocks_count'] ?? 0, // Количество ЖК с коммерцией
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
        
        return $this->executeWithRetry(function() use ($id, $params) {
            return $this->auth->getCommercePremiseDetail($id, $params);
        });
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
     * Выполнить запрос с автоматической переаутентификацией при 401
     */
    protected function executeWithRetry(callable $callback, int $maxRetries = 1): mixed
    {
        $attempts = 0;
        
        while ($attempts <= $maxRetries) {
            try {
                return $callback();
            } catch (Exception $e) {
                $attempts++;
                
                // Проверяем, является ли это 401 ошибкой
                if (str_contains($e->getMessage(), '401') && $attempts <= $maxRetries) {
                    Log::warning('TrendAgentApiClient: Получена ошибка 401, переаутентификация', [
                        'attempt' => $attempts,
                    ]);
                    
                    // Сбрасываем флаг аутентификации и переаутентифицируемся
                    $this->authenticated = false;
                    $this->ensureAuthenticated();
                    
                    // Повторяем запрос
                    continue;
                }
                
                // Если это не 401 или достигнут лимит попыток - пробрасываем исключение
                throw $e;
            }
        }
        
        throw new Exception('Не удалось выполнить запрос после переаутентификации');
    }
    
    /**
     * Получить список всех квартир комплекса
     */
    public function getBlockApartments(string $blockId, array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $result = $this->auth->getBlockApartments($blockId, $params);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? $result,
                'total' => $result['total'] ?? count($result['data'] ?? []),
            ];
            
        } catch (Exception $e) {
            Log::error("TrendAgentApiClient: Ошибка получения квартир комплекса {$blockId}", [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
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

    /**
     * Получить паркинги комплекса (машиноместа)
     */
    public function getBlockParkings(string $blockId, array $params = []): array
    {
        $this->ensureAuthenticated();
        
        return $this->executeWithRetry(function() use ($blockId, $params) {
            return $this->auth->getBlockParkings($blockId, $params);
        });
    }

    /**
     * Получить дома комплекса
     */
    public function getBlockHouses(string $blockId, array $params = []): array
    {
        $this->ensureAuthenticated();
        
        return $this->executeWithRetry(function() use ($blockId, $params) {
            return $this->auth->getBlockHouses($blockId, $params);
        });
    }

    /**
     * Получить коммерческие помещения комплекса
     */
    public function getBlockCommercial(string $blockId, array $params = []): array
    {
        $this->ensureAuthenticated();
        
        return $this->executeWithRetry(function() use ($blockId, $params) {
            return $this->auth->getBlockCommercial($blockId, $params);
        });
    }

    /**
     * Получить комплексы по типу комнат (room)
     */
    public function getComplexesByRoom(array $rooms, string $city = 'spb', int $count = 100, int $offset = 0): array
    {
        $this->ensureAuthenticated();
        
        try {
            $params = [
                'city' => $this->getCityId($city),
                'count' => $count,
                'offset' => $offset,
                'show_type' => 'list',
                'room' => $rooms,
            ];

            $result = $this->executeWithRetry(function() use ($params) {
                return $this->auth->getBlocksSearch($params);
            });
            
            return [
                'success' => true,
                'data' => $result['data'] ?? [],
                'total' => $result['blocks_count'] ?? 0,
            ];
            
        } catch (Exception $e) {
            Log::error('TrendAgentApiClient: Ошибка получения комплексов по room', [
                'rooms' => $rooms,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить токен авторизации
     */
    public function getAuthToken(): ?string
    {
        $this->ensureAuthenticated();
        return $this->auth->getAuthToken();
    }

    /**
     * Получить коммерческие помещения
     */
    public function getCommercePremises(array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $cityCode = $params['city'] ?? 'spb';
            unset($params['city']);
            
            $defaultParams = [
                'city' => $this->getCityId($cityCode),
                'count' => $params['count'] ?? 50,
                'offset' => $params['offset'] ?? 0,
            ];
            
            $apiParams = array_merge($defaultParams, $params);
            
            $result = $this->executeWithRetry(function() use ($apiParams) {
                return $this->auth->getCommercePremises($apiParams);
            });
            
            return [
                'success' => true,
                'data' => $result['data'] ?? [],
                'total' => $result['premises_count'] ?? $result['total'] ?? 0,
                'premises_count' => $result['premises_count'] ?? 0,
                'blocks_count' => $result['blocks_count'] ?? 0,
                'booked_premises_count' => $result['booked_premises_count'] ?? 0,
            ];
            
        } catch (Exception $e) {
            Log::error('TrendAgentApiClient: Ошибка получения помещений коммерции', [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить список подрядчиков (проектов домов)
     */
    public function getContractors(array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $cityCode = $params['city'] ?? 'spb';
            unset($params['city']);
            
            $defaultParams = [
                'city' => $this->getCityId($cityCode),
                'count' => $params['count'] ?? 100,
                'offset' => $params['offset'] ?? 0,
            ];
            
            $apiParams = array_merge($defaultParams, $params);
            $result = $this->auth->getContractorsSearch($apiParams);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? [],
                'total' => $result['total_count'] ?? $result['total'] ?? 0,
            ];
            
        } catch (Exception $e) {
            Log::error('TrendAgentApiClient: Ошибка получения подрядчиков', [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Получить детали проекта подрядчика
     */
    public function getContractorProjectDetails(string $id, array $params = []): array
    {
        $this->ensureAuthenticated();
        
        try {
            $result = $this->auth->getContractorProjectDetails($id, $params);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? $result,
            ];
            
        } catch (Exception $e) {
            Log::error("TrendAgentApiClient: Ошибка получения деталей проекта подрядчика {$id}", [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
}
