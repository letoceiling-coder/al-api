# Анализ сервисов TrendAgent из проекта figma-trendagent

## 📋 Обзор

В проекте `figma-trendagent` реализована **унифицированная архитектура** для работы с TrendAgent API, которая значительно превосходит текущую реализацию в проекте `AL` по следующим аспектам:

- ✅ Единые сервисы для всех типов объектов
- ✅ Типизированные ошибки
- ✅ Параллельные запросы
- ✅ Retry логика с exponential backoff
- ✅ Управление токенами с кэшированием
- ✅ Унифицированные фильтры
- ✅ Агрегация данных из множественных endpoints
- ✅ Нормализация сущностей

---

## 🔍 Сравнение архитектур

### Текущая архитектура (проект AL)

```
app/Services/TrendAgent/
├── CityService.php           # Управление городами
├── ImageDownloader.php      # Загрузка изображений
├── TrendAgentApiClient.php  # Клиент API (много методов)
└── TrendSsoApiAuth.php      # Авторизация (6097 строк!)
```

**Проблемы:**
- ❌ `TrendSsoApiAuth` содержит всю бизнес-логику (6097 строк)
- ❌ Дублирование кода для каждого типа объекта
- ❌ Нет единой точки входа для каталогов
- ❌ Нет retry логики
- ❌ Нет параллельных запросов
- ❌ Нет типизированных ошибок
- ❌ Нет нормализации сущностей

### Архитектура figma-trendagent

```
app/Services/TrendAgent/
├── Auth/
│   └── AuthTokenManager.php      # Управление токенами (450 строк)
├── Http/
│   ├── HttpClient.php            # Низкоуровневый HTTP (100 строк)
│   ├── ParallelExecutor.php      # Параллельные запросы (128 строк)
│   └── RetryManager.php          # Retry логика (140 строк)
├── Catalog/
│   ├── CatalogService.php        # ЕДИНЫЙ сервис каталогов
│   └── PaginationManager.php    # Управление пагинацией
├── Detail/
│   ├── DetailService.php         # ЕДИНЫЙ сервис деталей
│   ├── DetailAggregator.php      # Агрегация 22 endpoints
│   └── SlugResolver.php          # Slug → ID конвертация
├── Filters/
│   ├── FilterBuilder.php         # Унифицированные фильтры
│   └── FilterRegistry.php        # Реестр фильтров
├── Entities/
│   ├── EntityNormalizer.php      # Нормализация сущностей
│   └── [Entity classes]          # Типизированные сущности
└── Core/
    ├── ObjectType.php            # Enum типов объектов
    └── Errors/                   # Типизированные ошибки
```

**Преимущества:**
- ✅ Разделение ответственности (SRP)
- ✅ Единые сервисы для всех типов
- ✅ Переиспользуемые компоненты
- ✅ Типизированные ошибки
- ✅ Параллельные запросы
- ✅ Retry логика
- ✅ Кэширование токенов

---

## 🎯 Рекомендации по улучшению

### 1. Управление токенами (AuthTokenManager)

**Текущая проблема:**
- `TrendSsoApiAuth` содержит всю логику авторизации (6097 строк)
- Нет кэширования токенов
- Нет автоматического обновления токенов

**Решение:**
Использовать `AuthTokenManager` из figma-trendagent:

```php
// app/Services/TrendAgent/Auth/AuthTokenManager.php

class AuthTokenManager
{
    // Кэширование токенов
    private const TOKEN_CACHE_KEY = 'trendagent_auth_token';
    
    // Автоматическое обновление перед истечением
    private const REFRESH_BEFORE_SECONDS = 60;
    
    public function getValidToken(): string
    {
        // Проверка кэша
        $cached = $this->getFromCache();
        if ($cached !== null) {
            return $cached;
        }
        
        // Обновление токена
        return $this->refreshToken();
    }
    
    // Декодирование JWT для проверки exp
    public function decodeToken(string $token): array
    {
        // Извлечение payload из JWT
    }
}
```

**Преимущества:**
- ✅ Кэширование токенов в Redis/Cache
- ✅ Автоматическое обновление перед истечением
- ✅ Декодирование JWT для проверки exp
- ✅ Меньше запросов к SSO API

---

### 2. HTTP слой с Retry логикой

**Текущая проблема:**
- Нет retry логики при ошибках
- Нет обработки временных сбоев (5xx, 429)
- Нет exponential backoff

