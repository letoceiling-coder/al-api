# План реализации AI API Gateway

## Исходя из ваших требований:
- ✅ **Много пользователей**
- ✅ **Пользователь платит за API** (если флаг запрещает внутренние ключи)
- ✅ **Нужна аналитика** (токены, стоимость)
- ⏳ **Библиотека промптов** - добавим позже

---

## Рекомендованная архитектура: Гибрид Вариантов 1 и 2

### Базовая функциональность (MVP):
1. Управление API ключами через .env флаги
2. Валидация и обработка запросов
3. Унифицированные ответы
4. Поддержка файлов

### Сразу с аналитикой:
1. База данных для логирования
2. Подсчет токенов и стоимости
3. История запросов
4. Лимиты использования

---

## Структура базы данных

```sql
-- Пользователи (уже есть через Sanctum)
-- users table

-- Хранение API ключей пользователей (опционально)
CREATE TABLE user_ai_keys (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    provider VARCHAR(20) NOT NULL,  -- 'gemini', 'openai'
    api_key TEXT NOT NULL,          -- зашифрованный ключ
    label VARCHAR(100),              -- название ключа для удобства
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_provider (user_id, provider)
);

-- Логи всех AI запросов
CREATE TABLE ai_request_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    request_id VARCHAR(36) UNIQUE,   -- UUID для отслеживания
    provider VARCHAR(20) NOT NULL,   -- 'gemini', 'openai'
    model VARCHAR(100) NOT NULL,     -- 'gpt-4', 'gemini-1.5-pro'
    
    -- Данные запроса
    prompt_length INT,
    has_files BOOLEAN DEFAULT false,
    file_count INT DEFAULT 0,
    
    -- Токены и стоимость
    prompt_tokens INT,
    completion_tokens INT,
    total_tokens INT,
    estimated_cost DECIMAL(10, 6),   -- в долларах
    
    -- Производительность
    processing_time FLOAT,            -- в секундах
    
    -- Статус
    status VARCHAR(20),               -- 'success', 'error', 'rate_limited'
    error_message TEXT,
    
    -- Метаданные
    ip_address VARCHAR(45),
    user_agent TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, created_at),
    INDEX idx_provider_model (provider, model),
    INDEX idx_status (status)
);

-- Использование пользователем (для дашборда)
CREATE TABLE user_usage_stats (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    date DATE NOT NULL,
    
    -- Статистика
    total_requests INT DEFAULT 0,
    successful_requests INT DEFAULT 0,
    failed_requests INT DEFAULT 0,
    
    -- Токены
    total_tokens INT DEFAULT 0,
    total_cost DECIMAL(10, 4) DEFAULT 0,
    
    -- По провайдерам
    gemini_requests INT DEFAULT 0,
    openai_requests INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, date),
    INDEX idx_date (date)
);

-- Лимиты пользователей (опционально)
CREATE TABLE user_limits (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    
    -- Лимиты
    daily_request_limit INT DEFAULT 100,
    monthly_token_limit INT DEFAULT 100000,
    max_file_size_mb INT DEFAULT 10,
    
    -- Текущее использование
    today_requests INT DEFAULT 0,
    today_reset_at DATE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id)
);
```

---

## .env конфигурация

```env
# ============================================
# API Key Management
# ============================================

# Разрешить использование внутренних ключей (из .env)
ALLOW_INTERNAL_API_KEYS=true

# Требовать от пользователя свои ключи
REQUIRE_USER_API_KEYS=false

# Разрешить передачу ключей в теле запроса
ALLOW_API_KEY_IN_REQUEST=true

# Разрешить хранение ключей пользователей в БД
ALLOW_USER_KEY_STORAGE=true

# ============================================
# AI Provider Keys (Internal)
# ============================================
GEMINI_API_KEY=your_gemini_key_here
OPENAI_API_KEY=your_openai_key_here

# ============================================
# Usage Limits (Default for new users)
# ============================================
DEFAULT_DAILY_REQUEST_LIMIT=100
DEFAULT_MONTHLY_TOKEN_LIMIT=100000
DEFAULT_MAX_FILE_SIZE_MB=10

# ============================================
# Pricing (for cost calculation)
# ============================================
# Gemini pricing (per 1M tokens)
GEMINI_PRO_INPUT_PRICE=0.50
GEMINI_PRO_OUTPUT_PRICE=1.50
GEMINI_FLASH_INPUT_PRICE=0.10
GEMINI_FLASH_OUTPUT_PRICE=0.30

# OpenAI pricing (per 1M tokens)
GPT4_TURBO_INPUT_PRICE=10.00
GPT4_TURBO_OUTPUT_PRICE=30.00
GPT35_TURBO_INPUT_PRICE=0.50
GPT35_TURBO_OUTPUT_PRICE=1.50

# ============================================
# Features
# ============================================
ENABLE_REQUEST_LOGGING=true
ENABLE_USAGE_ANALYTICS=true
ENABLE_RATE_LIMITING=true
ENABLE_COST_TRACKING=true
```

---

