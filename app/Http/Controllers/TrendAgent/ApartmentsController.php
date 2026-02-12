<?php

namespace App\Http\Controllers\TrendAgent;

use App\Services\TrendAgent\TrendSsoApiAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApartmentsController
{
    /**
     * Получение списка квартир с фильтрами
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
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
            'room' => 'nullable|array',
            'room.*' => 'integer',
            'price_from' => 'nullable|integer|min:0',
            'price_to' => 'nullable|integer|min:0',
            'area_from' => 'nullable|numeric|min:0',
            'area_to' => 'nullable|numeric|min:0',
            'floor_from' => 'nullable|integer|min:0',
            'floor_to' => 'nullable|integer|min:0',
            'finishing_types' => 'nullable|array',
            'deadline_key' => 'nullable|string',
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
            if ($request->has('room') && is_array($request->input('room'))) {
                $apiParams['room'] = array_filter($request->input('room'), function($r) {
                    return is_numeric($r) && $r > 0;
                });
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
            if ($request->has('floor_from')) {
                $apiParams['floor_from'] = (int) $request->input('floor_from');
            }
            if ($request->has('floor_to')) {
                $apiParams['floor_to'] = (int) $request->input('floor_to');
            }
            if ($request->has('finishing_types') && is_array($request->input('finishing_types'))) {
                $apiParams['finishing_types'] = $request->input('finishing_types');
            }
            if ($request->has('deadline_key')) {
                $apiParams['deadline_key'] = $request->input('deadline_key');
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

            // Получаем данные через API (используем getApartmentsSearch для получения списка квартир)
            $apiData = $apiAuth->getApartmentsSearch($apiParams);

            if (!($apiData['success'] ?? false)) {
                throw new \Exception('Ошибка при получении данных из API');
            }

            // Формируем ответ
            // getApartmentsSearch возвращает: { success: true, data: [...], total: ..., blocks_count: ... }
            $totalCount = $apiData['total'] ?? 0;
            $returnedCount = count($apiData['data'] ?? []);
            
            $result = [
                'success' => true,
                'total_count' => $totalCount,
                'data' => [
                    'blocks_count' => $apiData['blocks_count'] ?? 0,
                    'apartments_count' => $totalCount,
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
            Log::error('Ошибка получения списка квартир', [
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
     * Получение детальной информации о квартире (блоке/ЖК)
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
            } else {
                // Если options передан, объединяем с дефолтными
                $options = array_merge([
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
                ], $requestOptions);
            }
            
                // Параметры для квартир
                // НЕ устанавливаем onrequest по умолчанию - показываем ВСЕ доступные квартиры
                // onrequest будет использоваться только если явно указан в запросе
                if (!isset($options['apartments_params'])) {
                    $options['apartments_params'] = [];
                }
                // Не устанавливаем onrequest по умолчанию

            Log::info('ApartmentsController::show - начало', [
                'id' => $id,
                'has_options' => !empty($options),
            ]);

            // Авторизация через API
            $apiAuth = new TrendSsoApiAuth();
            $authData = $apiAuth->authenticate($phone, $password);

            if (!($authData['authenticated'] ?? false)) {
                Log::error('ApartmentsController::show - авторизация не удалась', [
                    'id' => $id,
                    'auth_data' => $authData,
                ]);
                throw new \Exception('Авторизация не удалась');
            }

            Log::info('ApartmentsController::show - авторизация успешна', [
                'id' => $id,
            ]);

            // Получаем полные данные блока
            Log::info('ApartmentsController::show - запрос getBlockFullData', [
                'id' => $id,
                'options' => $options,
                'apartments_params' => $options['apartments_params'] ?? null,
            ]);
            
            $fullData = $apiAuth->getBlockFullData($id, $options);

            Log::info('ApartmentsController::show - получены данные', [
                'id' => $id,
                'has_data' => !empty($fullData),
                'data_keys' => is_array($fullData) ? array_keys($fullData) : 'not_array',
                'block_id' => $fullData['block_id'] ?? null,
                'block_guid' => $fullData['block_guid'] ?? null,
            ]);

            if (empty($fullData)) {
                Log::warning('ApartmentsController::show - пустые данные', [
                    'id' => $id,
                ]);
            }

            // Упрощаем структуру ответа - убираем двойную вложенность data
            // getBlockFullData возвращает: { success, block_id, block_guid, data: { unified, apartments, ... } }
            // Мы возвращаем: { success, block_id, block_guid, data: { unified, apartments, ... } }
            // То есть просто передаем data из getBlockFullData напрямую
            return response()->json([
                'success' => true,
                'block_id' => $fullData['block_id'] ?? $id,
                'block_guid' => $fullData['block_guid'] ?? null,
                'data' => $fullData['data'] ?? $fullData, // Убираем лишний уровень вложенности
            ], 200);

        } catch (\Exception $e) {
            Log::error('Ошибка получения детальной информации о квартире', [
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
     * Получение корпусов для шахматки
     * 
     * @param Request $request
     * @param string $id ID блока
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkerboardBuildings(Request $request, string $id)
    {
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

            $apiAuth = new TrendSsoApiAuth();
            $authData = $apiAuth->authenticate($phone, $password);

            if (!($authData['authenticated'] ?? false)) {
                throw new \Exception('Авторизация не удалась');
            }

            $params = [];
            if ($request->has('room')) {
                $params['room'] = is_array($request->input('room')) 
                    ? $request->input('room') 
                    : [$request->input('room')];
            }
            
            // Параметр onrequest для фильтрации квартир под запрос
            if ($request->has('onrequest')) {
                $params['onrequest'] = $request->input('onrequest');
            }

            $result = $apiAuth->getCheckerboardBuildings($id, $params);

            return response()->json([
                'success' => true,
                'data' => $result['data'] ?? $result,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Ошибка получения корпусов для шахматки', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получение квартир для шахматки по корпусу
     * 
     * @param Request $request
     * @param string $id ID блока
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkerboardApartments(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string',
            'building_id' => 'required|string',
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
            $buildingId = $request->input('building_id');

            $apiAuth = new TrendSsoApiAuth();
            $authData = $apiAuth->authenticate($phone, $password);

            if (!($authData['authenticated'] ?? false)) {
                throw new \Exception('Авторизация не удалась');
            }

            $result = $apiAuth->getCheckerboardApartments($id, $buildingId);

            return response()->json([
                'success' => true,
                'data' => $result['data'] ?? $result,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Ошибка получения квартир для шахматки', [
                'id' => $id,
                'building_id' => $request->input('building_id'),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получение детальной информации о квартире
     * 
     * @param Request $request
     * @param string $id ID блока
     * @param string $apartmentId ID квартиры
     * @return \Illuminate\Http\JsonResponse
     */
    public function flatDetail(Request $request, string $id, string $apartmentId)
    {
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

            $apiAuth = new TrendSsoApiAuth();
            $authData = $apiAuth->authenticate($phone, $password);

            if (!($authData['authenticated'] ?? false)) {
                throw new \Exception('Авторизация не удалась');
            }

            // Извлекаем дополнительные параметры из query string (sort, sort_order, open и т.д.)
            $params = [];
            if ($request->has('sort')) {
                $params['sort'] = $request->input('sort');
            }
            if ($request->has('sort_order')) {
                $params['sort_order'] = $request->input('sort_order');
            }
            if ($request->has('open')) {
                $params['open'] = $request->input('open');
            }
            // Передаем все остальные query параметры (кроме phone, password, block, guid)
            $excludedParams = ['phone', 'password', 'block', 'guid'];
            foreach ($request->query() as $key => $value) {
                if (!in_array($key, $excludedParams) && !isset($params[$key])) {
                    $params[$key] = $value;
                }
            }
            
            Log::info('ApartmentsController::flatDetail - параметры запроса', [
                'apartment_id' => $apartmentId,
                'block_id' => $id,
                'params' => $params,
            ]);
            
            // Получаем детальную информацию о квартире с параметрами
            $apartmentData = $apiAuth->getApartmentDetail($apartmentId, $id, $params);

            // Также получаем данные блока для контекста
            $blockData = null;
            try {
                $blockData = $apiAuth->getBlockUnified($id);
            } catch (\Exception $e) {
                Log::warning('Не удалось получить данные блока для flat detail', [
                    'block_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Получаем планы и отделку для блока (могут содержать изображения для квартиры)
            $plansData = null;
            $finishingsData = null;
            try {
                $plansData = $apiAuth->getBlockPlans($id);
            } catch (\Exception $e) {
                Log::warning('Не удалось получить планы блока', ['error' => $e->getMessage()]);
            }
            
            try {
                $finishingsData = $apiAuth->getBlockFinishings($id);
            } catch (\Exception $e) {
                Log::warning('Не удалось получить отделку блока', ['error' => $e->getMessage()]);
            }

            // Получаем дополнительные данные: вознаграждения, скидки, ипотека, рассрочка
            $rewardsData = null;
            $discountsData = null;
            $mortgageData = null;
            $installmentsData = null;
            
            $builderIdRaw = $blockData['data']['builder_id'] ?? $blockData['builder_id'] ?? null;
            // Преобразуем builderId в строку, если это массив, берем _id или первый элемент
            $builderId = null;
            if ($builderIdRaw) {
                if (is_array($builderIdRaw)) {
                    $builderId = $builderIdRaw['_id'] ?? $builderIdRaw['id'] ?? (is_array($builderIdRaw) && count($builderIdRaw) > 0 ? (string)($builderIdRaw[0] ?? reset($builderIdRaw)) : null);
                } else {
                    $builderId = (string)$builderIdRaw;
                }
            }
            
            try {
                $rewardsData = $apiAuth->getRewards($id, $builderId);
            } catch (\Exception $e) {
                Log::warning('Не удалось получить данные о вознаграждениях', ['error' => $e->getMessage()]);
            }
            
            try {
                $discountsData = $apiAuth->getDiscounts($id, $builderId);
            } catch (\Exception $e) {
                Log::warning('Не удалось получить данные о скидках', ['error' => $e->getMessage()]);
            }
            
            try {
                $mortgageData = $apiAuth->getMortgage($id);
            } catch (\Exception $e) {
                Log::warning('Не удалось получить данные об ипотеке', ['error' => $e->getMessage()]);
            }
            
            try {
                $installmentsData = $apiAuth->getInstallments($id);
            } catch (\Exception $e) {
                Log::warning('Не удалось получить данные о рассрочке', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'block_id' => $id,
                'apartment_id' => $apartmentId,
                'data' => [
                    'apartment' => $apartmentData['data'] ?? $apartmentData,
                    'block' => $blockData['data'] ?? $blockData,
                    'rewards' => $rewardsData,
                    'discounts' => $discountsData,
                    'mortgage' => $mortgageData,
                    'installments' => $installmentsData,
                    'plans' => $plansData,
                    'finishings' => $finishingsData,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Ошибка получения детальной информации о квартире', [
                'block_id' => $id,
                'apartment_id' => $apartmentId,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Справочник для поэтажного плана: корпуса, секции, этажи
     *
     * @param Request $request
     * @param string $id ID блока
     * @return \Illuminate\Http\JsonResponse
     */
    public function floorPlanDirectory(Request $request, string $id)
    {
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

            $apiAuth = new TrendSsoApiAuth();
            $authData = $apiAuth->authenticate($phone, $password);

            if (!($authData['authenticated'] ?? false)) {
                throw new \Exception('Авторизация не удалась');
            }

            $result = $apiAuth->getFloorPlanDirectory($id);

            return response()->json([
                'success' => true,
                'data' => $result['data'] ?? $result,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Ошибка получения справочника поэтажного плана', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Данные плана этажа (поэтажный план)
     *
     * @param Request $request
     * @param string $id ID блока (не используется в API, для единообразия роутов)
     * @return \Illuminate\Http\JsonResponse
     */
    public function floorPlan(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string',
            'building_id' => 'required|string',
            'section_id' => 'required|string',
            'floor_number' => 'required',
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
            $buildingId = $request->input('building_id');
            $sectionId = $request->input('section_id');
            $floorNumber = $request->input('floor_number');

            $apiAuth = new TrendSsoApiAuth();
            $authData = $apiAuth->authenticate($phone, $password);

            if (!($authData['authenticated'] ?? false)) {
                throw new \Exception('Авторизация не удалась');
            }

            $result = $apiAuth->getFloorPlan($buildingId, $sectionId, $floorNumber);

            return response()->json([
                'success' => true,
                'data' => $result['data'] ?? $result,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Ошибка получения поэтажного плана', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}


