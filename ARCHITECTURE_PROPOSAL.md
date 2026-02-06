# Архитектура API Gateway для AI моделей

## Анализ требований

### Основные функции:
1. **Контроль доступа к API ключам**
   - Флаг в .env разрешает/запрещает использование внутренних ключей
   - Возможность использовать пользовательские ключи

2. **Обработка запросов**
   - Аутентификация через Sanctum токен
   - Выбор модели и провайдера (Gemini/OpenAI)
   - Промпт и параметры
   - Файлы для мультимодальных запросов

3. **Валидация и ответы**
   - Специфичная валидация для каждой модели
   - Унифицированный формат ответов
   - Обработка ошибок

## Вариант 1: Базовая архитектура (Рекомендуется для старта)

### Концепция
Простой API Gateway с гибким управлением ключами и базовой валидацией.

### Структура

```
┌─────────────┐
│   Client    │
└──────┬──────┘
       │ Bearer Token + Request
       ▼
┌─────────────────────────────────┐
│     Laravel Sanctum Auth        │
└──────┬──────────────────────────┘
       │
       ▼
┌─────────────────────────────────┐
│   API Controller                │
│   - Validate Request            │
│   - Check API Key Access        │
└──────┬──────────────────────────┘
       │
       ▼
┌─────────────────────────────────┐
│   API Key Resolver              │
│   - Check .env flag             │
│   - Select key source           │
└──────┬──────────────────────────┘
       │
       ▼
┌─────────────────────────────────┐
│   AI Service (Gemini/OpenAI)    │
│   - Execute API call            │
│   - Handle response             │
└──────┬──────────────────────────┘
       │
       ▼
┌─────────────────────────────────┐
│   Response Formatter            │
│   - Normalize response          │
│   - Add metadata                │
└──────┬──────────────────────────┘
       │
       ▼
┌─────────────┐
│   Client    │
└─────────────┘
```

### .env конфигурация

```env
# API Key Management
ALLOW_INTERNAL_API_KEYS=true         # Разрешить использование ключей из .env
REQUIRE_USER_API_KEYS=false          # Требовать ключи от пользователя
ALLOW_API_KEY_IN_REQUEST=true        # Разрешить передачу ключей в запросе

# AI Provider Keys (internal)
GEMINI_API_KEY=your_key
OPENAI_API_KEY=your_key
```

### Формат запроса

```json
{
  "provider": "gemini|openai",
  "model": "gemini-1.5-pro|gpt-4-turbo",
  "prompt": "Your prompt here",
  "user_api_key": "optional_if_ALLOW_INTERNAL_API_KEYS=false",
  "parameters": {
    "temperature": 0.7,
    "max_tokens": 1000,
    "top_p": 1.0
  },
  "files": [
    {
      "type": "image|audio|document",
      "content": "base64_encoded_content",
      "mime_type": "image/jpeg"
    }
  ],
  "stream": false
}
```

### Формат ответа (унифицированный)

```json
{
  "success": true,
  "provider": "gemini",
  "model": "gemini-1.5-pro",
  "response": {
    "text": "Generated response...",
    "finish_reason": "stop",
    "usage": {
      "prompt_tokens": 100,
      "completion_tokens": 200,
      "total_tokens": 300
    }
  },
  "metadata": {
    "request_id": "uuid",
    "processing_time": 1.234,
    "timestamp": "2026-02-06T12:00:00Z"
  }
}
```

---

## Вариант 2: Продвинутая архитектура (Для масштабирования)

### Дополнительные функции:
1. **Пользовательские ключи в БД**
   - Хранение ключей пользователей
   - Шифрование ключей
   - Управление несколькими ключами

2. **Система промптов**
   - Библиотека предопределенных промптов
   - Шаблоны с переменными
   - Версионирование

3. **Аналитика и лимиты**
   - Учет использования токенов
   - Rate limiting
   - Логирование запросов

### База данных

