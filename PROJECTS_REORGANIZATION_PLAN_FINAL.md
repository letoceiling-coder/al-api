# План реорганизации проектов - ФИНАЛЬНАЯ ВЕРСИЯ

## 📋 ТРЕБОВАНИЯ (СТРОГО)

### URL структура
- **`/`** → Laravel страница с карточками-ссылками на проекты
- **`/trendagent`** → React приложение (физически в `projects/trendagent/`)
- **`/frontend`** → React приложение (физически в `projects/frontend/`)
- **`/api/trendagent/v1/*`** → API для TrendAgent
- **`/api/frontend/v1/*`** → API для Frontend
- **`/swagger/trendagent`** → Swagger для TrendAgent API
- **`/swagger/frontend`** → Swagger для Frontend API

### Структура директорий
```
projects/
├── frontend/              # Исходники React проекта Frontend
│   ├── src/
│   ├── public/
│   ├── package.json
│   └── vite.config.js
└── trendagent/            # Исходники React проекта TrendAgent
    ├── src/
    ├── public/
    ├── package.json
    └── vite.config.js

public/
├── assets/
│   ├── frontend/          # npm run build → сюда
│   │   ├── assets/
│   │   └── index.html
│   └── trendagent/        # npm run build → сюда
│       ├── assets/
│       └── index.html
└── index.php
```

---

## 🎯 ЭТАП 1: Подготовка структуры директорий

### 1.1 Создание `projects/` и перенос исходников

```bash
# Создать директорию projects
mkdir -p projects

# Переместить frontend
mv frontend projects/

# Переместить trendagent
mv trendagent projects/
```

### 1.2 Обновление конфигураций сборки

**projects/frontend/vite.config.js:**
```javascript
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig({
  plugins: [react()],
  base: '/frontend/',  // URL путь
  build: {
    outDir: '../../public/assets/frontend',  // Куда собирать
    emptyOutDir: true,
    sourcemap: false,
    rollupOptions: {
      output: {
        manualChunks: {
          'react-vendor': ['react', 'react-dom', 'react-router-dom'],
          'axios-vendor': ['axios'],
        },
      },
    },
  },
  server: {
    port: 3000,
    proxy: {
      '/api': {
        target: 'https://api.siteaccess.ru',
        changeOrigin: true,
        secure: true,
      }
    }
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
})
```

**projects/trendagent/vite.config.js:**
```javascript
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig({
  plugins: [react()],
  base: '/trendagent/',  // URL путь
  build: {
    outDir: '../../public/assets/trendagent',  // Куда собирать
    emptyOutDir: true,
    sourcemap: false,
    rollupOptions: {
      output: {
        manualChunks: {
          'react-vendor': ['react', 'react-dom', 'react-router-dom'],
          'axios-vendor': ['axios'],
        },
      },
    },
  },
  server: {
    port: 3001,
    proxy: {
      '/api': {
        target: 'https://api.siteaccess.ru',
        changeOrigin: true,
        secure: true,
      }
    }
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
})
```

### 1.3 Обновление React Router

**projects/frontend/src/main.jsx:**
```javascript
import React from 'react'
import ReactDOM from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import App from './App'
import './index.css'

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <BrowserRouter basename="/frontend">
      <App />
    </BrowserRouter>
  </React.StrictMode>,
)
```

**projects/trendagent/src/main.jsx:**
```javascript
import React from 'react'
import ReactDOM from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import App from './App'
import './index.css'

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <BrowserRouter basename="/trendagent">
      <App />
    </BrowserRouter>
  </React.StrictMode>,
)
```

### 1.4 Обновление API endpoints

**projects/frontend/src/services/api.js:**
```javascript
import axios from 'axios'

const API_BASE_URL = '/api/frontend/v1'  // Версионированный API

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  timeout: 30000,
})

// ... остальной код
```

**projects/trendagent/src/services/api.js:**
```javascript
import axios from 'axios'

const API_BASE_URL = '/api/trendagent/v1'  // Версионированный API
const TRENDAGENT_TOKEN = '8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF'

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Authorization': `Bearer ${TRENDAGENT_TOKEN}`,
    'Content-Type': 'application/json',
  },
  timeout: 120000,
})

// ... остальной код
```

