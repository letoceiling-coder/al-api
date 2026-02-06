<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GeminiController;
use App\Http\Controllers\Api\OpenAIController;

Route::get('/test', function () {
    return response()->json(['message' => 'API is working', 'status' => 'success']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Gemini API Routes
Route::prefix('gemini')->middleware('auth:sanctum')->group(function () {
    Route::post('/generate', [GeminiController::class, 'generate']);
    Route::post('/multimodal', [GeminiController::class, 'multimodal']);
    Route::post('/stream', [GeminiController::class, 'stream']);
    Route::post('/embedding', [GeminiController::class, 'embedding']);
    Route::post('/functions', [GeminiController::class, 'functions']);
    Route::post('/json', [GeminiController::class, 'json']);
});

// OpenAI API Routes
Route::prefix('openai')->middleware('auth:sanctum')->group(function () {
    Route::post('/chat', [OpenAIController::class, 'chat']);
    Route::post('/vision', [OpenAIController::class, 'vision']);
    Route::post('/image', [OpenAIController::class, 'image']);
    Route::post('/transcribe', [OpenAIController::class, 'transcribe']);
    Route::post('/embedding', [OpenAIController::class, 'embedding']);
    Route::post('/tts', [OpenAIController::class, 'tts']);
    Route::post('/moderate', [OpenAIController::class, 'moderate']);
    Route::post('/stream', [OpenAIController::class, 'stream']);
    Route::post('/functions', [OpenAIController::class, 'functions']);
    Route::post('/json', [OpenAIController::class, 'json']);
});
