<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ProjectController extends Controller
{
    /**
     * Отображение главной страницы со списком проектов
     */
    public function index()
    {
        $projects = [
            [
                'name' => 'Frontend',
                'icon' => '📱',
                'description' => 'Main application with documentation and navigation. Unified API interface for working with AI models.',
                'url' => route('project.show', 'frontend'),
                'api' => route('swagger.project', 'frontend'),
            ],
            [
                'name' => 'TrendAgent',
                'icon' => '🏠',
                'description' => 'Real estate platform - apartments, houses, plots, commercial properties. Data from trendagent.ru.',
                'url' => route('project.show', 'trendagent'),
                'api' => route('swagger.project', 'trendagent'),
            ],
        ];
        
        return view('home', compact('projects'));
    }
    
    /**
     * Отображение конкретного проекта
     */
    public function show(Request $request, string $project, $any = null)
    {
        // Проверяем существование проекта
        $indexPath = public_path("{$project}/index.html");
        
        if (!File::exists($indexPath)) {
            abort(404, "Project '{$project}' not found. Please run: cd projects/{$project} && npm run build");
        }
        
        // Читаем index.html и возвращаем как есть
        // React Router будет управлять маршрутизацией внутри приложения
        $html = File::get($indexPath);
        
        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}
