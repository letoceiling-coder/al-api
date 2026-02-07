<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrendAgent\ApartmentsController;
use App\Http\Controllers\TrendAgent\ParkingsController;
use App\Http\Controllers\TrendAgent\TrendSsoController;
use App\Http\Controllers\TrendAgent\HousesController;
use App\Http\Controllers\TrendAgent\PlotsController;
use App\Http\Controllers\TrendAgent\CommercialController;

/*
|--------------------------------------------------------------------------
| TrendAgent API Routes v1
|--------------------------------------------------------------------------
|
| Префикс: /api/trendagent/v1
| API для парсинга trendagent.ru
| Использует отдельный токен для аутентификации: 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF
| Все маршруты требуют авторизации через TrendAgentAuthMiddleware
|
*/

// Swagger JSON для TrendAgent
Route::get('trendagent/v1/swagger.json', function () {
    $swaggerPath = storage_path('api-docs/trendagent-swagger.json');
    
    if (file_exists($swaggerPath)) {
        $content = file_get_contents($swaggerPath);
        $swagger = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            \Log::error('TrendAgent Swagger JSON parse error', [
                'error' => json_last_error_msg(),
                'path' => $swaggerPath
            ]);
            
            // Fallback к базовой структуре при ошибке парсинга
            return response()->json([
                'openapi' => '3.0.0',
                'info' => [
                    'title' => 'TrendAgent API',
                    'version' => '1.0.0',
                    'description' => 'API для получения данных о недвижимости с сайта trendagent.ru',
                ],
                'servers' => [
                    ['url' => 'https://api.siteaccess.ru/trendagent', 'description' => 'Production API Server'],
                ],
                'security' => [
                    ['trendagent_auth' => []],
                ],
                'paths' => [],
            ], 200)->header('Content-Type', 'application/json');
        }
        
        return response()->json($swagger, 200)
            ->header('Content-Type', 'application/json')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type');
    }
    
    // Fallback к базовой структуре
    return response()->json([
        'openapi' => '3.0.0',
        'info' => [
            'title' => 'TrendAgent API',
            'version' => '1.0.0',
            'description' => 'API для получения данных о недвижимости с сайта trendagent.ru',
        ],
        'servers' => [
            ['url' => 'https://api.siteaccess.ru/api/trendagent/v1', 'description' => 'Production API Server'],
        ],
        'security' => [
            ['trendagent_auth' => []],
        ],
        'paths' => [],
    ], 200)->header('Content-Type', 'application/json');
})->name('trendagent.swagger.json');

Route::prefix('trendagent/v1')->middleware(['trendagent.auth'])->group(function () {
    
    // ============================================
    // SSO Authentication & Cities
    // ============================================
    Route::post('/authenticate', [TrendSsoController::class, 'authenticate']);
    Route::get('/cities', [TrendSsoController::class, 'getCities']);
    
    // ============================================
    // Apartments (Квартиры)
    // ============================================
    Route::prefix('apartments')->group(function () {
        Route::post('/', [ApartmentsController::class, 'index']); // Список квартир
        // Более специфичные роуты должны быть определены раньше менее специфичных
        Route::post('/{id}/flat/{apartmentId}', [ApartmentsController::class, 'flatDetail']); // Детальная информация о квартире
        Route::post('/{id}/floor-plan/directory', [ApartmentsController::class, 'floorPlanDirectory']); // Справочник поэтажного плана
        Route::post('/{id}/floor-plan', [ApartmentsController::class, 'floorPlan']); // Данные плана этажа
        Route::post('/{id}/checkerboard/buildings', [ApartmentsController::class, 'checkerboardBuildings'])
            ->where(['id' => '[a-f0-9]{24}']); // Корпуса для шахматки
        Route::post('/{id}/checkerboard/apartments', [ApartmentsController::class, 'checkerboardApartments'])
            ->where(['id' => '[a-f0-9]{24}']); // Квартиры для шахматки
        Route::post('/{id}', [ApartmentsController::class, 'show'])
            ->where(['id' => '[a-f0-9]{24}|[a-z0-9-]+']); // Детали объекта (должен быть последним)
    });
    
    // ============================================
    // Parkings (Паркинги)
    // ============================================
    Route::prefix('parkings')->group(function () {
        Route::post('/', [ParkingsController::class, 'index']); // Список паркингов
        Route::post('/{id}', [ParkingsController::class, 'show']); // Детали паркинга
        Route::post('/{id}/places', [ParkingsController::class, 'places']); // Места парковки
    });
    
    // ============================================
    // Houses (Дома с участками)
    // ============================================
    Route::prefix('houses')->group(function () {
        Route::post('/', [HousesController::class, 'index']); // Список домов
        Route::post('/{id}', [HousesController::class, 'show']); // Детали дома
        Route::post('/{id}/checkerboard/buildings', [HousesController::class, 'checkerboardBuildings']); // Корпуса для шахматки
        Route::post('/{id}/checkerboard/apartments', [HousesController::class, 'checkerboardApartments']); // Квартиры для шахматки
    });
    
    // ============================================
    // Plots (Участки)
    // ============================================
    Route::prefix('plots')->group(function () {
        Route::post('/', [PlotsController::class, 'index']); // Список участков
        Route::post('/{id}', [PlotsController::class, 'show']); // Детали поселка
        Route::post('/{id}/plot/{plotId}', [PlotsController::class, 'plotDetail']); // Детальная информация об участке
    });
    
    // ============================================
    // Commercial (Коммерческая недвижимость)
    // ============================================
    Route::prefix('commercial')->group(function () {
        Route::post('/', [CommercialController::class, 'index']); // Список коммерческой недвижимости
        Route::post('/{id}', [CommercialController::class, 'show']); // Детали коммерческой недвижимости
    });
    
    // ============================================
    // House Projects / Contractors (Проекты домов / Подрядчики)
    // ============================================
    Route::prefix('houseprojects')->group(function () {
        Route::post('/', [TrendSsoController::class, 'getObjectsList']); // Список проектов (object_type=contractors)
        Route::post('/{id}', [TrendSsoController::class, 'getBlockDetails']); // Детали проекта
    });
    
    // ============================================
    // Objects List (Универсальный список объектов)
    // ============================================
    Route::post('/objects/list', [TrendSsoController::class, 'getObjectsList']);
    
    // ============================================
    // Block Details (Детали блока)
    // ============================================
    Route::post('/block/details', [TrendSsoController::class, 'getBlockDetails']);
    Route::post('/block/{dataType}', [TrendSsoController::class, 'getBlockData']);
});
