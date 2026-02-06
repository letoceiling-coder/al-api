<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use L5Swagger\Http\Controllers\SwaggerController;

// Главная страница - React приложение
Route::get('/', function () {
    if (file_exists(public_path('react/index.html'))) {
        return response()->file(public_path('react/index.html'));
    }
    // Fallback на старую документацию
    $html = File::get(public_path('index_docs.html'));
    return response($html)->header('Content-Type', 'text/html; charset=utf-8');
});

// Альтернативный роут для документации (перенесен на /guide чтобы не конфликтовать с Swagger)
Route::get('/guide', function () {
    $html = File::get(public_path('index_docs.html'));
    return response($html)->header('Content-Type', 'text/html; charset=utf-8');
});

// React app routes - serve index.html for all React routes
Route::get('/react/{any?}', function () {
    if (file_exists(public_path('react/index.html'))) {
        return response()->file(public_path('react/index.html'));
    }
    return redirect('/');
})->where('any', '.*');

// Fix for l5-swagger route name issue
Route::get('/docs/{jsonFile?}', [SwaggerController::class, 'docs'])
    ->name('l5-swagger.default.docs')
    ->where('jsonFile', '.*');