**Решение:**
Использовать `HttpClient` + `RetryManager`:

```php
// app/Services/TrendAgent/Http/HttpClient.php
class HttpClient
{
    // ТОЛЬКО HTTP запросы, БЕЗ бизнес-логики
    public function get(string $url, array $headers = []): Response
    {
        return Http::withHeaders($this->buildHeaders($headers))
            ->timeout(30)
            ->get($url);
    }
    
    // Параллельные запросы
    public function getParallel(array $requests): array
    {
        return Http::pool(function ($pool) use ($requests) {
            // ...
        });
    }
}

// app/Services/TrendAgent/Http/RetryManager.php
class RetryManager
{
    private const MAX_RETRIES = 3;
    private const INITIAL_DELAY_MS = 1000;
    
    public function retry(callable $request, int $maxRetries = 3): Response
    {
        // Exponential backoff
        // Обработка retriable ошибок (5xx, 429, 408)
    }
}
```

**Преимущества:**
- ✅ Автоматический retry при временных ошибках
- ✅ Exponential backoff
- ✅ Обработка rate limiting (429)
- ✅ Разделение HTTP и бизнес-логики

---

### 3. Параллельные запросы (ParallelExecutor)

**Текущая проблема:**
- Все запросы выполняются последовательно
- Медленная загрузка детальной информации (22 endpoints для ЖК)

**Решение:**
Использовать `ParallelExecutor`:

```php
// app/Services/TrendAgent/Http/ParallelExecutor.php
class ParallelExecutor
{
    // Выполнить все запросы параллельно
    public function executeAllSettled(array $requests): array
    {
        // Http::pool() для параллельных запросов
        // Возвращает все ответы, включая неудачные
    }
    
    // Получить только успешные
    public function getSuccessful(array $settledResults): array
    
    // Получить только неудачные
    public function getFailed(array $settledResults): array
}
```

**Пример использования:**
```php
// Вместо последовательных запросов:
$block = $client->getBlockDetail($id);
$apartments = $client->getBlockApartments($id);
$parkings = $client->getBlockParkings($id);
// ... 22 запроса

// Параллельно:
$executor = app(ParallelExecutor::class);
$responses = $executor->executeAllSettled([
    'block' => "https://api.trendagent.ru/v4_29/blocks/{$id}/unified/",
    'apartments' => "https://api.trendagent.ru/v4_29/apartments/block/{$id}/search/",
    'parkings' => "https://parkings.trendagent.ru/parkings/block/{$id}",
    // ... все 22 endpoints
]);
```

**Преимущества:**
- ✅ Ускорение загрузки в 10-20 раз
- ✅ Обработка частичных ошибок
- ✅ Возможность работать с частичными данными

---

### 4. Унифицированный CatalogService

**Текущая проблема:**
- Отдельные методы для каждого типа объекта:
  - `getComplexes()`
  - `getApartments()`
  - `getParkings()`
  - `getHouses()`
  - и т.д.

**Решение:**
Единый `CatalogService`:

```php
// app/Services/TrendAgent/Catalog/CatalogService.php
class CatalogService
{
    public function getCatalog(
        ObjectType $objectType,  // Enum: BLOCKS, APARTMENTS, PARKING, etc.
        string $city,
        ?FilterSet $filters = null,
        int $page = 1,
        ?int $pageSize = null,
        ?string $sort = 'price',
        ?string $sortOrder = 'asc'
    ): CatalogResult {
        // Единая логика для всех типов
    }
}
```

**Пример использования:**
```php
$catalogService = app(CatalogService::class);

// Все типы объектов через один метод:
$blocks = $catalogService->getCatalog(ObjectType::BLOCKS, $city);
$apartments = $catalogService->getCatalog(ObjectType::APARTMENTS, $city);
$parkings = $catalogService->getCatalog(ObjectType::PARKING, $city);
```

**Преимущества:**
- ✅ Один метод вместо 7+
- ✅ Единая логика пагинации и фильтрации
- ✅ Легко добавить новый тип объекта

---

### 5. Унифицированный DetailService с агрегацией

**Текущая проблема:**
- Отдельные методы для деталей каждого типа
- Нет агрегации данных из множественных endpoints

**Решение:**
`DetailService` + `DetailAggregator`:

