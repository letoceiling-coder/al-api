<?php

namespace App\Http\Controllers\TrendAgent;

use App\Services\TrendAgent\TrendSsoApiAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ParkingsController
{
    /**
     * Получение списка паркингов с фильтрами
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if (config('trendagent.data_source') === 'db') {
            return app(\App\Http\Controllers\TrendAgent\Db\ParkingsDbController::class)->index($request);
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
            // Фильтры
            'parking_type' => 'nullable|string',
            'price_from' => 'nullable|integer|min:0',
            'price_to' => 'nullable|integer|min:0',
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

            // Вычисляем offset из page, если передан page
            if ($page > 1 && $offset === 0) {
                $offset = ($page - 1) * $count;
            }

            // Параметры для API
            $apiParams = [
                'count' => $count,
                'offset' => $offset,
            ];

            // Параметр города
            if ($request->has('city') && !empty($request->input('city'))) {
                $apiParams['city'] = $request->input('city');
            }

            // Сортировка
            if ($request->has('sort')) {
                $apiParams['sort'] = $request->input('sort');
            }
            if ($request->has('sort_order')) {
                $apiParams['sort_order'] = $request->input('sort_order');
            }

            // Фильтры
            if ($request->has('parking_type')) {
                $apiParams['parking_type'] = $request->input('parking_type');
            }

            if ($request->has('price_from')) {
                $apiParams['price_from'] = (int) $request->input('price_from');
            }
            if ($request->has('price_to')) {
                $apiParams['price_to'] = (int) $request->input('price_to');
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
            $apiData = $apiAuth->getParkingsSearch($apiParams);

            if (!($apiData['success'] ?? false)) {
                throw new \Exception('Ошибка при получении данных из API');
            }

            // Формируем ответ
            $totalCount = $apiData['blocks_count'] ?? $apiData['total'] ?? 0;
            $returnedCount = count($apiData['data'] ?? []);
            
            $result = [
                'success' => true,
                'total_count' => $totalCount,
                'data' => [
                    'blocks_count' => $apiData['blocks_count'] ?? 0,
                    'places_count' => $apiData['places_count'] ?? 0,
                    'booked_places_count' => $apiData['booked_places_count'] ?? 0,
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
            Log::error('Ошибка получения списка паркингов', [
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
     * Получение детальной информации о паркинге (блоке)
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
            
            // Опции по умолчанию, если не переданы
            $requestOptions = $request->input('options', []);
            
            // Если options пустой массив, используем значения по умолчанию
            if (empty($requestOptions)) {
                $options = [
                    'unified' => true,
                    'buildings' => true,
                    'parkings' => true,
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
                // Если options передан, объединяем с дефолтными
                $options = array_merge([
                    'unified' => true,
                    'buildings' => true,
                    'parkings' => true,
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

            Log::info('ParkingsController::show - начало', [
                'id' => $id,
                'has_options' => !empty($options),
            ]);

            // Авторизация через API
            $apiAuth = new TrendSsoApiAuth();
            $authData = $apiAuth->authenticate($phone, $password);

            if (!($authData['authenticated'] ?? false)) {
                Log::error('ParkingsController::show - авторизация не удалась', [
                    'id' => $id,
                    'auth_data' => $authData,
                ]);
                throw new \Exception('Авторизация не удалась');
            }

            Log::info('ParkingsController::show - авторизация успешна', [
                'id' => $id,
            ]);

            // Получаем полные данные блока через getBlockFullData (как в ApartmentsController)
            Log::info('ParkingsController::show - запрос getBlockFullData', [
                'id' => $id,
                'options' => $options,
            ]);
            
            $fullData = $apiAuth->getBlockFullData($id, $options);

            Log::info('ParkingsController::show - получены данные', [
                'id' => $id,
                'has_data' => !empty($fullData),
                'data_keys' => is_array($fullData) ? array_keys($fullData) : 'not_array',
                'block_id' => $fullData['block_id'] ?? null,
                'block_guid' => $fullData['block_guid'] ?? null,
            ]);

            if (empty($fullData)) {
                Log::warning('ParkingsController::show - пустые данные', [
                    'id' => $id,
                ]);
            }

            // Получаем паркинги для этого блока через поиск (для дополнительных данных паркинга)
            $blockId = $fullData['block_id'] ?? $id;
            $blockGuid = $fullData['block_guid'] ?? null;
            $parkingData = null;
            
            try {
                $parkingsSearch = $apiAuth->getParkingsSearch([
                    'city' => '58c665588b6aa52311afa01b',
                    'lang' => 'ru',
                    'count' => 100, // Большой лимит для поиска нужного паркинга
                ]);

                if ($parkingsSearch['success'] && isset($parkingsSearch['data']) && is_array($parkingsSearch['data'])) {
                    foreach ($parkingsSearch['data'] as $parking) {
                        if (($parking['id'] ?? null) === $blockId || ($parking['guid'] ?? null) === $blockGuid) {
                            $parkingData = $parking;
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Ошибка загрузки паркинга для блока', [
                    'block_id' => $blockId,
                    'error' => $e->getMessage(),
                ]);
            }

            // Добавляем данные паркинга в структуру ответа
            $resultData = $fullData['data'] ?? $fullData;
            if ($parkingData) {
                $resultData['parking'] = [
                    'success' => true,
                    'data' => $parkingData,
                ];
            }
            
            // Также добавляем метод для получения мест парковки (будет использоваться при клике на "Показать паркинги")
            // Данные будут загружаться отдельным запросом через метод getBlockParkings

            return response()->json([
                'success' => true,
                'block_id' => $blockId,
                'block_guid' => $blockGuid,
                'data' => $resultData,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Ошибка получения детальной информации о паркинге', [
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
     * Получение мест парковки для блока (детальная информация)
     * 
     * @param Request $request
     * @param string $id ID блока
     * @return \Illuminate\Http\JsonResponse
     */
    public function places(Request $request, string $id)
    {
        if (config('trendagent.data_source') === 'db') {
            return app(\App\Http\Controllers\TrendAgent\Db\ParkingsDbController::class)->places($request, $id);
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

            // Получаем места парковки через getBlockParkings
            $parkingsData = $apiAuth->getBlockParkings($id);

            if (!($parkingsData['success'] ?? false)) {
                throw new \Exception('Ошибка при получении мест парковки');
            }

            return response()->json([
                'success' => true,
                'data' => $parkingsData['data'] ?? $parkingsData,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Ошибка получения мест парковки', [
                'id' => $id,
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