## Структура кода

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AIProxyController.php        # Основной контроллер
│   │       ├── UserKeysController.php       # Управление ключами
│   │       └── AnalyticsController.php      # Аналитика
│   │
│   ├── Middleware/
│   │   ├── ValidateApiKeyAccess.php        # Проверка доступа к ключам
│   │   ├── RateLimitAI.php                 # Rate limiting
│   │   └── TrackUsage.php                  # Отслеживание использования
│   │
│   └── Requests/
│       ├── AIProcessRequest.php            # Валидация запросов
│       ├── StoreUserKeyRequest.php
│       └── UpdateUserKeyRequest.php
│
├── Services/
│   ├── AI/
│   │   ├── Contracts/
│   │   │   └── AIProviderInterface.php
│   │   ├── GeminiService.php
│   │   ├── OpenAIService.php
│   │   ├── ApiKeyResolver.php              # Определение какой ключ использовать
│   │   └── ProviderFactory.php             # Фабрика провайдеров
│   │
│   ├── Analytics/
│   │   ├── UsageTracker.php                # Отслеживание использования
│   │   ├── CostCalculator.php              # Расчет стоимости
│   │   └── StatisticsService.php           # Статистика
│   │
│   └── Response/
│       ├── ResponseFormatter.php           # Форматирование ответов
│       └── ErrorHandler.php                # Обработка ошибок
│
├── Models/
│   ├── UserAIKey.php
│   ├── AIRequestLog.php
│   ├── UserUsageStats.php
│   └── UserLimit.php
│
├── DTOs/
│   ├── AIRequest.php                       # Data Transfer Object
│   ├── AIResponse.php
│   └── UsageStats.php
│
└── Exceptions/
    ├── InvalidApiKeyException.php
    ├── RateLimitExceededException.php
    ├── InsufficientCreditsException.php
    └── AIProviderException.php
```

---

## API Endpoints

### 1. Обработка AI запросов
```
POST /api/ai/process
Headers: Authorization: Bearer {sanctum_token}
```

### 2. Управление ключами пользователя
```
GET    /api/user/keys              # Список ключей
POST   /api/user/keys              # Добавить ключ
PUT    /api/user/keys/{id}         # Обновить ключ
DELETE /api/user/keys/{id}         # Удалить ключ
```

### 3. Аналитика
```
GET /api/user/analytics/summary    # Общая статистика
GET /api/user/analytics/history    # История запросов
GET /api/user/analytics/costs      # Стоимость использования
GET /api/user/analytics/limits     # Текущие лимиты
```

---

## Логика выбора API ключа (Priority Order)

```php
1. Проверить флаг REQUIRE_USER_API_KEYS
   └─ Если true → требовать ключ от пользователя

2. Проверить наличие ключа в запросе (user_api_key)
   └─ Если есть и ALLOW_API_KEY_IN_REQUEST=true → использовать

3. Проверить сохраненные ключи пользователя в БД
   └─ Если есть и ALLOW_USER_KEY_STORAGE=true → использовать

4. Проверить флаг ALLOW_INTERNAL_API_KEYS
   └─ Если true → использовать ключ из .env
   └─ Если false → вернуть ошибку "API key required"
```

---

## Формат запроса (финальный)

```json
POST /api/ai/process
Authorization: Bearer {sanctum_token}

{
  "provider": "gemini",              // required: 'gemini' | 'openai'
  "model": "gemini-1.5-pro",         // required
  "prompt": "Analyze this image",    // required
  
  // Опционально: API ключ (если требуется)
  "user_api_key": "AIza...",
  
  // Опционально: использовать сохраненный ключ
  "use_saved_key": true,
  "saved_key_id": 5,
  
  // Параметры модели
  "parameters": {
    "temperature": 0.7,
    "max_tokens": 1000,
    "top_p": 1.0,
    "stream": false
  },
  
  // Файлы (для мультимодальных моделей)
  "files": [
    {
      "type": "image",               // 'image' | 'audio' | 'document'
      "content": "base64_string",    // или URL
      "mime_type": "image/jpeg",
      "name": "photo.jpg"            // опционально
    }
  ],
  
  // Метаданные (опционально, для вашей аналитики)
  "metadata": {
    "task_type": "image_analysis",
    "project_id": "proj_123"
  }
}
```

---

## Формат ответа (финальный)

```json
{
  "success": true,
  "request_id": "550e8400-e29b-41d4-a716-446655440000",
  
  // Данные ответа
  "data": {
    "provider": "gemini",
    "model": "gemini-1.5-pro",
    "response": {
      "text": "Generated response text...",
      "finish_reason": "stop"        // 'stop' | 'length' | 'content_filter'
    }
  },
  
  // Использование токенов
  "usage": {
    "prompt_tokens": 150,
    "completion_tokens": 300,
    "total_tokens": 450,
    "estimated_cost": 0.000675       // в долларах
  },
  
  // Метаданные
  "metadata": {
    "processing_time": 2.345,        // секунды
    "timestamp": "2026-02-06T14:30:00Z",
    "api_key_source": "internal"     // 'internal' | 'user_request' | 'user_saved'
  },
  
  // Лимиты пользователя
  "limits": {
    "daily_requests_used": 45,
    "daily_requests_limit": 100,
    "daily_requests_remaining": 55
  }
}
```

---

## Начнем реализацию?

**План действий:**

### Этап 1: База (1-2 часа)
1. ✅ Создать миграции для таблиц
2. ✅ Создать модели
3. ✅ Настроить .env

### Этап 2: Ключевая логика (2-3 часа)
1. ✅ ApiKeyResolver - выбор ключа
2. ✅ AIProxyController - обработка запросов
3. ✅ Валидация запросов

### Этап 3: Аналитика (1-2 часа)
1. ✅ UsageTracker - отслеживание
2. ✅ CostCalculator - расчет стоимости
3. ✅ Middleware для логирования

### Этап 4: Документация (1 час)
1. ✅ API документация
2. ✅ Примеры запросов
3. ✅ Postman коллекция

**Готов начать? Или есть вопросы по плану?**
