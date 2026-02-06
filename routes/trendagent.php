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
| TrendAgent API Routes
|--------------------------------------------------------------------------
|
| API для парсинга trendagent.ru
| Использует отдельный токен для аутентификации: 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF
| Все маршруты требуют авторизации через TrendAgentAuthMiddleware
|
*/

Route::prefix('trendagent')->middleware(['trendagent.auth'])->group(function () {
    
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
        Route::post('/{id}', [ApartmentsController::class, 'show']); // Детали объекта
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
    });
    
    // ============================================
    // Plots (Участки)
    // ============================================
    Route::prefix('plots')->group(function () {
        Route::post('/', [PlotsController::class, 'index']); // Список участков
        Route::post('/{id}', [PlotsController::class, 'show']); // Детали участка
    });
    
    // ============================================
    // Commercial (Коммерческая недвижимость)
    // ============================================
    Route::prefix('commercial')->group(function () {
        Route::post('/', [CommercialController::class, 'index']); // Список коммерческой недвижимости
        Route::post('/{id}', [CommercialController::class, 'show']); // Детали коммерческой недвижимости
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
