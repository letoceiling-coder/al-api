<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use L5Swagger\Http\Controllers\SwaggerController;

// Главная страница - документация
Route::get('/', function () {
    $html = File::get(public_path('index_docs.html'));
    return response($html)->header('Content-Type', 'text/html; charset=utf-8');
});

// Альтернативный роут для документации (только если не запрос к Swagger)
Route::get('/docs', function () {
    // Проверяем, не запрашивается ли JSON/YAML файл для Swagger
    if (request()->has('api-docs.json') || request()->has('api-docs.yaml')) {
        abort(404); // Пусть l5-swagger обработает
    }
    $html = File::get(public_path('index_docs.html'));
    return response($html)->header('Content-Type', 'text/html; charset=utf-8');
})->where('docs', '^docs$');

// Fix for l5-swagger route name issue - должен быть ПЕРЕД /docs
Route::get('/docs/{jsonFile?}', [SwaggerController::class, 'docs'])
    ->name('l5-swagger.default.docs')
    ->where('jsonFile', '.*');
