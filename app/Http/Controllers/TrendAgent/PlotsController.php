<?php

namespace App\Http\Controllers\TrendAgent;

use App\Services\TrendAgent\TrendSsoApiAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PlotsController
{
    /**
     * Получение списка участков с фильтрами
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if (config('trendagent.data_source') === 'db') {
            return app(\App\Http\Controllers\TrendAgent\Db\PlotsDbController::class)->index($request);
        }

        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string',
            'city' => 'nullable|string',
            'count' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|in:price,deadline,name',
            'sort_order' => 'nullable|string|in:asc,desc',
            'price_from' => 'nullable|integer|min:0',
            'price_to' => 'nullable|integer|min:0',
            'area_from' => 'nullable|numeric|min:0',
            'area_to' => 'nullable|numeric|min:0',
            'text' => 'nullable|string|max:255',
            'subway' => 'nullable|string',
            'region' => 'nullable|string',
            'district' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $phone = $request->input('phone');
            $password = $request->input('password');

            // Авторизация через API
            $apiAuth = new TrendSsoApiAuth();
            $authData = $apiAuth->authenticate($phone, $password);

            if (!($authData['authenticated'] ?? false)) {
                throw new \Exception('Авторизация не удалась');
            }

            // Параметры пагинации
            $count = (int) $request->input('count', 20);
            $offset = (int) $request->input('offset', 0);
            $page = (int) $request->input('page', 1);

            if ($page > 1 && $offset === 0) {
                $offset = ($page - 1) * $count;
            }

            // Параметры для API
            $apiParams = [
                'count' => $count,
                'offset' => $offset,
            ];

            if ($request->has('city') && !empty($request->input('city'))) {
                $apiParams['city'] = $request->input('city');
            }

            if ($request->has('sort')) {
                $apiParams['sort'] = $request->input('sort');
            }
            if ($request->has('sort_order')) {
                $apiParams['sort_order'] = $request->input('sort_order');
            }

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
            if ($request->has('text')) {
                $apiParams['text'] = $request->input('text');
            }
            if ($request->has('subway')) {
                $apiParams['subway'] = $request->input('subway');
            }
            if ($request->has('region')) {
                $apiParams['region'] = $request->input('region');
            }
            if ($request->has('district')) {
                $apiParams['district'] = $request->input('district');
            }

            // Получаем данные через API
            // Для участков используем getVillagesSearch (как в старом проекте)
            // Это endpoint /v1/search/villages, который возвращает поселки с участками
            $apiData = $apiAuth->getVillagesSearch($apiParams);

            if (!($apiData['success'] ?? false)) {
                throw new \Exception('Ошибка при получении данных из API');
            }

            // Формируем ответ
            $totalCount = $apiData['blocks_count'] ?? $apiData['total'] ?? 0;
            $returnedCount = count($apiData['data'] ?? []);
            
            // Логирование для отладки
            if ($returnedCount > 0) {
                $firstItem = $apiData['data'][0] ?? [];
                Log::info('PlotsController: First item structure', [
                    'has_name' => isset($firstItem['name']),
                    'name' => $firstItem['name'] ?? null,
                    'has_min_prices' => isset($firstItem['min_prices']),
                    'min_prices_count' => is_array($firstItem['min_prices'] ?? null) ? count($firstItem['min_prices']) : 0,
                    'has_images' => isset($firstItem['images']),
                    'images_count' => is_array($firstItem['images'] ?? null) ? count($firstItem['images']) : 0,
                    'keys' => array_keys($firstItem),
                ]);
            }
            
            $result = [
                'success' => true,
                'total_count' => $totalCount,
                'data' => [
                    'blocks_count' => $apiData['blocks_count'] ?? 0,
                    'objects_count' => $returnedCount,
                    'objects' => $apiData['data'] ?? [],
                ],
                'pagination' => [
                    'count' => $count,
                    'offset' => $offset,
                    'page' => $page,
                    'has_more' => $returnedCount >= $count && ($offset + $returnedCount) < $totalCount,
                    'returned_count' => $returnedCount,
                ],
            ];

            return response()->json($result, 200);

        } catch (\Exception $e) {
            Log::error('Ошибка получения списка участков', [
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
     * Получение детальной информации об участке
     * 
     * @param Request $request
     * @param string $id ID или GUID блока
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string',
            'options' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $phone = $request->input('phone');
            $password = $request->input('password');
            $requestOptions = $request->input('options', []);
            
            if (empty($requestOptions)) {
                $options = [
                    'unified' => true,
                    'buildings' => true,
                    'plans' => true,
                    'progress' => true,
                    'finishings' => true,
                    'advantages' => true,
                    'nearby_places' => true,
                    'min_price' => true,
                    'videos' => true,
                    'files' => true,
                ];
            } else {
                $options = array_merge([
                    'unified' => true,
                    'buildings' => true,
                    'plans' => true,
                    'progress' => true,
                    'finishings' => true,
                    'advantages' => true,
                    'nearby_places' => true,
                    'min_price' => true,
                    'videos' => true,
                    'files' => true,
                ], $requestOptions);
            }

            // Авторизация через API
            $apiAuth = new TrendSsoApiAuth();
            $authData = $apiAuth->authenticate($phone, $password);

            if (!($authData['authenticated'] ?? false)) {
                throw new \Exception('Авторизация не удалась');
            }

            // Для поселков (участков) unified endpoint возвращает другую структуру
            // Нужно получить данные из getVillagesSearch по ID или GUID
            $villageData = null;
            $isGuid = !preg_match('/^[a-f0-9]{24}$/i', $id);
            $villageId = $id;
            
            // Если это GUID, получаем ID через getBlockById
            if ($isGuid) {
                try {
                    $blockData = $apiAuth->getBlockById($id);
                    $villageId = $blockData['data']['_id'] ?? null;
                    if (!$villageId) {
                        throw new \Exception('Блок с GUID ' . $id . ' не найден');
                    }
                } catch (\Exception $e) {
                    Log::warning('Ошибка получения ID по GUID для поселка', [
                        'guid' => $id,
                        'error' => $e->getMessage(),
                    ]);
                    throw new \Exception('Не удалось получить ID поселка по GUID: ' . $e->getMessage());
                }
            }
            
            // Получаем данные поселка из списка villages
            try {
                $villageData = $apiAuth->getVillageById($villageId);
            } catch (\Exception $e) {
                Log::warning('Ошибка получения данных поселка из списка', [
                    'id' => $villageId,
                    'error' => $e->getMessage(),
                ]);
            }
            
            // Если нашли данные поселка, используем их как unified данные
            if ($villageData) {
                // Получаем дополнительные данные через getBlockFullData (buildings, progress, etc.)
                try {
                    $fullData = $apiAuth->getBlockFullData($villageId, $options);
                    
                    // Объединяем данные из списка поселков с unified данными
                    $unifiedDataFromApi = $fullData['data']['unified']['data'] ?? [];
                    
                    // Если в unified есть passport, используем его
                    if (isset($unifiedDataFromApi['passport']) && is_array($unifiedDataFromApi['passport'])) {
                        $villageData = array_merge($villageData, $unifiedDataFromApi['passport']);
                    }
                    
                    // Объединяем все данные
                    $mergedUnifiedData = array_merge($unifiedDataFromApi, $villageData);
                    
                    // Заменяем unified данные на объединенные данные
                    $fullData['data']['unified'] = [
                        'success' => true,
                        'data' => $mergedUnifiedData,
                    ];
                } catch (\Exception $e) {
                    Log::warning('Ошибка получения дополнительных данных блока', [
                        'id' => $villageId,
                        'error' => $e->getMessage(),
                    ]);
                    // Используем только данные из списка
                    $fullData = [
                        'success' => true,
                        'block_id' => $villageId,
                        'block_guid' => $isGuid ? $id : null,
                        'data' => [
                            'unified' => [
                                'success' => true,
                                'data' => $villageData,
                            ],
                        ],
                    ];
                }
            } else {
                // Если не нашли в списке, используем стандартный метод
                $fullData = $apiAuth->getBlockFullData($villageId, $options);
            }

            return response()->json([
                'success' => true,
                'block_id' => $fullData['block_id'] ?? $id,
                'block_guid' => $fullData['block_guid'] ?? null,
                'data' => $fullData['data'] ?? $fullData,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Ошибка получения детальной информации об участке', [
                'id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ], 500);
        }
    }

    /**
     * Получение детальной информации об участке
     * 
     * @param Request $request
     * @param string $id ID поселка (для контекста)
     * @param string $plotId ID участка
     * @return \Illuminate\Http\JsonResponse
     */
    public function plotDetail(Request $request, string $id, string $plotId)
    {
        if (config('trendagent.data_source') === 'db') {
            return app(\App\Http\Controllers\TrendAgent\Db\PlotsDbController::class)->plotDetail($request, $id, $plotId);
        }

        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $phone = $request->input('phone');
            $password = $request->input('password');

            // Авторизация через API
            $apiAuth = new TrendSsoApiAuth();
            $authData = $apiAuth->authenticate($phone, $password);

            if (!($authData['authenticated'] ?? false)) {
                throw new \Exception('Авторизация не удалась');
            }

            // Получаем детальную информацию об участке
            $plotData = $apiAuth->getPlotDetail($plotId, [
                'city' => $request->input('city', '58c665588b6aa52311afa01b'),
            ]);

            // Получаем данные поселка для контекста (если нужно)
            $villageData = null;
            try {
                $villageData = $apiAuth->getVillageById($id);
            } catch (\Exception $e) {
                Log::warning('Не удалось получить данные поселка для контекста', [
                    'village_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'block_id' => $plotData['block_id'] ?? $plotId,
                'block_guid' => $plotData['block_guid'] ?? null,
                'data' => $plotData['data'] ?? [],
                'village' => $villageData,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Ошибка получения детальной информации об участке', [
                'message' => $e->getMessage(),
                'plot_id' => $plotId,
                'village_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ], 500);
        }
    }
}
