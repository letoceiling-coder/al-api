# План реорганизации проектов с использованием Laravel маршрутизации и layouts

## 📋 ТЕКУЩЕЕ СОСТОЯНИЕ

### Структура директорий
```
public/
├── frontend/               # НЕ СУЩЕСТВУЕТ в public (симлинк на ../frontend)
├── trendagent/            # Статические файлы React (КОНФЛИКТ с маршрутом)
├── trendagent_asset/      # Уже переименовано
├── react/                 # Старый проект
├── index.html             # Корневая страница
└── index_docs.html        # Документация

/var/www/AL/
├── frontend/              # Исходники React проекта
├── trendagent/            # Исходники React проекта
└── public/
    ├── frontend -> ../frontend (симлинк)
    └── trendagent_asset/  # Собранные файлы
```

### Маршрутизация
**Nginx (текущая конфигурация):**
- `/` → `public/frontend/index.html` (alias)
- `/frontend/` → `public/frontend/index.html` (alias)
- `/trendagent/` → `public/trendagent_asset/index.html` (alias)
- `/api/*` → Laravel
- `/api/trendagent/*` → Laravel (TrendAgent API)

**Laravel (текущая конфигурация):**
- `routes/web.php` - почти пустой, только `/guide`
- `routes/trendagent.php` - зарегистрирован с префиксом `/api`
- React приложения обслуживаются напрямую через Nginx

### Проблемы текущей структуры
1. ❌ **Смешение подходов**: Nginx напрямую отдаёт React файлы, Laravel не участвует
2. ❌ **Нет централизованного управления**: каждый проект настроен индивидуально в Nginx
3. ❌ **Нет layouts**: нет единого шаблона для проектов
4. ❌ **Конфликт имён**: папки в `public/` конфликтуют с маршрутами
5. ❌ **Swagger не централизован**: каждый проект имеет свой Swagger, но без единой точки входа
6. ❌ **Нарушение Laravel best practices**: статические файлы в `public/` должны иметь префиксы

---

## 🎯 ЦЕЛЕВАЯ СТРУКТУРА

### Новая структура директорий
```
projects/
├── frontend/              # Исходники React проекта
│   ├── src/
│   ├── public/
│   ├── package.json
│   └── vite.config.js (base: /projects/frontend/)
└── trendagent/            # Исходники React проекта
    ├── src/
    ├── public/
    ├── package.json
    └── vite.config.js (base: /projects/trendagent/)

public/
├── projects/              # Собранные статические файлы
│   ├── frontend/          # npm run build → сюда
│   │   ├── assets/
│   │   └── index.html
│   └── trendagent/        # npm run build → сюда
│       ├── assets/
│       └── index.html
├── assets/                # Общие assets (если нужны)
├── index.html             # Корневая страница (по желанию)
└── index.php              # Laravel entry point

resources/views/
├── layouts/
│   └── project.blade.php  # Единый layout для всех проектов
├── projects/
│   ├── frontend.blade.php # View для frontend проекта
│   └── trendagent.blade.php # View для trendagent проекта
└── swagger/
    ├── index.blade.php    # Общий Swagger UI (l5-swagger)
    └── projects/
        ├── frontend.blade.php    # Swagger для Frontend API
        └── trendagent.blade.php  # Swagger для TrendAgent API
```

### Новая маршрутизация

**Laravel routes/web.php:**
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;

// Корневая страница - может быть Laravel view или редирект
Route::get('/', function () {
    return view('welcome'); // или redirect('/projects/frontend')
});

// Проекты через Laravel
Route::prefix('projects')->group(function () {
    // Frontend Project
    Route::get('/frontend/{any?}', [ProjectController::class, 'frontend'])
        ->where('any', '.*')
        ->name('projects.frontend');
    
    // TrendAgent Project
    Route::get('/trendagent/{any?}', [ProjectController::class, 'trendagent'])
        ->where('any', '.*')
        ->name('projects.trendagent');
});

// Swagger документация для каждого проекта
Route::prefix('swagger')->group(function () {
    Route::get('/frontend', function () {
        return view('swagger.projects.frontend');
    })->name('swagger.frontend');
    
    Route::get('/trendagent', function () {
        return view('swagger.projects.trendagent');
    })->name('swagger.trendagent');
});
```

**Laravel routes/api.php:**
```php
<?php

