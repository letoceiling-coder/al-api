<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use L5Swagger\Http\Controllers\SwaggerController;

// Главная страница - документация
Route::get('/', function () {
    $html = File::get(public_path('index_docs.html'));
    return response($html)->header('Content-Type', 'text/html; charset=utf-8');
});

// Альтернативный роут для документации
Route::get('/docs', function () {
    $html = File::get(public_path('index_docs.html'));
    return response($html)->header('Content-Type', 'text/html; charset=utf-8');
});

// Fix for l5-swagger route name issue
Route::get('/docs/{jsonFile?}', [SwaggerController::class, 'docs'])
    ->name('l5-swagger.default.docs')
    ->where('jsonFile', '.*');
