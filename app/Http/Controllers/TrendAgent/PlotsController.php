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
            $apiData = $apiAuth->getPlotsSearch($apiParams);

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

            // Получаем полные данные блока
            $fullData = $apiAuth->getBlockFullData($id, $options);

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
}