```sql
-- Таблица для хранения API ключей пользователей
CREATE TABLE user_api_keys (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    provider VARCHAR(50),  -- 'gemini', 'openai'
    api_key VARCHAR(255) ENCRYPTED,
    is_active BOOLEAN DEFAULT true,
    daily_limit INT,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Таблица промптов
CREATE TABLE prompt_templates (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    name VARCHAR(255),
    template TEXT,
    variables JSON,
    is_public BOOLEAN DEFAULT false,
    category VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Таблица логов запросов
CREATE TABLE ai_request_logs (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    provider VARCHAR(50),
    model VARCHAR(100),
    prompt_length INT,
    response_length INT,
    tokens_used INT,
    processing_time FLOAT,
    status VARCHAR(50),
    error_message TEXT,
    created_at TIMESTAMP
);
```

---

## Вариант 3: Микросервисная архитектура (Для высоких нагрузок)

### Компоненты:
1. **API Gateway** (Laravel)
2. **AI Processor Service** (Python/Node.js)
3. **File Storage Service** (S3/MinIO)
4. **Queue System** (Redis/RabbitMQ)
5. **Analytics Service**

```
┌─────────┐         ┌──────────────┐         ┌─────────────┐
│ Client  │────────>│ API Gateway  │────────>│   Queue     │
└─────────┘         │   (Laravel)  │         └──────┬──────┘
                    └──────────────┘                │
                                                    │
                    ┌──────────────┐                │
                    │   Worker     │<───────────────┘
                    │  (Process)   │
                    └──────┬───────┘
                           │
                    ┌──────▼───────┐
                    │  AI Service  │
                    │ Gemini/OpenAI│
                    └──────────────┘
```

---

## Рекомендации по реализации

### Этап 1: MVP (Вариант 1)
**Срок: 1-2 дня**

1. Добавить флаги в .env
2. Создать Middleware для проверки API ключей
3. Реализовать ApiKeyResolver
4. Унифицировать ответы
5. Создать базовую документацию

**Преимущества:**
- Быстрый старт
- Простота поддержки
- Легко тестировать

### Этап 2: Расширение (Вариант 2)
**Срок: 3-5 дней**

1. Добавить таблицы в БД
2. Реализовать управление ключами
3. Создать систему промптов
4. Добавить аналитику
5. Улучшить валидацию

**Преимущества:**
- Многопользовательская система
- Управление ресурсами
- Аналитика использования

### Этап 3: Масштабирование (Вариант 3)
**Срок: 1-2 недели**

1. Разделить на микросервисы
2. Добавить очереди
3. Реализовать кэширование
4. Оптимизировать производительность

**Преимущества:**
- Высокая производительность
- Горизонтальное масштабирование
- Отказоустойчивость

---

## Моя рекомендация

**Начать с Варианта 1**, но спроектировать код так, чтобы легко можно было расширить до Варианта 2.

### Ключевые принципы:
1. **Single Responsibility** - каждый класс отвечает за одну задачу
2. **Open/Closed** - легко добавлять новые модели без изменения существующего кода
3. **Dependency Injection** - все зависимости через конструктор
4. **Interface Segregation** - абстракции для провайдеров AI

### Структура кода:
```
app/
├── Http/
│   ├── Controllers/Api/
│   │   └── AIProxyController.php
│   ├── Middleware/
│   │   └── ValidateApiKeyAccess.php
│   └── Requests/
│       ├── GeminiRequest.php
│       └── OpenAIRequest.php
├── Services/
│   ├── AI/
│   │   ├── Contracts/
│   │   │   └── AIProviderInterface.php
│   │   ├── GeminiService.php
│   │   ├── OpenAIService.php
│   │   └── ApiKeyResolver.php
│   └── Response/
│       └── ResponseFormatter.php
├── DTOs/
│   ├── AIRequest.php
│   └── AIResponse.php
└── Exceptions/
    ├── InvalidApiKeyException.php
    └── AIProviderException.php
```

---

## Вопросы для уточнения:

1. **Масштаб**: Сколько пользователей планируется? (10, 100, 1000+)
2. **Бюджет**: Будете использовать свои ключи или пользователи будут предоставлять свои?
3. **Функционал**: Нужна ли аналитика использования сразу?
4. **Файлы**: Какой максимальный размер файлов для обработки?
5. **Безопасность**: Нужно ли шифровать хранимые ключи?

**Давайте начнем с Варианта 1 и я реализую его прямо сейчас?**
