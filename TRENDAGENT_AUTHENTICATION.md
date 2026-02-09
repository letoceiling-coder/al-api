# TrendAgent API - Процесс авторизации

## 📋 Обзор

Авторизация для TrendAgent API проходит через SSO (Single Sign-On) систему Trend.tech. Процесс включает получение `app_id`, авторизацию через SSO API и использование полученного `auth_token` для всех последующих запросов к API.

## 🔐 Процесс авторизации

### Шаг 1: Получение app_id

**URL:** `https://sso.trend.tech/login`

**Метод:** `GET`

**Описание:** 
- Выполняется запрос к странице `/login` для получения `app_id` из редиректа или HTML-кода
- `app_id` используется для идентификации приложения при авторизации
- По умолчанию используется `app_id = '66d84f584c0168b8ccd281c3'` или `'66d84ffc4c0168b8ccd281c7'`

**Заголовки:**
```
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8
Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36
```

**Извлечение app_id:**
- Из URL редиректа: `?app_id=...`
- Из HTML-кода страницы (регулярные выражения)
- Из JavaScript-переменных на странице

### Шаг 2: Авторизация через SSO API

**URL:** `https://sso-api.trend.tech/v1/login?app_id={app_id}&lang=ru`

**Метод:** `POST`

**Параметры запроса:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q",
  "client": "web"
}
```

**Заголовки:**
```
Content-Type: application/x-www-form-urlencoded; charset=UTF-8
Origin: https://sso.trend.tech
Referer: https://sso.trend.tech/login?app_id={app_id}
Accept: application/json, text/plain, */*
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36
```

**Ответы:**
- **201 Created** - успешная авторизация
- **302/301 Redirect** - редирект с токеном в Location или cookies
- **403 Forbidden** - ошибка авторизации (но токен может быть в cookies)

### Шаг 3: Извлечение auth_token

Токен авторизации может быть получен из нескольких источников (в порядке приоритета):

1. **Cookies** - `auth_token` в cookies ответа
2. **JSON ответ** - поле `auth_token`, `token`, `access_token` или `data.auth_token`
3. **Location header** - параметр `auth_token` в URL редиректа
4. **X-Auth-Token header** - заголовок ответа

**Пример успешного ответа:**
```json
{
  "auth_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": "...",
    "phone": "+79045393434"
  }
}
```

### Шаг 4: Использование токена в запросах

После получения `auth_token`, он используется во всех последующих запросах к API TrendAgent.

**Способ 1: Параметр URL (основной)**
```
https://api.trendagent.ru/v4_29/blocks/search/?auth_token={auth_token}&city=...&count=20
```

**Способ 2: Заголовок Authorization (опционально)**
```
Authorization: Bearer {auth_token}
```

## 📝 Конфигурация

### Переменные окружения (.env)

```env
TRENDAGENT_PHONE=+79045393434
TRENDAGENT_PASSWORD=nwBvh4q
```

### Классы и методы

**Основной класс:** `App\Services\TrendAgent\TrendSsoApiAuth`

**Методы:**
- `authenticate(string $phone, string $password, ?string $appId = null): array` - выполнение авторизации
- `getAuthToken(): ?string` - получение токена авторизации
- `isAuthenticated(): bool` - проверка статуса авторизации
- `getAuthHeaders(): array` - получение заголовков для авторизованных запросов
- `getCookies(): array` - получение всех cookies

**Обертка:** `App\Services\TrendAgent\TrendAgentApiClient`

Использует `TrendSsoApiAuth` для авторизации и предоставляет упрощенные методы для работы с API.

## 🔄 Пример использования

```php
use App\Services\TrendAgent\TrendSsoApiAuth;

// Создание экземпляра
$auth = new TrendSsoApiAuth();

// Авторизация
$authData = $auth->authenticate('+79045393434', 'nwBvh4q');

if ($authData['authenticated']) {
    // Получение токена
    $token = $auth->getAuthToken();
    
    // Выполнение запросов к API
    $blocks = $auth->getBlocksSearch([
        'city' => '58c665588b6aa52311afa01b',
        'count' => 20,
        'offset' => 0
    ]);
}
```

## 🌐 API Endpoints

После авторизации можно выполнять запросы к следующим API:

### Основные API домены:

1. **API комплексов и квартир:**
   - `https://api.trendagent.ru/v4_29/blocks/search/`
   - `https://api.trendagent.ru/v4_29/blocks/{id}/`
   - `https://api.trendagent.ru/v4_29/flats/{id}/`

2. **API паркингов:**
   - `https://parkings-api.trendagent.ru/search/blocks`
   - `https://parkings-api.trendagent.ru/parkings/{id}/`

3. **API домов:**
   - `https://api.trendagent.ru/v4_29/houses/{id}/`

4. **API участков:**
   - `https://api.trendagent.ru/v4_29/land_plots/{id}/`

5. **API коммерции:**
   - `https://api.trendagent.ru/v4_29/commerce_premises/search/`

6. **API подрядчиков:**
   - `https://api.trendagent.ru/v4_29/contractors/search/`

## 🔒 Безопасность

1. **Cookies:** Все cookies сохраняются в `CookieJar` и автоматически отправляются с каждым запросом
2. **SSL:** В разработке используется `'verify' => false` для обхода проблем с сертификатами
3. **Токен:** `auth_token` добавляется в каждый запрос к API
4. **User-Agent:** Используется реалистичный User-Agent для имитации браузера

## ⚠️ Важные замечания

1. **Форматирование телефона:** Телефон автоматически форматируется в формат `+7XXXXXXXXXX`
2. **Редиректы:** Автоматическое следование редиректам включено (до 10 редиректов)
3. **Таймауты:** Таймаут запросов установлен на 30 секунд
4. **Кеширование:** Результаты запросов могут кешироваться для оптимизации
5. **Обработка ошибок:** При 401 ошибке выполняется переавторизация

## 📊 Структура данных авторизации

```php
[
    'authenticated' => true,
    'tokens' => [
        'auth_token' => '...',
        'access_token' => '...'
    ],
    'cookies' => [...],
    'headers' => [...],
    'session_id' => '...',
    'user' => [...],
    'timestamp' => '2024-...'
]
```

## 🔍 Отладка

Все этапы авторизации логируются через `Log::info()` и `Log::error()`:

- Получение `app_id`
- Отправка запроса авторизации
- Извлечение токена из различных источников
- Выполнение запросов к API

Логи можно найти в `storage/logs/laravel.log`.
