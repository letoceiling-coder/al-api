<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TrendAgent\ParserController;

// Корневая страница - список проектов
Route::get('/', [ProjectController::class, 'index'])->name('home');

// TrendAgent Parser API - только API endpoints, не UI
Route::prefix('trendagent/parser')->name('trendagent.parser.')->group(function () {
    Route::post('/start', [ParserController::class, 'start'])->name('start');
    Route::post('/stop', [ParserController::class, 'stop'])->name('stop');
    Route::get('/status', [ParserController::class, 'status'])->name('status');
    Route::get('/logs', [ParserController::class, 'logs'])->name('logs');
    Route::get('/statistics', [ParserController::class, 'statistics'])->name('statistics');
});

// Проекты - без префикса /projects
// URL: /frontend, /trendagent (включая /trendagent/parser для UI)
Route::get('/{project}/{any?}', [ProjectController::class, 'show'])
    ->where('project', 'frontend|trendagent')  // Только разрешённые проекты
    ->where('any', '.*')
    ->name('project.show');

// Swagger для каждого проекта
Route::get('/swagger/{project}', function (string $project) {
    if (!in_array($project, ['frontend', 'trendagent'])) {
        abort(404);
    }
    
    return view('swagger.project', compact('project'));
})->name('swagger.project');