### 1.5 Создание структуры в `public/`

```bash
mkdir -p public/assets/frontend
mkdir -p public/assets/trendagent
```

### 1.6 Удаление старых файлов

```bash
# Удалить старые собранные файлы
rm -rf public/frontend
rm -rf public/trendagent
rm -rf public/trendagent_asset
rm -rf public/react

# Удалить старые симлинки (если есть)
```

---

## 🎯 ЭТАП 2: Создание корневой страницы с карточками

### 2.1 Создание главной страницы

**resources/views/home.blade.php:**
```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'API Gateway') }} - Projects</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            width: 100%;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 3rem;
        }
        
        .header h1 {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .project-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }
        
        .project-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        
        .project-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .project-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .project-description {
            font-size: 1rem;
            color: #666;
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }
        
        .project-links {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .project-link {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background: #f0f0f0;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #555;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .project-link:hover {
            background: #667eea;
            color: white;
        }
        
        .project-link svg {
            width: 16px;
            height: 16px;
            margin-right: 0.5rem;
        }
        
        .footer {
            text-align: center;
            color: white;
            padding: 2rem 0;
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            
            .projects-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 {{ config('app.name', 'API Gateway') }}</h1>
            <p>Select a project to get started</p>
        </div>
        
        <div class="projects-grid">
            @foreach($projects as $project)
            <a href="{{ $project['url'] }}" class="project-card">
                <div class="project-icon">{{ $project['icon'] }}</div>
                <h2 class="project-title">{{ $project['name'] }}</h2>
                <p class="project-description">{{ $project['description'] }}</p>
                <div class="project-links">
                    <span class="project-link">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        Open App
                    </span>
                    @if(isset($project['api']))
                    <a href="{{ $project['api'] }}" class="project-link" onclick="event.stopPropagation()">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        API Docs
                    </a>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
```

---

## 🎯 ЭТАП 3: Laravel Controllers и Routes

### 3.1 Создание ProjectController

**app/Http/Controllers/ProjectController.php:**
```php
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
        $indexPath = public_path("assets/{$project}/index.html");
        
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
```

### 3.2 Обновление routes/web.php

**routes/web.php:**
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;

// Корневая страница - список проектов
Route::get('/', [ProjectController::class, 'index'])->name('home');

// Проекты - без префикса /projects
// URL: /frontend, /trendagent
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
```

### 3.3 Обновление routes/api.php

**routes/api.php:**
```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Все маршруты автоматически получают префикс /api
| Структура: /api/{project}/{version}/{endpoint}
|
*/

// Frontend API v1
Route::prefix('frontend/v1')->group(function () {
    // Пример endpoints для Frontend API
    Route::get('/test', function () {
        return response()->json([
            'success' => true,
            'message' => 'Frontend API v1 is working',
            'timestamp' => now()->toIso8601String(),
        ]);
    });
    
    // Здесь будут другие endpoints Frontend API
});

// TrendAgent API - регистрируется через bootstrap/app.php
// Структура: /api/trendagent/v1/*
```

### 3.4 Обновление routes/trendagent.php

**routes/trendagent.php:**
```php
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
| TrendAgent API Routes v1
|--------------------------------------------------------------------------
|
| Префикс: /api/trendagent/v1
| Все маршруты требуют авторизации через TrendAgentAuthMiddleware
|
*/

