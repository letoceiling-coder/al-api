<?php

namespace App\Http\Controllers\TrendAgent;

use App\Http\Controllers\Controller;
use App\Services\TrendAgent\TrendSsoApiAuth;
use App\Services\TrendAgent\CityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrendSsoController extends Controller
{
    /**
     * Авторизация через Trend SSO
     * Использует TrendSsoApiAuth для прямой авторизации через API
     */
    public function authenticate(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
            'login_url' => 'nullable|url',
        ]);

        try {
            $phone = $request->input('phone');
            $password = $request->input('password');

            // Используем TrendSsoApiAuth для прямой авторизации через API
            $apiAuth = new TrendSsoApiAuth();
            $authData = $apiAuth->authenticate($phone, $password);

            if (!($authData['authenticated'] ?? false)) {
                throw new \Exception('Авторизация не удалась. Проверьте телефон и пароль.');
            }

            // Получаем токен
            $authToken = $apiAuth->getAuthToken();
            
            // Формируем ответ с токеном
            $responseData = [
                'authenticated' => true,
                'tokens' => $authData['tokens'] ?? [],
                'auth_token' => $authToken, // Основной токен для использования в запросах
                'cookies' => $authData['cookies'] ?? [],
                'headers' => $authData['headers'] ?? [],
                'session_id' => $authData['session_id'] ?? null,
                'user' => $authData['user'] ?? null,
                'timestamp' => $authData['timestamp'] ?? now()->toIso8601String(),
            ];

            Log::info('Авторизация Trend SSO успешна', [
                'has_token' => !empty($authToken),
                'token_preview' => $authToken ? substr($authToken, 0, 50) . '...' : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Авторизация успешна',
                'data' => $responseData,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Ошибка авторизации Trend SSO', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получение контента со страницы /objects/list
     */
    public function getObjectsList(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
            'url' => 'nullable|url',
            'parse' => 'nullable|boolean',
            'count' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string',
            'sort_order' => 'nullable|string|in:asc,desc',
            'room' => 'nullable|array',
            'room.*' => 'nullable|integer|min:1',
            'object_type' => 'nullable|string|in:apartments,parking,houses,plots,commercial,contractors',
            'city' => 'nullable|string',
            // Расширенные фильтры
            'price_from' => 'nullable|integer|min:0',
            'price_to' => 'nullable|integer|min:0',
            'area_from' => 'nullable|numeric|min:0',
            'area_to' => 'nullable|numeric|min:0',
            'floor_from' => 'nullable|integer|min:0',
            'floor_to' => 'nullable|integer|min:0',
            'finishing_types' => 'nullable|string',
            'parking_type' => 'nullable|string',
            'purpose' => 'nullable|string',
            'text' => 'nullable|string|max:255',
            'deadline_key' => 'nullable|string',
        ]);

        try {
            $phone = $request->input('phone');
            $password = $request->input('password');
            $targetUrl = $request->input('url', 'https://spb.trendagent.ru/objects/list');
            $shouldParse = $request->input('parse', true);

            // Авторизация через API (быстрее и надежнее)
            $apiAuth = new TrendSsoApiAuth();
            $authData = $apiAuth->authenticate($phone, $password);

            if (!$authData['authenticated'] ?? false) {
                throw new \Exception('Авторизация не удалась');
            }

            // Используем прямой API запрос вместо парсинга HTML
            // Контент генерируется через JavaScript, поэтому используем API /v4_29/blocks/search/
            $apiParams = [];
            
            // Извлекаем параметры из URL, если они есть
            $parsedUrl = parse_url($targetUrl);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $urlParams);
                if (isset($urlParams['lang'])) {
                    $apiParams['lang'] = $urlParams['lang'];
                }
            }
            
            // Параметры пагинации и количества
            $count = (int) $request->input('count', 20);
            $offset = (int) $request->input('offset', 0);
            $page = (int) $request->input('page', 1);
            
            // Ограничиваем максимальное количество (защита от слишком больших запросов)
            $maxCount = 100;
            if ($count > $maxCount) {
                $count = $maxCount;
            }
            if ($count < 1) {
                $count = 20;
            }
            
            // Вычисляем offset из page, если передан page
            if ($page > 1 && $offset === 0) {
                $offset = ($page - 1) * $count;
            }
            
            $apiParams['count'] = $count;
            $apiParams['offset'] = $offset;
            
            // Параметр города
            if ($request->has('city') && !empty($request->input('city'))) {
                $apiParams['city'] = $request->input('city');
            }
            
            // Дополнительные параметры сортировки
            if ($request->has('sort')) {
                $apiParams['sort'] = $request->input('sort');
            }
            if ($request->has('sort_order')) {
                $apiParams['sort_order'] = $request->input('sort_order');
            }
            
            // Параметр room для фильтрации по типу объекта
            // room=30 - Коттеджи (дома с участками)
            // room=40 - Таунхаусы (дома с участками)
            // room может быть массивом для нескольких типов
            if ($request->has('room')) {
                $roomValue = $request->input('room');
                if (is_array($roomValue)) {
                    $apiParams['room'] = array_filter($roomValue, function($r) {
                        return is_numeric($r) && $r > 0;
                    });
                } elseif (is_numeric($roomValue) && $roomValue > 0) {
                    $apiParams['room'] = [(int)$roomValue];
                }
            }
            
            // Тип объекта (для удобства)
            // ВАЖНО: Если object_type пустая строка или не передан, это означает БЛОКИ (комплексы)
            $objectTypeInput = $request->input('object_type');
            $objectType = ($objectTypeInput === '' || $objectTypeInput === null) ? 'blocks' : ($objectTypeInput ?: 'apartments');
            
            // Маппинг типов объектов на коды room
            $roomTypeMap = [
                'apartments' => [], // Квартиры - без фильтра room
                'parking' => [], // Паркинг (нужно уточнить код)
                'houses' => [30, 40], // Дома с участками: 30=Коттеджи, 40=Таунхаусы
                'plots' => [], // Участки (нужно уточнить код)
                'commercial' => [], // Коммерция (нужно уточнить код)
                'blocks' => [], // Блоки (комплексы) - без фильтра room
            ];
            
            // Если передан object_type и не передан room, используем маппинг
            if (!isset($apiParams['room']) && isset($roomTypeMap[$objectType])) {
                $mappedRooms = $roomTypeMap[$objectType];
                if (!empty($mappedRooms)) {
                    $apiParams['room'] = $mappedRooms;
                }
            }
            
            // Расширенные фильтры
            if ($request->has('price_from')) {
                $apiParams['price_from'] = (int) $request->input('price_from');
            }
            if ($request->has('price_to')) {
                $apiParams['price_to'] = (int) $request->input('price_to');
            }
            if ($request->has('area_from')) {
                $apiParams['area_from'] = (float) $request->input('area_from');
            }
            if ($request->has('area_to')) {
                $apiParams['area_to'] = (float) $request->input('area_to');
            }
            if ($request->has('floor_from')) {
                $apiParams['floor_from'] = (int) $request->input('floor_from');
            }
            if ($request->has('floor_to')) {
                $apiParams['floor_to'] = (int) $request->input('floor_to');
            }
            if ($request->has('finishing_types')) {
                $apiParams['finishing_types'] = $request->input('finishing_types');
            }
            if ($request->has('parking_type')) {
                $apiParams['parking_type'] = $request->input('parking_type');
            }
            if ($request->has('purpose')) {
                $apiParams['purpose'] = $request->input('purpose');
            }
            if ($request->has('text')) {
                $apiParams['text'] = $request->input('text');
            }
            if ($request->has('deadline_key')) {
                $apiParams['deadline_key'] = $request->input('deadline_key');
            }
            
            // Выбираем правильный метод API в зависимости от типа объекта
            if ($objectType === 'parking') {
                // Для паркинга используем отдельный API
                $apiData = $apiAuth->getParkingsSearch($apiParams);
            } elseif ($objectType === 'plots') {
                // Для участков используем отдельный API
                $apiData = $apiAuth->getPlotsSearch($apiParams);
            } elseif ($objectType === 'commercial') {
                // Для коммерции используем отдельный API
                $apiData = $apiAuth->getCommercialSearch($apiParams);
            } elseif ($objectType === 'contractors') {
                // Для подрядчиков используем отдельный API
                $apiData = $apiAuth->getContractorsSearch($apiParams);
            } elseif ($objectType === 'apartments') {
                // Для квартир используем getApartmentsSearch
                $apiData = $apiAuth->getApartmentsSearch($apiParams);
            } else {
                // Для блоков (blocks) и остальных типов используем getBlocksSearch
                $apiData = $apiAuth->getBlocksSearch($apiParams);
            }
            
            // Определяем, есть ли еще данные
            // Если вернулось меньше объектов, чем запрошено, значит это последняя страница
            $returnedCount = count($apiData['data'] ?? []);
            $hasMore = $returnedCount >= $count;
            
            // Определяем API endpoint в зависимости от типа объекта
            $apiEndpoint = match($objectType) {
                'parking' => 'https://parkings.trendagent.ru/search/blocks',
                'plots' => 'https://house-api.trendagent.ru/v1/search/villages',
                'commercial' => 'https://commerce.trendagent.ru/search/blocks',
                'contractors' => 'https://house-api.trendagent.ru/v1/projects/search',
                default => 'https://api.trendagent.ru/v4_29/blocks/search/',
            };
            
            $result = [
                'success' => true,
                'url' => $targetUrl,
                'source' => 'api',
                'api_endpoint' => $apiEndpoint,
                'object_type' => $objectType,
                'room_filter' => $apiParams['room'] ?? null,
                'pagination' => [
                    'count' => $count,
                    'offset' => $offset,
                    'page' => $page,
                    'has_more' => $hasMore,
                    'returned_count' => $returnedCount,
                ],
            ];

            // Добавляем данные из API (разная структура для разных типов)
            if ($objectType === 'parking') {
                $result['data'] = [
                    'blocks_count' => $apiData['blocks_count'] ?? 0,
                    'places_count' => $apiData['places_count'] ?? 0,
                    'booked_places_count' => $apiData['booked_places_count'] ?? 0,
                    'prelaunches_count' => 0,
                    'apartments_count' => 0,
                    'booked_apartments_count' => 0,
                    'view_apartments_count' => 0,
                    'objects_count' => $apiData['total'] ?? 0,
                    'objects' => $apiData['data'] ?? [],
                ];
            } elseif ($objectType === 'plots') {
                $result['data'] = [
                    'total_count' => $apiData['total_count'] ?? 0,
                    'result_count' => $apiData['result_count'] ?? 0,
                    'plots_count' => $apiData['plots_count'] ?? 0,
                    'blocks_count' => 0,
                    'prelaunches_count' => 0,
                    'apartments_count' => 0,
                    'booked_apartments_count' => 0,
                    'view_apartments_count' => 0,
                    'places_count' => 0,
                    'booked_places_count' => 0,
                    'objects_count' => $apiData['total'] ?? 0,
                    'objects' => $apiData['data'] ?? [],
                ];
            } elseif ($objectType === 'commercial') {
                $result['data'] = [
                    'blocks_count' => $apiData['blocks_count'] ?? 0,
                    'premises_count' => $apiData['premises_count'] ?? 0,
                    'booked_premises_count' => $apiData['booked_premises_count'] ?? 0,
                    'prelaunches_count' => 0,
                    'apartments_count' => 0,
                    'booked_apartments_count' => 0,
                    'view_apartments_count' => 0,
                    'places_count' => 0,
                    'booked_places_count' => 0,
                    'objects_count' => $apiData['total'] ?? 0,
                    'objects' => $apiData['data'] ?? [],
                ];
            } elseif ($objectType === 'contractors') {
                $result['data'] = [
                    'total_count' => $apiData['total_count'] ?? 0,
                    'contractors_count' => $apiData['total_count'] ?? 0,
                    'blocks_count' => 0,
                    'prelaunches_count' => 0,
                    'apartments_count' => 0,
                    'booked_apartments_count' => 0,
                    'view_apartments_count' => 0,
                    'places_count' => 0,
                    'booked_places_count' => 0,
                    'premises_count' => 0,
                    'plots_count' => 0,
                    'objects_count' => $apiData['total'] ?? 0,
                    'objects' => $apiData['data'] ?? [],
                ];
            } else {
                $result['data'] = [
                    'blocks_count' => $apiData['blocks_count'] ?? 0,
                    'prelaunches_count' => $apiData['prelaunches_count'] ?? 0,
                    'apartments_count' => $apiData['apartments_count'] ?? 0,
                    'booked_apartments_count' => $apiData['booked_apartments_count'] ?? 0,
                    'view_apartments_count' => $apiData['view_apartments_count'] ?? 0,
                    'places_count' => 0,
                    'booked_places_count' => 0,
                    'objects_count' => $apiData['total'] ?? 0,
                    'objects' => $apiData['data'] ?? [],
                ];
            }
            
            // Логируем структуру первого объекта для отладки (только структуру, без данных)
            if (!empty($apiData['data']) && is_array($apiData['data'])) {
                $firstObject = $apiData['data'][0];
                $structureSample = [];
                foreach ($firstObject as $key => $value) {
                    if (is_array($value)) {
                        $structureSample[$key] = array_keys($value);
                        // Для изображения показываем полную структуру
                        if ($key === 'image') {
                            $structureSample[$key] = $value;
                        }
                    } else {
                        $structureSample[$key] = gettype($value);
                    }
                }
                Log::info('Структура объекта из API (пример)', [
                    'structure' => $structureSample,
                    'image_url_example' => $firstObject['image']['url'] ?? null,
                ]);
            }

            return response()->json($result, 200);

        } catch (\Exception $e) {
            Log::error('Ошибка получения контента objects/list', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получение детальной информации об объекте
     */
    public function getBlockDetails(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
            'block_id' => 'nullable|string', // ID блока (24 символа hex)
            'block_guid' => 'nullable|string', // GUID блока (например, 'villa-marina')
            'options' => 'nullable|array', // Опции загрузки данных
            'object_type' => 'nullable|string|in:apartments,parking,houses,plots,commercial,contractors', // Тип объекта
        ]);

        try {
            $phone = $request->input('phone');
            $password = $request->input('password');
            $blockId = $request->input('block_id');
            $blockGuid = $request->input('block_guid');
            $options = $request->input('options', []);
            $objectType = $request->input('object_type', 'apartments');

            if (!$blockId && !$blockGuid) {
                throw new \Exception('Необходимо указать block_id или block_guid');
            }

            // Авторизация через API
            $apiAuth = new TrendSsoApiAuth();
            $authData = $apiAuth->authenticate($phone, $password);

            if (!($authData['authenticated'] ?? false)) {
                throw new \Exception('Авторизация не удалась');
            }

            // Для подрядчиков используем специальный метод
            if ($objectType === 'contractors') {
                $blockIdentifier = $blockGuid ?? $blockId;
                $fullData = $apiAuth->getContractorProjectDetails($blockIdentifier);
                
                return response()->json([
                    'success' => true,
                    'object_type' => 'contractors',
                    'data' => $fullData,
                ], 200);
            }

            // Для остальных типов используем стандартный метод
            $blockIdentifier = $blockId ?? $blockGuid;
            $fullData = $apiAuth->getBlockFullData($blockIdentifier, $options);

            return response()->json([
                'success' => true,
                'object_type' => $objectType,
                'data' => $fullData,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Ошибка получения детальной информации об объекте', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получение списка городов
     */
    public function getCities(Request $request)
    {
        try {
            $cities = CityService::getAllCities();
            
            // Преобразуем в формат для фронтенда
            $citiesList = [];
            foreach ($cities as $key => $city) {
                $citiesList[] = [
                    'id' => $city['id'],
                    'name' => $city['name'],
                    'key' => $key,
                    'base_url' => $city['base_url'] ?? null,
                    'subdomain' => $city['subdomain'] ?? null,
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $citiesList,
                'count' => count($citiesList),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Ошибка получения списка городов', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получение конкретных данных об объекте (unified, apartments, plans и т.д.)
     */
    public function getBlockData(Request $request, string $dataType)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
            'block_id' => 'required|string',
            'params' => 'nullable|array',
        ]);

        $allowedTypes = [
            'unified', 'buildings', 'apartments', 'plans', 'progress',
            'finishings', 'advantages', 'nearby_places', 'min_price',
            'videos', 'files'
        ];

        if (!in_array($dataType, $allowedTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Недопустимый тип данных. Доступные: ' . implode(', ', $allowedTypes),
            ], 400);
        }

        try {
            $phone = $request->input('phone');
            $password = $request->input('password');
            $blockId = $request->input('block_id');
            $params = $request->input('params', []);

            // Авторизация через API
            $apiAuth = new TrendSsoApiAuth();
            $authData = $apiAuth->authenticate($phone, $password);

            if (!($authData['authenticated'] ?? false)) {
                throw new \Exception('Авторизация не удалась');
            }

            // Вызываем соответствующий метод
            $methodName = 'getBlock' . ucfirst(str_replace('_', '', ucwords($dataType, '_')));
            
            if (!method_exists($apiAuth, $methodName)) {
                throw new \Exception("Метод {$methodName} не найден");
            }

            // Для progress может быть дополнительный параметр year
            if ($dataType === 'progress') {
                $year = $request->input('year');
                $result = $apiAuth->$methodName($blockId, $year, $params);
            } else {
                $result = $apiAuth->$methodName($blockId, $params);
            }

            return response()->json([
                'success' => true,
                'data_type' => $dataType,
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Ошибка получения данных объекта', [
                'data_type' => $dataType,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