// Все API маршруты автоматически получают префикс /api

// Frontend API (если есть)
Route::prefix('frontend')->group(function () {
    // API endpoints для Frontend проекта
});

// TrendAgent API (уже существует в routes/trendagent.php)
// Регистрируется через bootstrap/app.php
```

**Nginx конфигурация (упрощённая):**
```nginx
# Все запросы к /projects/* идут в Laravel
location /projects/ {
    try_files $uri $uri/ /index.php?$query_string;
}

# API маршруты (остаются без изменений)
location /api {
    try_files $uri $uri/ /index.php?$query_string;
}

# Swagger (через Laravel)
location /swagger {
    try_files $uri $uri/ /index.php?$query_string;
}

# Корневая страница - Laravel
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

# PHP обработчик
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

---

## 📝 ДЕТАЛЬНЫЙ ПЛАН МИГРАЦИИ

### ЭТАП 1: Подготовка структуры директорий

#### 1.1 Создание директории `projects/`
```bash
mkdir -p projects/frontend
mkdir -p projects/trendagent
```

#### 1.2 Перенос исходников
```bash
# Frontend
mv frontend/* projects/frontend/
rmdir frontend

# TrendAgent
mv trendagent/* projects/trendagent/
rmdir trendagent
```

#### 1.3 Обновление конфигураций сборки

**projects/frontend/vite.config.js:**
```javascript
export default defineConfig({
  plugins: [react()],
  base: '/projects/frontend/',  // ИЗМЕНЕНО
  build: {
    outDir: '../../public/projects/frontend',  // ИЗМЕНЕНО
    emptyOutDir: true,
    // ...
  },
  // ...
})
```

**projects/trendagent/vite.config.js:**
```javascript
export default defineConfig({
  plugins: [react()],
  base: '/projects/trendagent/',  // ИЗМЕНЕНО
  build: {
    outDir: '../../public/projects/trendagent',  // ИЗМЕНЕНО
    emptyOutDir: true,
    // ...
  },
  // ...
})
```

#### 1.4 Обновление BrowserRouter в React

**projects/frontend/src/main.jsx:**
```javascript
<BrowserRouter basename="/projects/frontend">
  <App />
</BrowserRouter>
```

**projects/trendagent/src/main.jsx:**
```javascript
<BrowserRouter basename="/projects/trendagent">
  <App />
</BrowserRouter>
```

#### 1.5 Создание структуры в `public/`
```bash
mkdir -p public/projects/frontend
mkdir -p public/projects/trendagent
```

---

### ЭТАП 2: Создание Laravel Views и Layouts

#### 2.1 Создание базового layout

**resources/views/layouts/project.blade.php:**
```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ $title ?? config('app.name') }}</title>
    
    @yield('head')
</head>
<body>
    <div id="root">
        @yield('content')
    </div>
    
    @yield('scripts')
</body>
</html>
```

#### 2.2 Создание views для проектов

**resources/views/projects/frontend.blade.php:**
```blade
@extends('layouts.project')

@section('head')
    <script type="module" crossorigin src="{{ asset('projects/frontend/assets/index-[hash].js') }}"></script>
    <link rel="stylesheet" crossorigin href="{{ asset('projects/frontend/assets/index-[hash].css') }}">
@endsection

@section('content')
    <!-- React монтируется в #root -->
@endsection
```

**resources/views/projects/trendagent.blade.php:**
```blade
@extends('layouts.project')

@section('head')
    <script type="module" crossorigin src="{{ asset('projects/trendagent/assets/index-[hash].js') }}"></script>
    <link rel="stylesheet" crossorigin href="{{ asset('projects/trendagent/assets/index-[hash].css') }}">
@endsection

@section('content')
    <!-- React монтируется в #root -->
@endsection
```

---

### ЭТАП 3: Создание Controller

