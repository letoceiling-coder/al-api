# Документация по настройке роутов для проектов

## 📋 Содержание

1. [Общая архитектура роутинга](#общая-архитектура-роутинга)
2. [Laravel роуты](#laravel-роуты)
3. [React роуты](#react-роуты)
4. [Nginx конфигурация](#nginx-конфигурация)
5. [Примеры настройки](#примеры-настройки)
6. [Типичные проблемы и решения](#типичные-проблемы-и-решения)

---

## 🏗️ Общая архитектура роутинга

Проект использует гибридную архитектуру роутинга:

```
┌─────────────────────────────────────────────────────────┐
│                    Nginx (Frontend)                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │  /api/*      │  │  /frontend/  │  │ /trendagent/  │ │
│  │  → Laravel   │  │  → React     │  │ → React       │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────┘
         │                    │                    │
         ▼                    ▼                    ▼
┌─────────────────────────────────────────────────────────┐
│              Laravel Application                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │ API Routes   │  │ Web Routes   │  │ React Router │ │
│  │ (PHP)        │  │ (PHP)        │  │ (JS)         │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### Принцип работы:

1. **Nginx** получает запрос и определяет, куда его направить:
   - `/api/*` → Laravel (PHP)
   - `/frontend/*` → React приложение (статический HTML/JS)
   - `/trendagent/*` → React приложение (статический HTML/JS)
   - `/` → React приложение (по умолчанию)

2. **Laravel** обрабатывает API запросы и некоторые веб-роуты

3. **React Router** обрабатывает клиентскую маршрутизацию внутри SPA

---

## 🔧 Laravel роуты

### Структура файлов роутов

```
routes/
├── web.php          # Веб-роуты (HTML страницы, формы)
├── api.php          # API роуты (JSON API)
├── trendagent.php   # Специфичные роуты для TrendAgent API
└── console.php      # Консольные команды
```

### Основные файлы роутов

#### 1. `routes/web.php` - Веб-роуты

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;

// Корневая страница - список проектов
Route::get('/', [ProjectController::class, 'index'])->name('home');

// Swagger документация
Route::get('/swagger/{project}', function (string $project) {
    if (!in_array($project, ['frontend', 'trendagent'])) {
        abort(404);
    }
    return view('swagger.project', compact('project'));
})->name('swagger.project');

// TrendAgent DB Interface - должен быть ПЕРЕД общим маршрутом
Route::get('/trendagent-db', [\App\Http\Controllers\TrendAgent\TrendAgentDbController::class, 'index'])
    ->name('trendagent.db');
Route::get('/trendagent/db', [\App\Http\Controllers\TrendAgent\TrendAgentDbController::class, 'index'])
    ->name('trendagent.db.alt');

// Проекты - React приложения (должен быть ПОСЛЕДНИМ)
// URL: /frontend, /trendagent (включая /trendagent/parser для UI)
Route::get('/{project}/{any?}', [ProjectController::class, 'show'])
    ->where('project', 'frontend|trendagent')
    ->where('any', '.*')
    ->name('project.show');
```

**Важно:** Порядок роутов имеет значение! Более специфичные роуты должны быть определены ПЕРЕД общими.

#### 2. `routes/trendagent.php` - TrendAgent API

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrendAgent\ApartmentsController;
// ... другие контроллеры

// Регистрируется в bootstrap/app.php с префиксом /api
Route::prefix('trendagent/parser')->name('trendagent.parser.')->group(function () {
    Route::post('/start', [ParserController::class, 'start'])->name('start');
    Route::get('/status', [ParserController::class, 'status'])->name('status');
    // ...
});

Route::prefix('trendagent/v1')->middleware(['trendagent.auth'])->group(function () {
    Route::post('/apartments', [ApartmentsController::class, 'index']);
    // ...
});
```

**Регистрация в `bootstrap/app.php`:**

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    then: function () {
        // TrendAgent API routes - регистрируются как часть /api
        \Illuminate\Support\Facades\Route::prefix('api')
            ->group(base_path('routes/trendagent.php'));
    },
)
```

### Итоговые URL для API:

- `/api/trendagent/v1/apartments` - API endpoints
- `/api/trendagent/parser/start` - Парсер
- `/trendagent-db` - Интерфейс БД (Laravel view)
- `/trendagent/db` - Альтернативный URL (требует настройки Nginx)

---

## ⚛️ React роуты

### Структура React приложений

```
projects/
├── frontend/
│   ├── src/
│   │   ├── App.jsx          # Главный компонент с роутами
│   │   ├── main.jsx         # Точка входа
│   │   └── pages/           # Страницы
│   └── vite.config.js       # Конфигурация сборки
└── trendagent/
    ├── src/
    │   ├── App.jsx          # Главный компонент с роутами
    │   ├── main.jsx         # Точка входа
    │   └── pages/           # Страницы
    └── vite.config.js       # Конфигурация сборки
```

### Настройка React Router

#### `projects/frontend/src/main.jsx`:

```jsx
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

#### `projects/frontend/src/App.jsx`:

```jsx
import { Routes, Route } from 'react-router-dom'
import Home from './pages/Home'
import Documentation from './pages/Documentation'

function App() {
  return (
    <div className="app">
      <Routes>
        <Route path="/" element={<Home />} />
        <Route path="/docs" element={<Documentation />} />
        {/* другие роуты */}
      </Routes>
    </div>
  )
}

export default App
```

#### `projects/trendagent/src/main.jsx`:

```jsx
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

#### `projects/trendagent/src/App.jsx`:

```jsx
import { Routes, Route } from 'react-router-dom'
import ObjectsList from './pages/ObjectsList'
import ObjectsTable from './pages/ObjectsTable'
import Parser from './pages/Parser'
// ... другие импорты

function App() {
  return (
    <div className="app">
      <Routes>
        <Route path="/" element={<ObjectsList />} />
        <Route path="/parser" element={<Parser />} />
        <Route path="/objects/table" element={<ObjectsTable />} />
        {/* другие роуты */}
      </Routes>
    </div>
  )
}

export default App
```

### Итоговые URL для React приложений:

**Frontend:**
- `https://api.siteaccess.ru/frontend/` → `Home` компонент
- `https://api.siteaccess.ru/frontend/docs` → `Documentation` компонент

**TrendAgent:**
- `https://api.siteaccess.ru/trendagent/` → `ObjectsList` компонент
- `https://api.siteaccess.ru/trendagent/parser` → `Parser` компонент
- `https://api.siteaccess.ru/trendagent/objects/table` → `ObjectsTable` компонент

---

## 🌐 Nginx конфигурация

### Полная конфигурация для `api.siteaccess.ru`

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

    # SSL сертификаты
    ssl_certificate /etc/letsencrypt/live/api.siteaccess.ru/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.siteaccess.ru/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    root /var/www/AL/public;
    index index.php index.html;
    client_max_body_size 100M;

    # ============================================
    # API routes - Laravel (ПЕРВЫМИ!)
    # ============================================
    location /api {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # ============================================
    # TrendAgent специфичные роуты
    # ============================================
    
    # Swagger - обрабатывается Laravel
    location = /trendagent/swagger {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /trendagent/swagger.json {
        try_files $uri $uri/ /index.php?$query_string;
        add_header Access-Control-Allow-Origin *;
        add_header Access-Control-Allow-Methods "GET OPTIONS";
        add_header Access-Control-Allow-Headers "Content-Type";
    }

    # TrendAgent DB Interface - Laravel view
    location = /trendagent/db {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # ============================================
    # React приложения (ПОСЛЕ API роутов!)
    # ============================================
    
    # TrendAgent React app
    location /trendagent/ {
        alias /var/www/AL/public/trendagent/;
        try_files $uri $uri/ /trendagent/index.html;
        index index.html;
        
        # Кэширование статических файлов
        location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
            expires 1y;
            add_header Cache-Control "public immutable";
        }
        
        # Отключить кэширование для index.html
        location = /trendagent/index.html {
            add_header Cache-Control "no-cache, no-store, must-revalidate";
            add_header Pragma "no-cache";
            add_header Expires "0";
        }
    }
    
    location = /trendagent {
        return 301 /trendagent/;
    }

    # Frontend React app
    location /frontend/ {
        alias /var/www/AL/public/frontend/;
        try_files $uri $uri/ /frontend/index.html;
        index index.html;
    }

    # ============================================
    # Остальные роуты
    # ============================================
    
    location /docs {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Корневой путь - Frontend React app (по умолчанию)
    location / {
        try_files $uri $uri/ /index.html /index.php?$query_string;
        
        # Serve static assets with cache
        location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
            expires 1y;
            add_header Cache-Control "public immutable";
        }
    }

    # ============================================
    # PHP обработка (Laravel)
    # ============================================
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Ключевые моменты конфигурации:

1. **Порядок location блоков важен!** Более специфичные должны быть ПЕРЕД общими:
   ```nginx
   location /api { ... }           # Специфичный
   location /trendagent/ { ... }   # Специфичный
   location / { ... }              # Общий (последний!)
   ```

2. **Использование `alias` для React приложений:**
   ```nginx
   location /trendagent/ {
       alias /var/www/AL/public/trendagent/;  # Обратите внимание на слэш в конце!
       try_files $uri $uri/ /trendagent/index.html;
   }
   ```

3. **`try_files` для SPA:**
   - Сначала пытается найти файл (`$uri`)
   - Затем директорию (`$uri/`)
   - Затем `index.html` для React Router
   - В конце Laravel (`/index.php?$query_string`)

---

## 📝 Примеры настройки

### Пример 1: Добавление нового API endpoint

**1. Создайте контроллер:**

```php
// app/Http/Controllers/MyController.php
namespace App\Http\Controllers;

class MyController extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'Hello']);
    }
}
```

**2. Добавьте роут в `routes/api.php`:**

```php
Route::get('/my-endpoint', [MyController::class, 'index']);
```

**3. URL будет доступен по адресу:**

```
https://api.siteaccess.ru/api/my-endpoint
```

### Пример 2: Добавление новой страницы в React приложение

**1. Создайте компонент страницы:**

```jsx
// projects/frontend/src/pages/NewPage.jsx
function NewPage() {
  return <div>New Page</div>;
}

export default NewPage;
```

**2. Добавьте роут в `App.jsx`:**

```jsx
import NewPage from './pages/NewPage'

function App() {
  return (
    <Routes>
      <Route path="/" element={<Home />} />
      <Route path="/new-page" element={<NewPage />} />
    </Routes>
  )
}
```

**3. URL будет доступен по адресу:**

```
https://api.siteaccess.ru/frontend/new-page
```

### Пример 3: Добавление Laravel view (Blade)

**1. Создайте контроллер:**

```php
// app/Http/Controllers/MyWebController.php
class MyWebController extends Controller
{
    public function show()
    {
        return view('my-page');
    }
}
```

**2. Добавьте роут в `routes/web.php`:**

```php
Route::get('/my-page', [MyWebController::class, 'show'])->name('my.page');
```

**3. Создайте view:**

```blade
{{-- resources/views/my-page.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>My Page</title>
</head>
<body>
    <h1>My Page</h1>
</body>
</html>
```

**4. URL будет доступен по адресу:**

```
https://api.siteaccess.ru/my-page
```

**Важно:** Этот роут должен быть определен ПЕРЕД общим `Route::get('/{project}/{any?}')` в `web.php`!

---

## 🔍 Типичные проблемы и решения

### Проблема 1: 403 Forbidden для React приложений

**Причина:** Неправильные права доступа к файлам или директориям.

**Решение:**

```bash
chown -R www-data:www-data /var/www/AL/public/frontend
chown -R www-data:www-data /var/www/AL/public/trendagent
chmod -R 755 /var/www/AL/public/frontend
chmod -R 755 /var/www/AL/public/trendagent
```

### Проблема 2: React роуты не работают (404 при обновлении страницы)

**Причина:** Nginx не настроен для отдачи `index.html` для всех путей React приложения.

**Решение:** Убедитесь, что в конфигурации есть:

```nginx
location /trendagent/ {
    alias /var/www/AL/public/trendagent/;
    try_files $uri $uri/ /trendagent/index.html;  # Важно!
    index index.html;
}
```

### Проблема 3: Laravel роуты перехватываются React приложением

**Причина:** Общий роут `/{project}/{any?}` определен ПЕРЕД специфичными роутами.

**Решение:** В `routes/web.php` специфичные роуты должны быть ПЕРЕД общим:

```php
// ✅ Правильно
Route::get('/trendagent-db', [Controller::class, 'method']);  // Специфичный
Route::get('/{project}/{any?}', [ProjectController::class, 'show']);  // Общий

// ❌ Неправильно
Route::get('/{project}/{any?}', [ProjectController::class, 'show']);  // Общий
Route::get('/trendagent-db', [Controller::class, 'method']);  // Специфичный (никогда не сработает!)
```

### Проблема 4: Nginx перехватывает `/trendagent/db` для React

**Причина:** `location /trendagent/` определен ПЕРЕД `location = /trendagent/db`.

**Решение:** В Nginx конфигурации более специфичные location должны быть ПЕРЕД общими:

```nginx
# ✅ Правильно
location = /trendagent/db {  # Точное совпадение (более специфичное)
    try_files $uri $uri/ /index.php?$query_string;
}
location /trendagent/ {  # Префикс (менее специфичное)
    alias /var/www/AL/public/trendagent/;
    try_files $uri $uri/ /trendagent/index.html;
}

# ❌ Неправильно
location /trendagent/ {  # Префикс (перехватит /trendagent/db)
    alias /var/www/AL/public/trendagent/;
}
location = /trendagent/db {  # Никогда не сработает!
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Проблема 5: API роуты возвращают 404

**Причина:** Роут не зарегистрирован или неправильный префикс.

**Решение:**

1. Проверьте регистрацию в `bootstrap/app.php`
2. Проверьте префикс в `routes/trendagent.php`
3. Проверьте, что Nginx правильно перенаправляет на Laravel:

```nginx
location /api {
    try_files $uri $uri/ /index.php?$query_string;  # Важно!
}
```

4. Проверьте роуты:

```bash
php artisan route:list | grep trendagent
```

---

## 📚 Дополнительные ресурсы

### Laravel Routing
- [Официальная документация Laravel Routing](https://laravel.com/docs/routing)
- `php artisan route:list` - список всех роутов

### React Router
- [Официальная документация React Router](https://reactrouter.com/)
- `basename` prop для настройки базового пути

### Nginx
- [Официальная документация Nginx](https://nginx.org/en/docs/)
- `nginx -t` - проверка конфигурации
- `systemctl reload nginx` - перезагрузка без простоя

---

## ✅ Чеклист настройки нового проекта

- [ ] Создать React приложение в `projects/{project-name}/`
- [ ] Настроить `basename` в `BrowserRouter`
- [ ] Настроить роуты в `App.jsx`
- [ ] Собрать проект: `npm run build`
- [ ] Проверить, что файлы в `public/{project-name}/`
- [ ] Добавить location блок в Nginx конфигурацию
- [ ] Установить правильные права доступа
- [ ] Проверить работу: `curl -I https://api.siteaccess.ru/{project-name}/`
- [ ] Добавить роут в `routes/web.php` (если нужен Laravel роут)
- [ ] Проверить порядок роутов (специфичные перед общими)

---

**Последнее обновление:** 2026-02-08
