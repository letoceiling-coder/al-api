# План улучшений AL API Gateway (на основе Ответа 1)

## 📊 Анализ текущего состояния

### ✅ Что уже реализовано:
- Авторизация через Sanctum Bearer token
- Унифицированный endpoint `/api/ai/process`
- Поддержка параметров модели (temperature, max_tokens, top_p, top_k)
- Передача файлов в Base64
- Управление API-ключами (CRUD)
- Аналитика и лимиты
- HTML документация

### ⚠️ Что нужно улучшить:
- Нет машиночитаемой спецификации (OpenAPI)
- Нет версионирования API
- Ограниченная обработка ошибок
- Нет streaming ответов
- Файлы только в Base64 (нет multipart)
- Документация только на русском

---

## 🎯 План улучшений (приоритизированный)

### Приоритет 1: Критично для интеграции (1-2 недели)

#### 1.1 OpenAPI/Swagger спецификация ⭐⭐⭐

**Цель:** Создать машиночитаемую спецификацию API

**Задачи:**
- [ ] Установить `darkaonline/l5-swagger` или `vyuldashev/laravel-openapi`
- [ ] Создать OpenAPI 3.0 спецификацию (YAML/JSON)
- [ ] Описать все endpoints с примерами
- [ ] Описать схемы запросов и ответов
- [ ] Описать все возможные ошибки
- [ ] Настроить Swagger UI на `/api/documentation`
- [ ] Автогенерация из аннотаций или ручное описание

**Файлы для создания:**
```
resources/api-docs/
  ├── openapi.yaml          # Основная спецификация
  ├── schemas/
  │   ├── AIRequest.yaml    # Схема запроса
  │   ├── AIResponse.yaml    # Схема ответа
  │   └── Error.yaml        # Схема ошибок
  └── paths/
      ├── ai-process.yaml   # Endpoint /ai/process
      └── analytics.yaml    # Analytics endpoints
```

**Преимущества:**
- ✅ Автогенерация SDK (TypeScript, Python, PHP, Go)
- ✅ Интерактивное тестирование в браузере
- ✅ Автоматическая валидация запросов
- ✅ Легче поддерживать документацию

**Оценка:** 2-3 дня

---

#### 1.2 Версионирование API ⭐⭐⭐

**Цель:** Добавить версионирование для безопасных обновлений

**Задачи:**
- [ ] Создать структуру версий: `/api/v1/`, `/api/v2/`
- [ ] Перенести текущие routes в `/api/v1/`
- [ ] Создать middleware для версионирования
- [ ] Обновить все routes с префиксом версии
- [ ] Добавить заголовок `API-Version` в ответы
- [ ] Обновить документацию

**Структура:**
```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::post('/ai/process', [AIProxyController::class, 'process']);
    // ... остальные routes
});

Route::prefix('v2')->group(function () {
    // Будущие улучшения
});
```

**URL структура:**
```
https://api.siteaccess.ru/api/v1/ai/process
https://api.siteaccess.ru/api/v1/analytics/summary
https://api.siteaccess.ru/api/v2/ai/process (будущее)
```

**Оценка:** 1 день

---

#### 1.3 Улучшение обработки ошибок ⭐⭐

**Цель:** Стандартизировать ошибки по RFC 7807

**Задачи:**
- [ ] Создать единый формат ошибок (RFC 7807 Problem Details)
- [ ] Обновить все Exception классы
- [ ] Добавить коды ошибок для каждого типа
- [ ] Добавить trace_id для отладки
- [ ] Создать документацию по всем ошибкам
- [ ] Добавить в OpenAPI спецификацию

**Формат ошибки (RFC 7807):**
```json
{
  "type": "https://api.siteaccess.ru/docs/errors/invalid-api-key",
  "title": "Invalid API Key",
  "status": 401,
  "detail": "The provided API key is invalid or expired",
  "instance": "/api/v1/ai/process",
  "trace_id": "550e8400-e29b-41d4-a716-446655440000",
  "errors": {
    "api_key": ["The API key format is invalid"]
  }
}
```

**Оценка:** 2 дня

---

### Приоритет 2: Важно для UX (2-3 недели)

#### 2.1 Streaming ответы ⭐⭐

**Цель:** Реализовать потоковую передачу ответов

**Задачи:**
- [ ] Реализовать Server-Sent Events (SSE) для streaming
- [ ] Обновить `AIProxyController` для поддержки stream
- [ ] Обновить `GeminiService` и `OpenAIService` для streaming
- [ ] Добавить endpoint `/api/v1/ai/stream`
- [ ] Обновить документацию с примерами
- [ ] Добавить в OpenAPI спецификацию

**Формат ответа (SSE):**
```
data: {"type":"token","content":"Hello"}

data: {"type":"token","content":" world"}

data: {"type":"done","usage":{"tokens":10}}
```

**Оценка:** 3-4 дня

---

#### 2.2 Multipart/form-data для файлов ⭐⭐

**Цель:** Улучшить работу с большими файлами