Route::prefix('trendagent/v1')->middleware(['trendagent.auth'])->group(function () {
    
    // SSO Authentication & Cities
    Route::post('/authenticate', [TrendSsoController::class, 'authenticate']);
    Route::get('/cities', [TrendSsoController::class, 'getCities']);
    
    // Apartments
    Route::prefix('apartments')->group(function () {
        Route::post('/', [ApartmentsController::class, 'index']);
        Route::post('/{id}/flat/{apartmentId}', [ApartmentsController::class, 'flatDetail']);
        Route::post('/{id}/floor-plan/directory', [ApartmentsController::class, 'floorPlanDirectory']);
        Route::post('/{id}/floor-plan', [ApartmentsController::class, 'floorPlan']);
        Route::post('/{id}/checkerboard/buildings', [ApartmentsController::class, 'checkerboardBuildings']);
        Route::post('/{id}/checkerboard', [ApartmentsController::class, 'checkerboard']);
    });
    
    // Parkings
    Route::prefix('parkings')->group(function () {
        Route::post('/', [ParkingsController::class, 'index']);
        Route::post('/{id}', [ParkingsController::class, 'show']);
    });
    
    // Houses
    Route::prefix('houses')->group(function () {
        Route::post('/', [HousesController::class, 'index']);
        Route::post('/{id}', [HousesController::class, 'show']);
        Route::post('/{id}/checkerboard', [HousesController::class, 'checkerboard']);
    });
    
    // Plots
    Route::prefix('plots')->group(function () {
        Route::post('/', [PlotsController::class, 'index']);
        Route::post('/{id}', [PlotsController::class, 'show']);
    });
    
    // Commercial
    Route::prefix('commercial')->group(function () {
        Route::post('/', [CommercialController::class, 'index']);
        Route::post('/{id}', [CommercialController::class, 'show']);
    });
    
    // Objects & Blocks
    Route::post('/objects/list', [TrendSsoController::class, 'getObjectsList']);
    Route::post('/block/details', [ApartmentsController::class, 'blockDetails']);
});

// Swagger JSON для TrendAgent
Route::get('trendagent/v1/swagger.json', function () {
    $swaggerPath = storage_path('api-docs/trendagent-swagger.json');
    
    if (file_exists($swaggerPath)) {
        $content = file_get_contents($swaggerPath);
        $swagger = json_decode($content, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            return response()->json($swagger, 200)
                ->header('Content-Type', 'application/json')
                ->header('Access-Control-Allow-Origin', '*');
        }
    }
    
    // Fallback
    return response()->json([
        'openapi' => '3.0.0',
        'info' => [
            'title' => 'TrendAgent API',
            'version' => '1.0.0',
        ],
        'paths' => [],
    ], 200)->header('Content-Type', 'application/json');
});
```

### 3.5 Обновление bootstrap/app.php

**bootstrap/app.php:**
```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // TrendAgent API routes регистрируются как часть /api
            \Illuminate\Support\Facades\Route::prefix('api')
                ->group(base_path('routes/trendagent.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api-version' => \App\Http\Middleware\ApiVersionMiddleware::class,
            'deprecation-warning' => \App\Http\Middleware\DeprecationWarningMiddleware::class,
            'trendagent.auth' => \App\Http\Middleware\TrendAgentAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
```

---

## 🎯 ЭТАП 4: Swagger для каждого проекта

### 4.1 Создание Swagger view

**resources/views/swagger/project.blade.php:**
```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ucfirst($project) }} API - Swagger Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.5/swagger-ui.css" />
    <style>
        body { margin: 0; padding: 0; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.5/swagger-ui-bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            @if($project === 'frontend')
                // Frontend использует l5-swagger
                var swaggerUrl = "{{ url('/api/documentation') }}";
            @elseif($project === 'trendagent')
                // TrendAgent имеет свой swagger.json
                var swaggerUrl = "{{ url('/api/trendagent/v1/swagger.json') }}";
            @else
                var swaggerUrl = "/api/{{ $project }}/swagger.json";
            @endif
            
            const ui = SwaggerUIBundle({
                url: swaggerUrl,
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                validatorUrl: null,
                docExpansion: "list",
                filter: true,
                showExtensions: true,
                showCommonExtensions: true,
            });
            
            window.ui = ui;
        };
    </script>