```php
// app/Services/TrendAgent/Detail/DetailService.php
class DetailService
{
    public function getDetail(
        ObjectType $objectType,
        string $id,
        string $city
    ): DetailResult {
        // Для BLOCKS - агрегация 22 endpoints
        if ($objectType === ObjectType::BLOCKS) {
            return $this->getDetailWithAggregation($objectType, $id, $city);
        }
        
        // Для простых типов - один запрос
        return $this->getSimpleDetail($objectType, $id, $city);
    }
}

// app/Services/TrendAgent/Detail/DetailAggregator.php
class DetailAggregator
{
    public function aggregate(ObjectType $objectType, string $id, string $city): array
    {
        // Параллельная загрузка всех endpoints
        $endpoints = $this->getEndpoints($objectType, $id, $city);
        $responses = $this->parallelExecutor->executeAllSettled($endpoints);
        
        // Агрегация данных
        return $this->mergeResponses($responses);
    }
}
```

**Преимущества:**
- ✅ Единый метод для всех типов
- ✅ Автоматическая агрегация для сложных объектов
- ✅ Обработка частичных ошибок (PartialAggregationError)

---

### 6. Унифицированные фильтры (FilterBuilder)

**Текущая проблема:**
- Фильтры передаются как массивы без валидации
- Нет единой логики построения query параметров

**Решение:**
`FilterBuilder` + `FilterRegistry`:

```php
// app/Services/TrendAgent/Filters/FilterBuilder.php
class FilterBuilder
{
    public function createFromArray(ObjectType $objectType, array $filters): FilterSet
    {
        $filterSet = new FilterSet($objectType);
        
        foreach ($filters as $key => $value) {
            // Валидация фильтра
            $definition = $this->registry->get($key);
            if (!$definition->validate($value)) {
                throw new InvalidFilterError("Invalid filter: {$key}");
            }
            
            $filterSet->add($key, $value);
        }
        
        return $filterSet;
    }
    
    // Преобразование в query параметры
    public function toQueryParams(FilterSet $filterSet): array
    {
        // Унифицированная логика для всех типов
    }
}
```

**Пример использования:**
```php
$filterBuilder = app(FilterBuilder::class);

$filters = $filterBuilder->createFromArray(ObjectType::APARTMENTS, [
    'price' => ['from' => 1000000, 'to' => 5000000],
    'room' => [1, 2, 3],
    'district' => ['центральный']
]);

$result = $catalogService->getCatalog(ObjectType::APARTMENTS, $city, $filters);
```

**Преимущества:**
- ✅ Валидация фильтров
- ✅ Единая логика построения query параметров
- ✅ Типизированные ошибки (InvalidFilterError)

---

### 7. Типизированные ошибки

**Текущая проблема:**
- Все ошибки - обычные `Exception`
- Нет различия между retriable и non-retriable ошибками

**Решение:**
Иерархия ошибок:

```php
// app/Services/TrendAgent/Core/Errors/TrendAgentException.php
abstract class TrendAgentException extends \Exception
{
    public function __construct(
        string $message,
        public readonly bool $retriable = false,
        public readonly array $context = []
    ) {
        parent::__construct($message);
    }
}

// app/Services/TrendAgent/Core/Errors/AuthExpiredError.php
class AuthExpiredError extends TrendAgentException
{
    public function __construct(string $message = 'Auth token expired', array $context = [])
    {
        parent::__construct($message, retriable: true, context: $context);
    }
}

// app/Services/TrendAgent/Core/Errors/InvalidFilterError.php
class InvalidFilterError extends TrendAgentException
{
    public function __construct(string $message, array $context = [])
    {
        parent::__construct($message, retriable: false, context: $context);
    }
}

// app/Services/TrendAgent/Core/Errors/PartialAggregationError.php
class PartialAggregationError extends TrendAgentException
{
    public function __construct(
        string $message,
        public readonly array $successfulResponses,
        public readonly array $failedEndpoints
    ) {
        parent::__construct($message, retriable: true);
    }
}
```

**Преимущества:**
- ✅ Типизированные ошибки
- ✅ Автоматическая обработка retriable ошибок
- ✅ Контекст ошибок для отладки

---

### 8. Нормализация сущностей (EntityNormalizer)

**Текущая проблема:**
- Данные из API используются "как есть"
- Нет единого формата для разных типов объектов

**Решение:**
`EntityNormalizer` + Entity классы:

```php
// app/Services/TrendAgent/Entities/EntityNormalizer.php
class EntityNormalizer
{
    public function normalize(ObjectType $objectType, array $data): AbstractEntity
    {
        return match($objectType) {
            ObjectType::APARTMENTS => ApartmentEntity::fromArray($data),
            ObjectType::BLOCKS => BlockEntity::fromArray($data),
            ObjectType::PARKING => ParkingEntity::fromArray($data),
            // ...
        };
    }
}

// app/Services/TrendAgent/Entities/ApartmentEntity.php
class ApartmentEntity extends AbstractEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly Price $price,
        public readonly Area $area,
        public readonly Location $location,
        // ...
    ) {}
    
    public static function fromArray(array $data): self
    {
        // Нормализация данных из API
    }
}
```

**Преимущества:**
- ✅ Единый формат данных
- ✅ Типизированные свойства
- ✅ Value Objects (Price, Area, Location)

---

### 9. PaginationManager

**Текущая проблема:**
- Логика пагинации разбросана по коду
- Нет единого формата pagination metadata

**Решение:**
`PaginationManager`:

```php
// app/Services/TrendAgent/Catalog/PaginationManager.php
class PaginationManager
{
    public function createParams(int $page = 1, ?int $pageSize = null): array
    {
        return [
            'offset' => ($page - 1) * $pageSize,
            'count' => $pageSize,
        ];
    }
    
    public function createMetadata(int $total, int $offset, int $count): array
    {
        return [
            'currentPage' => (int) floor($offset / $count) + 1,
            'totalPages' => (int) ceil($total / $count),
            'total' => $total,
            'hasMore' => ($offset + $count) < $total,
            'from' => $offset + 1,
            'to' => min($offset + $count, $total),
        ];
    }
}
```

**Преимущества:**
- ✅ Единая логика пагинации
- ✅ Стандартизированный формат metadata

---

## 📊 План внедрения

### Этап 1: Базовые компоненты (приоритет: ВЫСОКИЙ)

1. **AuthTokenManager** - кэширование и управление токенами
2. **HttpClient** - низкоуровневый HTTP клиент
3. **RetryManager** - retry логика
4. **ParallelExecutor** - параллельные запросы

**Оценка:** 2-3 дня

### Этап 2: Унифицированные сервисы (приоритет: ВЫСОКИЙ)

5. **CatalogService** - единый сервис каталогов
6. **DetailService** - единый сервис деталей
7. **PaginationManager** - управление пагинацией

**Оценка:** 3-4 дня

### Этап 3: Фильтры и валидация (приоритет: СРЕДНИЙ)

8. **FilterBuilder** - унифицированные фильтры
9. **FilterRegistry** - реестр фильтров
10. Типизированные ошибки

**Оценка:** 2-3 дня

### Этап 4: Нормализация и агрегация (приоритет: СРЕДНИЙ)

11. **EntityNormalizer** - нормализация сущностей
12. **DetailAggregator** - агрегация данных
13. Entity классы

**Оценка:** 3-4 дня

### Этап 5: Миграция существующего кода (приоритет: НИЗКИЙ)

14. Рефакторинг `TrendAgentApiClient`
15. Рефакторинг `TrendSsoApiAuth`
16. Обновление команд парсинга

**Оценка:** 5-7 дней

---

## 🎯 Итоговые преимущества

### Производительность
- ⚡ **Ускорение загрузки деталей в 10-20 раз** (параллельные запросы)
- ⚡ **Меньше запросов к SSO** (кэширование токенов)
- ⚡ **Автоматический retry** при временных ошибках

### Надежность
- 🛡️ **Обработка частичных ошибок** (PartialAggregationError)
- 🛡️ **Автоматическое обновление токенов**
- 🛡️ **Типизированные ошибки** для лучшей отладки

### Поддерживаемость
- 🔧 **Единые сервисы** вместо множества методов
- 🔧 **Разделение ответственности** (SRP)
- 🔧 **Легко добавить новый тип объекта**

### Качество кода
- ✨ **Меньше дублирования** кода
- ✨ **Типизированные сущности** вместо массивов
- ✨ **Валидация фильтров**

---

## 📝 Рекомендации

1. **Начать с Этапа 1** - базовые компоненты дадут максимальный эффект при минимальных затратах
2. **Постепенная миграция** - не переписывать всё сразу, использовать новые сервисы параллельно со старыми
3. **Тестирование** - каждый этап должен быть покрыт тестами
4. **Документация** - обновить документацию после каждого этапа

---

**Дата анализа:** 2024-01-15  
**Версия:** 1.0