**Задачи:**
- [ ] Добавить поддержку `multipart/form-data` в валидацию
- [ ] Создать middleware для обработки multipart
- [ ] Обновить `AIProcessRequest` для поддержки обоих форматов
- [ ] Добавить конвертацию multipart → base64 (для обратной совместимости)
- [ ] Обновить документацию
- [ ] Добавить примеры

**Поддержка обоих форматов:**
```php
// Base64 (текущий)
{
  "files": [{"type": "image", "content": "base64..."}]
}

// Multipart (новый)
Content-Type: multipart/form-data
file1: [binary data]
```

**Оценка:** 2-3 дня

---

#### 2.3 Расширение метаданных ответа ⭐

**Цель:** Добавить больше информации в ответы

**Задачи:**
- [ ] Добавить `model_version` в ответ
- [ ] Добавить `latency_breakdown` (время на каждом этапе)
- [ ] Добавить `request_trace` для отладки
- [ ] Добавить `warnings` (если есть)
- [ ] Обновить `ResponseFormatter`

**Расширенный ответ:**
```json
{
  "success": true,
  "request_id": "...",
  "data": {
    "provider": "gemini",
    "model": "gemini-1.5-pro",
    "model_version": "1.5.0",
    "response": {...}
  },
  "metadata": {
    "processing_time": 2.345,
    "latency_breakdown": {
      "validation": 0.01,
      "key_resolution": 0.05,
      "ai_request": 2.28,
      "formatting": 0.005
    },
    "request_trace": {
      "steps": ["validation", "key_resolution", "ai_request", "formatting"]
    }
  }
}
```

**Оценка:** 1-2 дня

---

### Приоритет 3: Удобство разработчиков (1-2 недели)

#### 3.1 Postman/Insomnia коллекция ⭐

**Цель:** Готовая коллекция для тестирования

**Задачи:**
- [ ] Создать Postman Collection v2.1
- [ ] Добавить все endpoints
- [ ] Добавить переменные окружения
- [ ] Добавить примеры запросов
- [ ] Добавить тесты (автоматические проверки)
- [ ] Создать Insomnia коллекцию (опционально)
- [ ] Добавить в репозиторий

**Структура:**
```
docs/
  ├── postman/
  │   ├── AL_API_Gateway.postman_collection.json
  │   └── AL_API_Gateway.postman_environment.json
  └── insomnia/
      └── AL_API_Gateway.json
```

**Оценка:** 1 день

---

#### 3.2 Тестовая среда (Sandbox) ⭐

**Цель:** Среда для тестирования без реальных ключей

**Задачи:**
- [ ] Создать отдельный endpoint `/api/v1/sandbox/ai/process`
- [ ] Реализовать mock-ответы (без реальных вызовов к AI)
- [ ] Добавить специальный токен для sandbox
- [ ] Ограничить функционал (только тестирование)
- [ ] Добавить в документацию

**Оценка:** 1-2 дня

---

#### 3.3 Endpoint для лимитов ⭐

**Цель:** Программный доступ к лимитам

**Задачи:**
- [ ] Расширить `/api/v1/analytics/limits`
- [ ] Добавить hourly лимиты
- [ ] Добавить информацию о reset времени
- [ ] Добавить прогноз использования

**Расширенный ответ:**
```json
{
  "limits": {
    "daily": {
      "limit": 100,
      "used": 45,
      "remaining": 55,
      "reset_at": "2026-02-07T00:00:00Z"
    },
    "hourly": {
      "limit": 20,
      "used": 5,
      "remaining": 15,
      "reset_at": "2026-02-06T16:00:00Z"
    },
    "monthly": {
      "limit": 100000,
      "used": 25000,
      "remaining": 75000
    }
  }
}
```

**Оценка:** 1 день

---

### Приоритет 4: Интернационализация (1 неделя)

#### 4.1 Английская версия документации ⭐

**Цель:** Поддержка международных пользователей

**Задачи:**
- [ ] Создать `public/index_docs_en.html`
- [ ] Перевести всю документацию
- [ ] Добавить переключатель языка
- [ ] Обновить OpenAPI спецификацию на английском
- [ ] Добавить примеры на английском

**Оценка:** 2-3 дня

---

### Приоритет 5: Расширенная аналитика (1-2 недели)

#### 5.1 Прогнозирование затрат ⭐

**Цель:** Помочь пользователям планировать бюджет

**Задачи:**
- [ ] Создать endpoint `/api/v1/analytics/forecast`
- [ ] Реализовать алгоритм прогнозирования
- [ ] Учитывать исторические данные
- [ ] Добавить в документацию

**Оценка:** 2-3 дня

---

#### 5.2 Агрегация по модели ⭐

**Цель:** Статистика использования моделей

**Задачи:**
- [ ] Расширить `/api/v1/analytics/by-model`
- [ ] Добавить детальную статистику
- [ ] Добавить сравнение моделей
- [ ] Добавить рекомендации

**Оценка:** 1-2 дня

---

#### 5.3 Учет затрат по API-ключам ⭐

**Цель:** Отслеживание расходов по ключам