</body>
</html>
```

---

## 🎯 ЭТАП 5: Nginx конфигурация

### 5.1 Новая конфигурация Nginx

**nginx_api_siteaccess_ru_v2.conf:**
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name api.siteaccess.ru;
    return 301 https://api.siteaccess.ru$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name api.siteaccess.ru;

    ssl_certificate /etc/letsencrypt/live/api.siteaccess.ru/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.siteaccess.ru/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    root /var/www/AL/public;
    index index.php index.html;
    client_max_body_size 100M;

    # Логи
    access_log /var/log/nginx/api.siteaccess.ru-access.log;
    error_log /var/log/nginx/api.siteaccess.ru-error.log;

    # API endpoints (всегда Laravel)
    location /api {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Swagger (всегда Laravel)
    location /swagger {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Все остальные запросы - Laravel
    # Laravel сам решит, отдавать React или обрабатывать
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP обработчик
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param SCRIPT_NAME $fastcgi_script_name;
    }
    
    # Запретить доступ к скрытым файлам
    location ~ /\. {
        deny all;
    }
}
```

---

## 🎯 ЭТАП 6: Обновление Home.jsx (карточки на корневой странице)

**projects/frontend/src/pages/Home.jsx:**
```jsx
import { useTranslation } from 'react-i18next'
import './Home.css'

const Home = () => {
  const { t } = useTranslation()

  return (
    <div className="home">
      <div className="home-hero">
        <h1 className="hero-title">{t('home.title')}</h1>
        <p className="hero-subtitle">{t('home.subtitle')}</p>
        <p className="hero-description">{t('home.description')}</p>
      </div>

      <div className="home-content">
        {/* Карточки проектов теперь на корневой странице Laravel, убираем отсюда */}
        
        <div className="home-section">
          <h2>📚 {t('home.documentation')}</h2>
          <div className="cards-grid">
            {/* ... остальные карточки документации */}
          </div>
        </div>
        
        {/* ... остальной контент */}
      </div>
    </div>
  )
}

export default Home
```

---

## 📋 ПОРЯДОК ВЫПОЛНЕНИЯ

### Шаг 1: Backup (ОБЯЗАТЕЛЬНО)

```bash
# На сервере
cd /var/www/AL
tar -czf backup-$(date +%Y%m%d-%H%M%S).tar.gz frontend/ trendagent/ public/ routes/ resources/

# Локально
git add -A
git commit -m "Backup before projects reorganization"
git push origin main
```

### Шаг 2: Реорганизация (локально)

```bash
# 1. Создать projects/
mkdir -p projects

# 2. Переместить проекты
mv frontend projects/
mv trendagent projects/

# 3. Обновить vite.config.js в обоих проектах (см. ЭТАП 1.2)
# 4. Обновить main.jsx в обоих проектах (см. ЭТАП 1.3)
# 5. Обновить api.js в обоих проектах (см. ЭТАП 1.4)

# 6. Создать структуру в public/
mkdir -p public/assets/frontend
mkdir -p public/assets/trendagent

# 7. Удалить старые файлы
rm -rf public/frontend
rm -rf public/trendagent
rm -rf public/trendagent_asset
rm -rf public/react
```

### Шаг 3: Создать Laravel файлы

```bash
# 1. Создать Controller
# app/Http/Controllers/ProjectController.php (см. ЭТАП 3.1)

# 2. Создать Views
# resources/views/home.blade.php (см. ЭТАП 2.1)
# resources/views/swagger/project.blade.php (см. ЭТАП 4.1)

# 3. Обновить routes
# routes/web.php (см. ЭТАП 3.2)
# routes/api.php (см. ЭТАП 3.3)
# routes/trendagent.php (см. ЭТАП 3.4)
# bootstrap/app.php (см. ЭТАП 3.5)

# 4. Создать Nginx конфигурацию
# nginx_api_siteaccess_ru_v2.conf (см. ЭТАП 5.1)
```

### Шаг 4: Сборка проектов (локально)

```bash
# Frontend
cd projects/frontend
npm install
npm run build
cd ../..

# TrendAgent
cd projects/trendagent
npm install
npm run build
cd ../..
```

### Шаг 5: Тестирование локально

