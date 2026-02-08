<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;

// Корневая страница - список проектов
Route::get('/', [ProjectController::class, 'index'])->name('home');

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

// TrendAgent DB Interface
Route::get('/trendagent/db', [\App\Http\Controllers\TrendAgent\TrendAgentDbController::class, 'index'])
    ->name('trendagent.db');