**Задачи:**
- [ ] Добавить поле `cost_by_key` в аналитику
- [ ] Создать endpoint `/api/v1/analytics/costs/by-key`
- [ ] Визуализация в документации

**Оценка:** 1-2 дня

---

## 📅 Roadmap реализации

### Фаза 1: Фундамент (Неделя 1-2)
- ✅ OpenAPI спецификация
- ✅ Версионирование API
- ✅ Улучшение ошибок

**Результат:** API готов к интеграции, есть спецификация

### Фаза 2: UX улучшения (Неделя 3-4)
- ✅ Streaming ответы
- ✅ Multipart upload
- ✅ Расширенные метаданные

**Результат:** Улучшенный UX, поддержка больших файлов

### Фаза 3: Инструменты разработчика (Неделя 5)
- ✅ Postman коллекция
- ✅ Sandbox среда
- ✅ Endpoint лимитов

**Результат:** Удобные инструменты для разработчиков

### Фаза 4: Интернационализация (Неделя 6)
- ✅ Английская документация

**Результат:** Поддержка международных пользователей

### Фаза 5: Расширенная аналитика (Неделя 7-8)
- ✅ Прогнозирование
- ✅ Агрегация по моделям
- ✅ Учет по ключам

**Результат:** Продвинутая аналитика

---

## 🛠️ Технические детали реализации

### OpenAPI спецификация

**Структура:**
```yaml
openapi: 3.0.3
info:
  title: AL API Gateway
  version: 1.0.0
  description: Unified API for Gemini and OpenAI
servers:
  - url: https://api.siteaccess.ru/api/v1
    description: Production server
paths:
  /ai/process:
    post:
      summary: Process AI request
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/AIRequest'
      responses:
        '200':
          description: Success
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/AIResponse'
        '401':
          $ref: '#/components/responses/Unauthorized'
components:
  schemas:
    AIRequest:
      type: object
      required: [provider, model, prompt]
      properties:
        provider:
          type: string
          enum: [gemini, openai]
        model:
          type: string
        prompt:
          type: string
          minLength: 1
        files:
          type: array
          maxItems: 10
          items:
            $ref: '#/components/schemas/File'
```

### Версионирование

**Middleware:**
```php
class ApiVersionMiddleware
{
    public function handle($request, Closure $next)
    {
        $version = $request->header('API-Version', 'v1');
        $request->route()->setParameter('version', $version);
        $response = $next($request);
        return $response->header('API-Version', $version);
    }
}
```

### Streaming

**Реализация:**
```php
public function stream(AIProcessRequest $request): StreamedResponse
{
    return response()->stream(function () use ($request) {
        $service = $this->getService($request->provider);
        foreach ($service->stream($request) as $chunk) {
            echo "data: " . json_encode($chunk) . "\n\n";
            ob_flush();
            flush();
        }
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'Connection' => 'keep-alive',
    ]);
}
```

---

## 📊 Оценка трудозатрат

| Задача | Приоритет | Время | Сложность |
|--------|-----------|-------|-----------|
| OpenAPI спецификация | ⭐⭐⭐ | 2-3 дня | Средняя |
| Версионирование | ⭐⭐⭐ | 1 день | Низкая |
| Улучшение ошибок | ⭐⭐⭐ | 2 дня | Средняя |
| Streaming | ⭐⭐ | 3-4 дня | Высокая |
| Multipart upload | ⭐⭐ | 2-3 дня | Средняя |
| Расширенные метаданные | ⭐ | 1-2 дня | Низкая |
| Postman коллекция | ⭐ | 1 день | Низкая |
| Sandbox | ⭐ | 1-2 дня | Средняя |
| Endpoint лимитов | ⭐ | 1 день | Низкая |
| EN документация | ⭐ | 2-3 дня | Низкая |
| Прогнозирование | ⭐ | 2-3 дня | Высокая |
| Агрегация по моделям | ⭐ | 1-2 дня | Средняя |
| Учет по ключам | ⭐ | 1-2 дня | Средняя |

**Общее время:** 6-8 недель (при работе 1 разработчика)

---

## 🎯 Критерии успеха

### После Фазы 1:
- ✅ OpenAPI спецификация доступна
- ✅ API версионирован (v1)
- ✅ Стандартизированные ошибки

### После Фазы 2:
- ✅ Streaming работает
- ✅ Multipart upload поддерживается
- ✅ Расширенные метаданные в ответах

### После Фазы 3:
- ✅ Postman коллекция готова
- ✅ Sandbox доступен
- ✅ Лимиты доступны программно

---

## 🚀 Быстрый старт (MVP улучшений)

Если нужно быстро улучшить API, начните с:

1. **OpenAPI спецификация** (2-3 дня) - максимальный эффект
2. **Версионирование** (1 день) - защита от breaking changes
3. **Улучшение ошибок** (2 дня) - лучший UX

**Итого:** 5-6 дней для базовых улучшений

---

## 📝 Следующие шаги

1. ✅ Утвердить план
2. ⏳ Начать с Приоритета 1
3. ⏳ Создать задачи в проекте
4. ⏳ Начать реализацию OpenAPI

**Готов начать реализацию?** 🚀
