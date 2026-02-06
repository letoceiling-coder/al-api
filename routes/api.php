<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AIProxyController;
use App\Http\Controllers\Api\UserKeysController;
use App\Http\Controllers\Api\AnalyticsController;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
|
| Versioned API routes with backward compatibility for existing endpoints.
| OpenAPI/Swagger documentation available at /api/documentation
|
*/

// Public test endpoint (unversioned for backward compatibility)
Route::get('/test', function () {
    return response()->json([
        'message' => 'AL API is working',
        'version' => '1.0.0',
        'status' => 'success',
        'timestamp' => now()->toIso8601String(),
        'api_version' => 'v1',
        'documentation' => url('/api/documentation'),
    ]);
});

// ============================================
// API Version 1 (v1) - Main routes
// ============================================
Route::prefix('v1')->middleware(['api-version:v1'])->group(function () {
    
    // Public test endpoint
    Route::get('/test', function () {
        return response()->json([
            'message' => 'AL API v1 is working',
            'version' => '1.0.0',
            'status' => 'success',
            'timestamp' => now()->toIso8601String(),
            'api_version' => 'v1',
        ]);
    });

    // Protected routes (require Sanctum authentication)
    Route::middleware('auth:sanctum')->group(function () {
        
        // User info
        Route::get('/user', function (Request $request) {
            return response()->json([
                'success' => true,
                'user' => $request->user(),
                'api_version' => 'v1',
            ]);
        });

        // ============================================
        // AI Processing (Main endpoint)
        // ============================================
        Route::post('/ai/process', [AIProxyController::class, 'process'])
            ->name('v1.ai.process');

        // ============================================
        // User API Keys Management
        // ============================================
        Route::prefix('user/keys')->name('v1.keys.')->group(function () {
            Route::get('/', [UserKeysController::class, 'index'])->name('index');
            Route::post('/', [UserKeysController::class, 'store'])->name('store');
            Route::get('/{id}', [UserKeysController::class, 'show'])->name('show');
            Route::put('/{id}', [UserKeysController::class, 'update'])->name('update');
            Route::delete('/{id}', [UserKeysController::class, 'destroy'])->name('destroy');
            Route::patch('/{id}/toggle', [UserKeysController::class, 'toggle'])->name('toggle');
        });

        // ============================================
        // Analytics & Usage
        // ============================================
        Route::prefix('analytics')->name('v1.analytics.')->group(function () {
            Route::get('/summary', [AnalyticsController::class, 'summary'])->name('summary');
            Route::get('/history', [AnalyticsController::class, 'history'])->name('history');
            Route::get('/costs', [AnalyticsController::class, 'costs'])->name('costs');
            Route::get('/limits', [AnalyticsController::class, 'limits'])->name('limits');
            Route::get('/by-provider', [AnalyticsController::class, 'byProvider'])->name('by-provider');
            Route::get('/by-model', [AnalyticsController::class, 'byModel'])->name('by-model');
        });

    });
});

// ============================================
// Backward Compatibility (DEPRECATED)
// ============================================
// Legacy routes without version prefix (will be removed in v2)
Route::middleware('auth:sanctum')->group(function () {
    
    // User info
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
            'warning' => 'This endpoint is deprecated. Please use /api/v1/user instead.',
            'deprecated' => true,
        ]);
    });

    // AI Processing (backward compatibility alias)
    Route::post('/ai/process', [AIProxyController::class, 'process'])
        ->middleware('deprecation-warning');

    // User API Keys Management (backward compatibility)
    Route::prefix('user/keys')->group(function () {
        Route::get('/', [UserKeysController::class, 'index'])
            ->middleware('deprecation-warning');
        Route::post('/', [UserKeysController::class, 'store'])
            ->middleware('deprecation-warning');
        Route::get('/{id}', [UserKeysController::class, 'show'])
            ->middleware('deprecation-warning');
        Route::put('/{id}', [UserKeysController::class, 'update'])
            ->middleware('deprecation-warning');
        Route::delete('/{id}', [UserKeysController::class, 'destroy'])
            ->middleware('deprecation-warning');
        Route::patch('/{id}/toggle', [UserKeysController::class, 'toggle'])
            ->middleware('deprecation-warning');
    });

    // Analytics & Usage (backward compatibility)
    Route::prefix('analytics')->group(function () {
        Route::get('/summary', [AnalyticsController::class, 'summary'])
            ->middleware('deprecation-warning');
        Route::get('/history', [AnalyticsController::class, 'history'])
            ->middleware('deprecation-warning');
        Route::get('/costs', [AnalyticsController::class, 'costs'])
            ->middleware('deprecation-warning');
        Route::get('/limits', [AnalyticsController::class, 'limits'])
            ->middleware('deprecation-warning');
        Route::get('/by-provider', [AnalyticsController::class, 'byProvider'])
            ->middleware('deprecation-warning');
        Route::get('/by-model', [AnalyticsController::class, 'byModel'])
            ->middleware('deprecation-warning');
    });
});
