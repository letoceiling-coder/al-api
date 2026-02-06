<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AIProxyController;
use App\Http\Controllers\Api\UserKeysController;
use App\Http\Controllers\Api\AnalyticsController;

// Public test endpoint
Route::get('/test', function () {
    return response()->json([
        'message' => 'AL API is working',
        'version' => '1.0.0',
        'status' => 'success',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Protected routes (require Sanctum authentication)
Route::middleware('auth:sanctum')->group(function () {
    
    // User info
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    });

    // ============================================
    // AI Processing (Main endpoint)
    // ============================================
    Route::post('/ai/process', [AIProxyController::class, 'process']);

    // ============================================
    // User API Keys Management
    // ============================================
    Route::prefix('user/keys')->group(function () {
        Route::get('/', [UserKeysController::class, 'index']);           // List all keys
        Route::post('/', [UserKeysController::class, 'store']);          // Add new key
        Route::get('/{id}', [UserKeysController::class, 'show']);        // Get key details
        Route::put('/{id}', [UserKeysController::class, 'update']);      // Update key
        Route::delete('/{id}', [UserKeysController::class, 'destroy']);  // Delete key
        Route::patch('/{id}/toggle', [UserKeysController::class, 'toggle']); // Enable/disable key
    });

    // ============================================
    // Analytics & Usage
    // ============================================
    Route::prefix('analytics')->group(function () {
        Route::get('/summary', [AnalyticsController::class, 'summary']);           // Overall stats
        Route::get('/history', [AnalyticsController::class, 'history']);           // Request history
        Route::get('/costs', [AnalyticsController::class, 'costs']);               // Cost breakdown
        Route::get('/limits', [AnalyticsController::class, 'limits']);             // Current limits
        Route::get('/by-provider', [AnalyticsController::class, 'byProvider']);    // Stats by provider
        Route::get('/by-model', [AnalyticsController::class, 'byModel']);          // Stats by model
    });

});