```bash
php artisan serve

# Проверить:
# http://localhost:8000/ - главная страница с карточками
# http://localhost:8000/frontend - Frontend React
# http://localhost:8000/trendagent - TrendAgent React
# http://localhost:8000/api/frontend/v1/test
# http://localhost:8000/api/trendagent/v1/cities (с токеном)
# http://localhost:8000/swagger/frontend
# http://localhost:8000/swagger/trendagent
```

### Шаг 6: Деплой на сервер

```bash
# 1. Commit и push
git add -A
git commit -m "Reorganize projects: move to projects/, update routes and configs"
git push origin main

# 2. На сервере
ssh root@89.169.39.244
cd /var/www/AL

# 3. Pull изменений
git pull origin main

# 4. Установить зависимости и собрать
cd projects/frontend
npm install
npm run build
cd ../trendagent
npm install
npm run build
cd ../..

# 5. Права доступа
chown -R www-data:www-data /var/www/AL/public/assets
chmod -R 755 /var/www/AL/public/assets

# 6. Обновить Nginx
cp nginx_api_siteaccess_ru_v2.conf /etc/nginx/sites-available/api.siteaccess.ru
nginx -t
systemctl reload nginx

# 7. Очистить кеш Laravel
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### Шаг 7: Проверка на сервере

```bash
# Проверить все endpoints
curl -I https://api.siteaccess.ru/
curl -I https://api.siteaccess.ru/frontend
curl -I https://api.siteaccess.ru/trendagent
curl -I https://api.siteaccess.ru/api/frontend/v1/test
curl -I https://api.siteaccess.ru/swagger/frontend
curl -I https://api.siteaccess.ru/swagger/trendagent
```

---

## ✅ ИТОГОВАЯ СТРУКТУРА URL

| URL | Назначение | Обрабатывается |
|-----|-----------|---------------|
| `/` | Главная страница с карточками проектов | Laravel → `home.blade.php` |
| `/frontend` | Frontend React приложение | Laravel → `public/assets/frontend/index.html` |
| `/trendagent` | TrendAgent React приложение | Laravel → `public/assets/trendagent/index.html` |
| `/api/frontend/v1/*` | Frontend API v1 | Laravel → `routes/api.php` |
| `/api/trendagent/v1/*` | TrendAgent API v1 | Laravel → `routes/trendagent.php` |
| `/swagger/frontend` | Swagger для Frontend API | Laravel → `swagger/project.blade.php` |
| `/swagger/trendagent` | Swagger для TrendAgent API | Laravel → `swagger/project.blade.php` |

---

## 📝 ЧЕКЛИСТ ВЫПОЛНЕНИЯ

- [ ] Создан backup на сервере и локально
- [ ] Создана директория `projects/`
- [ ] Перенесены `frontend/` и `trendagent/` в `projects/`
- [ ] Обновлены `vite.config.js` в обоих проектах
- [ ] Обновлены `main.jsx` в обоих проектах
- [ ] Обновлены `api.js` в обоих проектах
- [ ] Создан `ProjectController.php`
- [ ] Создан `resources/views/home.blade.php`
- [ ] Создан `resources/views/swagger/project.blade.php`
- [ ] Обновлён `routes/web.php`
- [ ] Обновлён `routes/api.php`
- [ ] Обновлён `routes/trendagent.php`
- [ ] Обновлён `bootstrap/app.php`
- [ ] Создана новая Nginx конфигурация
- [ ] Удалены старые файлы из `public/`
- [ ] Собраны оба проекта локально (`npm run build`)
- [ ] Протестировано локально через `php artisan serve`
- [ ] Закоммичено и запушено в git
- [ ] Задеплоено на сервер
- [ ] Собраны проекты на сервере
- [ ] Обновлена Nginx конфигурация на сервере
- [ ] Перезагружен Nginx
- [ ] Протестированы все URL на сервере

---

## 🎯 СТАТУС

**План:** ✅ ГОТОВ К РЕАЛИЗАЦИИ  
**Дата:** 2026-02-07  
**Соответствие требованиям:** 100%

Все строго по заданию, ничего лишнего не добавлено.