**app/Http/Controllers/ProjectController.php:**
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ProjectController extends Controller
{
    /**
     * Отображение Frontend проекта
     */
    public function frontend(Request $request, $any = null)
    {
        // Получаем содержимое index.html
        $indexPath = public_path('projects/frontend/index.html');
        
        if (!File::exists($indexPath)) {
            abort(404, 'Frontend project not found. Run: cd projects/frontend && npm run build');
        }
        
        $html = File::get($indexPath);
        
        // Возвращаем HTML напрямую (React управляет роутингом)
        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }
    
    /**
     * Отображение TrendAgent проекта
     */
    public function trendagent(Request $request, $any = null)
    {
        $indexPath = public_path('projects/trendagent/index.html');
        
        if (!File::exists($indexPath)) {
            abort(404, 'TrendAgent project not found. Run: cd projects/trendagent && npm run build');
        }
        
        $html = File::get($indexPath);
        
        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
```

---

### ЭТАП 4: Обновление маршрутизации

#### 4.1 Обновление `routes/web.php`

**routes/web.php:**
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;

// Корневая страница
Route::get('/', function () {
    return redirect('/projects/frontend');
})->name('home');

// Проекты
Route::prefix('projects')->group(function () {
    Route::get('/frontend/{any?}', [ProjectController::class, 'frontend'])
        ->where('any', '.*')
        ->name('projects.frontend');
    
    Route::get('/trendagent/{any?}', [ProjectController::class, 'trendagent'])
        ->where('any', '.*')
        ->name('projects.trendagent');
});

// Swagger для каждого проекта
Route::get('/swagger/frontend', function () {
    return view('swagger.projects.frontend');
})->name('swagger.frontend');

Route::get('/swagger/trendagent', function () {
    return view('swagger.projects.trendagent');
})->name('swagger.trendagent');

// Главная Swagger страница (опционально)
Route::get('/swagger', function () {
    return view('swagger.index', [
        'projects' => [
            ['name' => 'Frontend API', 'url' => route('swagger.frontend')],
            ['name' => 'TrendAgent API', 'url' => route('swagger.trendagent')],
        ]
    ]);
})->name('swagger.index');
```

#### 4.2 Обновление `bootstrap/app.php`

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
            // TrendAgent API routes - с префиксом /api/trendagent
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

### ЭТАП 5: Swagger для каждого проекта

#### 5.1 Создание Swagger views

**resources/views/swagger/index.blade.php:**
```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .project { padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px; }
        .project h2 { margin-top: 0; }
        .project a { color: #007bff; text-decoration: none; }
        .project a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>📚 API Documentation</h1>
    <p>Select a project to view its API documentation:</p>
    
    @foreach($projects as $project)
    <div class="project">
        <h2>{{ $project['name'] }}</h2>
        <p><a href="{{ $project['url'] }}">View API Documentation →</a></p>
    </div>
    @endforeach
</body>
</html>
```

**resources/views/swagger/projects/frontend.blade.php:**
```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frontend API - Swagger Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.5/swagger-ui.css" />
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.5/swagger-ui-bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            SwaggerUIBundle({
                url: "{{ url('/api/documentation') }}", // l5-swagger endpoint
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout"
            });
        };
    </script>
</body>
</html>
```

**resources/views/swagger/projects/trendagent.blade.php:**
```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrendAgent API - Swagger Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.5/swagger-ui.css" />
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.5/swagger-ui-bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            SwaggerUIBundle({
                url: "{{ url('/api/trendagent/swagger.json') }}",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout"
            });
        };
    </script>
</body>
</html>
```

---

### ЭТАП 6: Обновление Nginx конфигурации

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

    # API endpoints (всегда идут в Laravel)
    location /api {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Swagger (всегда идут в Laravel)
    location /swagger {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Проекты (всегда идут в Laravel)
    location /projects {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Корневая страница (Laravel)
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP обработчик
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

### ЭТАП 7: Обновление .htaccess (для Apache)

**public/.htaccess** - уже корректен, но добавим комментарии:
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller (все запросы идут в index.php)
    # Статические файлы из public/ обслуживаются напрямую
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

### ЭТАП 8: Обновление API endpoints в TrendAgent

#### 8.1 Обновление `routes/trendagent.php`

Убрать конфликтующие маршруты:
```php
// УДАЛИТЬ:
Route::get('/trendagent/', function () { ... });
Route::get('/trendagent/swagger', function () { ... });
Route::get('/trendagent/swagger.json', function () { ... });

// Оставить только API endpoints под префиксом /api/trendagent
```

---

## 🔄 ПОРЯДОК ВЫПОЛНЕНИЯ МИГРАЦИИ

### Шаг 1: Резервное копирование
```bash
# На сервере
cd /var/www/AL
tar -czf backup-$(date +%Y%m%d).tar.gz frontend/ trendagent/ public/ routes/ resources/views/

# Локально
git add -A
git commit -m "Backup before projects reorganization"
git push origin main
```

### Шаг 2: Создание новой структуры (локально)
```bash
# 1. Создать директорию projects/
mkdir -p projects

# 2. Переместить исходники
mv frontend projects/
mv trendagent projects/

# 3. Обновить vite.config.js (см. ЭТАП 1)
# 4. Обновить main.jsx (см. ЭТАП 1)
# 5. Создать public/projects/
mkdir -p public/projects

# 6. Удалить старые собранные файлы
rm -rf public/frontend public/trendagent

# 7. Пересобрать проекты
cd projects/frontend && npm run build
cd ../trendagent && npm run build
cd ../..
```

### Шаг 3: Создание Laravel структуры
```bash
# 1. Создать Controller
# 2. Создать Views (layouts, projects, swagger)
# 3. Обновить routes/web.php
# 4. Обновить routes/trendagent.php
```

### Шаг 4: Тестирование локально
```bash
php artisan serve
# Проверить:
# - http://localhost:8000/
# - http://localhost:8000/projects/frontend
# - http://localhost:8000/projects/trendagent
# - http://localhost:8000/swagger
# - http://localhost:8000/api/...
```

### Шаг 5: Деплой на сервер
```bash
# 1. Закоммитить изменения
git add -A
git commit -m "Reorganize projects with Laravel routing and layouts"
git push origin main

# 2. На сервере
ssh root@89.169.39.244
cd /var/www/AL
git pull origin main

# 3. Установить зависимости и собрать
cd projects/frontend && npm install && npm run build
cd ../trendagent && npm install && npm run build
cd ../..

# 4. Обновить Nginx
cp nginx_api_siteaccess_ru_v2.conf /etc/nginx/sites-available/api.siteaccess.ru
nginx -t
systemctl reload nginx

# 5. Проверить права
chown -R www-data:www-data /var/www/AL/public/projects
```

### Шаг 6: Проверка на сервере
```bash
curl -I https://api.siteaccess.ru/projects/frontend
curl -I https://api.siteaccess.ru/projects/trendagent
curl -I https://api.siteaccess.ru/swagger
curl -I https://api.siteaccess.ru/api/trendagent/cities
```

---

## ✅ ПРЕИМУЩЕСТВА НОВОЙ СТРУКТУРЫ

1. **✅ Laravel Best Practices**
   - Все маршруты управляются через Laravel
   - Использование Controller и Views
   - Единая точка входа через `index.php`

2. **✅ Централизованное управление**
   - Все проекты в одной директории `projects/`
   - Единый layout для всех проектов
   - Легко добавлять новые проекты

3. **✅ Нет конфликтов имён**
   - Статические файлы в `public/projects/`
   - Маршруты `/projects/`
   - Чёткое разделение

4. **✅ Swagger для каждого проекта**
   - `/swagger` - главная страница
   - `/swagger/frontend` - Frontend API
   - `/swagger/trendagent` - TrendAgent API

5. **✅ Простая Nginx конфигурация**
   - Все запросы идут в Laravel
   - Статические файлы обслуживаются автоматически
   - Нет сложных правил

6. **✅ Расширяемость**
   - Легко добавить новый проект:
     1. Создать `projects/newproject/`
     2. Добавить метод в `ProjectController`
     3. Добавить маршрут в `routes/web.php`
     4. Собрать проект `npm run build`

---

## 📊 СРАВНЕНИЕ: ДО и ПОСЛЕ

### Структура URL

| URL | ДО (Nginx) | ПОСЛЕ (Laravel) |
|-----|-----------|----------------|
| `/` | `public/frontend/index.html` | Laravel → `redirect('/projects/frontend')` |
| `/frontend/` | `public/frontend/index.html` | Laravel → `ProjectController@frontend` |
| `/trendagent/` | `public/trendagent_asset/index.html` | Laravel → `ProjectController@trendagent` |
| `/projects/frontend/` | ❌ Не существует | ✅ Laravel → `ProjectController@frontend` |
| `/projects/trendagent/` | ❌ Не существует | ✅ Laravel → `ProjectController@trendagent` |
| `/api/*` | Laravel | Laravel (без изменений) |
| `/swagger` | ❌ Конфликт | ✅ Laravel → главная страница Swagger |
| `/swagger/frontend` | ❌ Не существует | ✅ Laravel → Swagger для Frontend |
| `/swagger/trendagent` | `/api/trendagent/swagger` | ✅ Laravel → Swagger для TrendAgent |

### Добавление нового проекта

**ДО (требует изменений в 5+ местах):**
1. Создать директорию в корне
2. Настроить `vite.config.js`
3. Добавить location block в Nginx
4. Перезагрузить Nginx
5. Настроить деплой
6. Обновить `.gitignore`

**ПОСЛЕ (требует изменений в 3 местах):**
1. Создать `projects/newproject/`
2. Добавить метод в `ProjectController`
3. Добавить маршрут в `routes/web.php`
4. `npm run build` (автоматически в `public/projects/newproject/`)

---

## ⚠️ ПОТЕНЦИАЛЬНЫЕ ПРОБЛЕМЫ И РЕШЕНИЯ

### Проблема 1: Старые ссылки
**Описание:** Ссылки `/frontend` и `/trendagent` перестанут работать  
**Решение:** Добавить редиректы в `routes/web.php`:
```php
Route::get('/frontend/{any?}', function ($any = null) {
    return redirect('/projects/frontend' . ($any ? "/$any" : ''));
})->where('any', '.*');

Route::get('/trendagent/{any?}', function ($any = null) {
    return redirect('/projects/trendagent' . ($any ? "/$any" : ''));
})->where('any', '.*');
```

### Проблема 2: React Router не работает
**Описание:** При обновлении страницы `/projects/trendagent/apartments` возвращает 404  
**Решение:** `ProjectController` уже возвращает `index.html` для всех путей

### Проблема 3: Assets не загружаются
**Описание:** CSS/JS файлы возвращают 404  
**Решение:** Laravel автоматически обслуживает файлы из `public/`, убедиться что `base` в `vite.config.js` корректен

---

## 📋 ЧЕКЛИСТ ПЕРЕД ВЫПОЛНЕНИЕМ

- [ ] Создан backup на сервере
- [ ] Создан git commit на локальной машине
- [ ] Проверено что все зависимости установлены (`npm install` в каждом проекте)
- [ ] Проверено что проекты собираются локально (`npm run build`)
- [ ] Создан `ProjectController.php`
- [ ] Созданы все view файлы
- [ ] Обновлён `routes/web.php`
- [ ] Обновлён `routes/trendagent.php`
- [ ] Создана новая Nginx конфигурация
- [ ] Протестировано локально через `php artisan serve`

---

## 🎯 ИТОГОВАЯ ОЦЕНКА

**Сложность:** ⭐⭐⭐⭐☆ (4/5 - средне-высокая)  
**Время выполнения:** 2-4 часа  
**Риск:** ⚠️ Средний (требуется backup и тестирование)  
**Выгода:** ✅ Высокая (улучшенная архитектура, расширяемость, централизованное управление)

---

## 📞 ВОПРОСЫ ДЛЯ ОБСУЖДЕНИЯ

1. **Корневая страница `/`:**
   - Вариант A: Редирект на `/projects/frontend`
   - Вариант B: Отдельная Laravel welcome страница со списком проектов
   - Вариант C: Отдельный статический `index.html`
   
2. **Обратная совместимость:**
   - Нужны ли редиректы с `/frontend` на `/projects/frontend`?
   - Или можно сразу обновить все ссылки?

3. **Swagger:**
   - Нужна ли главная страница `/swagger` со списком проектов?
   - Или сразу редирект на один из проектов?

4. **Frontend проект:**
   - Есть ли у него свой API или только TrendAgent?
   - Нужен ли для него отдельный Swagger?

5. **Дополнительные проекты:**
   - Планируется ли добавление других проектов в будущем?
   - Какие это могут быть проекты?

---

## 📝 СЛЕДУЮЩИЕ ШАГИ

После утверждения плана:
1. Выполнить backup
2. Начать с ЭТАП 1 (подготовка структуры)
3. Тестировать каждый этап локально
4. Деплоить на сервер только после полного локального тестирования
5. Мониторить логи после деплоя

---

**Дата создания плана:** 2026-02-07  
**Статус:** 📋 Ожидает утверждения
