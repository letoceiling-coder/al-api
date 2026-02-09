# TrendAgent API - Полная документация по авторизации и работе с API

## 📋 Содержание

1. [Обзор системы авторизации](#обзор-системы-авторизации)
2. [Процесс авторизации (пошагово)](#процесс-авторизации-пошагово)
3. [Хранение данных авторизации](#хранение-данных-авторизации)
4. [Выполнение запросов к API](#выполнение-запросов-к-api)
5. [Все API Endpoints](#все-api-endpoints)
6. [Примеры использования](#примеры-использования)
7. [Обработка ошибок](#обработка-ошибок)

---

## 🔐 Обзор системы авторизации

TrendAgent использует **SSO (Single Sign-On)** систему через `sso.trend.tech` и `sso-api.trend.tech` для авторизации. После успешной авторизации вы получаете `auth_token`, который используется во всех последующих запросах к API.

### Архитектура авторизации

```
┌─────────────────┐
│  Приложение     │
│  (Laravel)      │
└────────┬────────┘
         │
         │ 1. GET /login (получение app_id)
         ▼
┌─────────────────┐
│  sso.trend.tech │
│  /login         │
└────────┬────────┘
         │
         │ 2. POST /v1/login (phone + password)
         ▼
┌─────────────────┐
│ sso-api.trend.  │
│ tech/v1/login   │
└────────┬────────┘
         │
         │ 3. auth_token в ответе
         ▼
┌─────────────────┐
│  auth_token     │
│  (сохраняется)  │
└────────┬────────┘
         │
         │ 4. Используется в запросах
         ▼
┌─────────────────┐
│ api.trendagent. │
│ ru/v4_29/...    │
└─────────────────┘
```

### Основные компоненты

- **SSO Base URL:** `https://sso.trend.tech`
- **SSO API URL:** `https://sso-api.trend.tech`
- **App ID:** `66d84f584c0168b8ccd281c3` (по умолчанию)
- **API Base URL:** `https://api.trendagent.ru/v4_29/`

---

## 🔄 Процесс авторизации (пошагово)

### Шаг 1: Получение app_id

**Цель:** Получить `app_id` из страницы логина для использования в запросе авторизации.

**URL:** `https://sso.trend.tech/login`

**Метод:** `GET`

**Заголовки:**
```http
GET /login HTTP/1.1
Host: sso.trend.tech
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8
Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36
Sec-Fetch-Dest: document
Sec-Fetch-Mode: navigate
Sec-Fetch-Site: none
```

**Извлечение app_id:**

`app_id` может быть получен из:
1. **URL редиректа:** `https://sso.trend.tech/login?app_id=66d84f584c0168b8ccd281c3`
2. **HTML-кода страницы:** поиск по регулярным выражениям
3. **JavaScript переменных:** `app_id: "66d84f584c0168b8ccd281c3"`

**Регулярные выражения для поиска:**
```php
'/[?&]app_id[=:]\s*([a-f0-9]{24})/i'
'/href=["\'][^"\']*app_id[=:]([a-f0-9]{24})/i'
'/app_id[=:]\s*["\']?([a-f0-9]{24})["\']?/i'
```

**По умолчанию используется:** `66d84f584c0168b8ccd281c3` или `66d84ffc4c0168b8ccd281c7`

### Шаг 2: Авторизация через SSO API

**URL:** `https://sso-api.trend.tech/v1/login?app_id={app_id}&lang=ru`

**Метод:** `POST`

**Content-Type:** `application/x-www-form-urlencoded; charset=UTF-8`

**Тело запроса:**
```
phone=+79045393434
password=nwBvh4q
client=web
```

**Заголовки:**
```http
POST /v1/login?app_id=66d84f584c0168b8ccd281c3&lang=ru HTTP/1.1
Host: sso-api.trend.tech
Content-Type: application/x-www-form-urlencoded; charset=UTF-8
Origin: https://sso.trend.tech
Referer: https://sso.trend.tech/login?app_id=66d84f584c0168b8ccd281c3
Accept: application/json, text/plain, */*
Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36
Sec-Fetch-Dest: empty
Sec-Fetch-Mode: cors
Sec-Fetch-Site: same-site
```

**Важно:** 
- Автоматические редиректы **отключены** (`allow_redirects: false`) для получения cookies
- Cookies автоматически сохраняются в `CookieJar`

**Возможные ответы:**

1. **201 Created** - успешная авторизация
2. **302/301 Redirect** - редирект с токеном в Location или cookies
3. **403 Forbidden** - ошибка авторизации (но токен может быть в cookies)

### Шаг 3: Извлечение auth_token

Токен авторизации извлекается из нескольких источников (в порядке приоритета):

#### 1. Cookies ответа
```php
foreach ($cookies as $name => $cookie) {
    if ($name === 'auth_token' || stripos($name, 'auth') !== false) {
        $authToken = $cookie['value'];
        break;
    }
}
```

#### 2. JSON ответ
```json
{
  "auth_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token": "...",
  "access_token": "...",
  "data": {
    "auth_token": "..."
  }
}
```

**Порядок проверки полей:**
1. `auth_token`
2. `token`
3. `access_token`
4. `data.auth_token`
5. `data.token`

#### 3. Location header (редирект)
```
Location: https://spb.trendagent.ru/?auth_token=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

Извлекается из query параметров URL редиректа.

#### 4. X-Auth-Token header
```http
X-Auth-Token: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### Шаг 4: Форматирование телефона

Телефон автоматически форматируется в формат `+7XXXXXXXXXX`:

```php
// Входные форматы:
"+7 999 637 11 82" → "+79996371182"
"8 999 637 11 82"  → "+79996371182"
"79996371182"      → "+79996371182"
"9996371182"       → "9996371182" (без изменений)
```

---

## 💾 Хранение данных авторизации

### Структура хранения

Данные авторизации хранятся в объекте `TrendSsoApiAuth` в следующих свойствах:

#### 1. CookieJar (GuzzleHttp\Cookie\CookieJar)

**Тип:** `CookieJar`

**Содержимое:**
- Все cookies, полученные от сервера
- Автоматически отправляются с каждым запросом
- Управляются Guzzle автоматически

**Пример cookies:**
```php
[
    'auth_token' => [
        'value' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...',
        'domain' => '.trend.tech',
        'path' => '/',
        'expires' => 1735689600
    ],
    'session_id' => [
        'value' => 'abc123...',
        'domain' => '.trend.tech',
        'path' => '/',
        'expires' => null
    ]
]
```

#### 2. authData (массив)

**Тип:** `array`

**Структура:**
```php
[
    'authenticated' => true,
    'tokens' => [
        'auth_token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...',
        'access_token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...'
    ],
    'cookies' => [...],  // Все cookies в виде массива
    'headers' => [...],  // Заголовки для авторизованных запросов
    'session_id' => 'abc123...',
    'current_url' => 'https://spb.trendagent.ru/?auth_token=...',
    'timestamp' => '2024-01-15T10:30:00+00:00',
    'api_response' => [...],  // Полный ответ от API
    'user' => [
        'id' => '...',
        'phone' => '+79045393434',
        'name' => '...'
    ],
    'status_code' => 201
]
```

#### 3. Client (GuzzleHttp\Client)

**Тип:** `Client`

**Конфигурация:**
```php
[
    'cookies' => $cookieJar,  // Автоматическое управление cookies
    'allow_redirects' => [
        'max' => 10,
        'strict' => false,
        'referer' => true,
        'protocols' => ['http', 'https'],
        'track_redirects' => true
    ],
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept' => 'application/json, text/plain, */*',
        'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7'
    ],
    'verify' => false,  // Отключена проверка SSL (для разработки)
    'timeout' => 30
]
```

### Методы доступа к данным

```php
// Получить токен
$token = $auth->getAuthToken();  // Возвращает auth_token или access_token

// Получить все cookies
$cookies = $auth->getCookies();  // Массив cookies

// Получить заголовки для запросов
$headers = $auth->getAuthHeaders();  // Включает Authorization: Bearer {token}

// Проверить статус авторизации
$isAuth = $auth->isAuthenticated();  // true/false

// Получить все данные авторизации
$authData = $auth->getAuthData();  // Полный массив authData
```

### Время жизни токена

- Токен хранится в памяти объекта `TrendSsoApiAuth`
- При перезапуске приложения требуется повторная авторизация
- Cookies могут иметь срок действия (expires), но обычно сессионные
- При получении 401 ошибки выполняется автоматическая переавторизация

---

## 🌐 Выполнение запросов к API

### Общий формат запросов

После авторизации все запросы к API TrendAgent выполняются с использованием `auth_token`.

### Способ 1: Токен в URL параметрах (основной)

**Формат:**
```
https://api.trendagent.ru/v4_29/{endpoint}/?auth_token={token}&param1=value1&param2=value2
```

**Пример:**
```http
GET /v4_29/blocks/search/?auth_token=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...&city=58c665588b6aa52311afa01b&count=20&offset=0 HTTP/1.1
Host: api.trendagent.ru
Accept: application/json, text/plain, */*
Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7
Origin: https://spb.trendagent.ru
Referer: https://spb.trendagent.ru/
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36
```

### Способ 2: Токен в заголовке Authorization (опционально)

**Формат:**
```http
Authorization: Bearer {auth_token}
```

**Пример:**
```http
GET /v4_29/blocks/search/?city=58c665588b6aa52311afa01b&count=20 HTTP/1.1
Host: api.trendagent.ru
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Accept: application/json, text/plain, */*
```

**Примечание:** Основной способ - добавление токена в URL параметры.

### Стандартные заголовки запросов

Все запросы к API включают следующие заголовки:

```http
Accept: application/json, text/plain, */*
Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7
Accept-Encoding: gzip, deflate, br, zstd
Origin: https://{region}.trendagent.ru
Referer: https://{region}.trendagent.ru/
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36
Sec-Ch-Ua: "Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"
Sec-Ch-Ua-Mobile: ?0
Sec-Ch-Ua-Platform: "Windows"
Sec-Fetch-Dest: empty
Sec-Fetch-Mode: cors
Sec-Fetch-Site: same-site
Priority: u=1, i
```

**Origin и Referer** зависят от региона:
- `https://spb.trendagent.ru` - Санкт-Петербург
- `https://msk.trendagent.ru` - Москва
- `https://krasnodar.trendagent.ru` - Краснодар
- и т.д.

### Cookies в запросах

Все cookies из `CookieJar` автоматически отправляются с каждым запросом через Guzzle.

### Обработка ответов

**Успешный ответ (200 OK):**
```json
{
  "errors": null,
  "data": {
    "results": [...],
    "total": 100,
    "blocksCount": 50,
    "apartmentsCount": 1000
  }
}
```

**Ошибка авторизации (401 Unauthorized):**
- Выполняется автоматическая переавторизация
- Запрос повторяется с новым токеном

**Ошибка валидации (400 Bad Request):**
```json
{
  "errors": [
    {
      "field": "city",
      "message": "Invalid city ID"
    }
  ]
}
```

**Not Modified (304):**
- Данные не изменились
- Используется кэш (если включен)

---

## 📡 Все API Endpoints

### Базовые URL

| Сервис | Base URL |
|--------|----------|
| Основной API | `https://api.trendagent.ru/v4_29/` |
| Паркинги | `https://parkings-api.trendagent.ru/` |
| Дома/Участки | `https://house-api.trendagent.ru/v1/` |
| Коммерция | `https://commerce.trendagent.ru/` |
| SSO | `https://sso-api.trend.tech/v1/` |

### 1. Комплексы (ЖК) и Квартиры

#### 1.1. Поиск комплексов
```
GET https://api.trendagent.ru/v4_29/blocks/search/
```

**Параметры:**
- `auth_token` (required) - токен авторизации
- `show_type` - `list` (по умолчанию)
- `sort` - `price`, `name`, `date`
- `sort_order` - `asc`, `desc`
- `count` - количество результатов (по умолчанию 20)
- `offset` - смещение для пагинации (по умолчанию 0)
- `city` - ID города (required)
- `lang` - `ru`, `en` (по умолчанию `ru`)
- `room` - массив комнат: `[1, 2, 3]` или `room=1&room=2&room=3`

**Пример:**
```
GET /v4_29/blocks/search/?auth_token=...&city=58c665588b6aa52311afa01b&count=20&offset=0&sort=price&sort_order=asc&lang=ru
```

#### 1.2. Детальная информация о комплексе
```
GET https://api.trendagent.ru/v4_29/blocks/{blockId}/unified/
```

**Параметры:**
- `auth_token` (required)
- `lang` - `ru`, `en`

#### 1.3. Поиск квартир
```
GET https://api.trendagent.ru/v4_29/apartments/search/
```

**Параметры:**
- `auth_token` (required)
- `city` - ID города (required)
- `count` - количество результатов
- `offset` - смещение
- `sort` - `price`, `area`, `floor`
- `sort_order` - `asc`, `desc`
- `room` - массив комнат
- `price_from`, `price_to` - диапазон цен
- `area_from`, `area_to` - диапазон площади
- `floor_from`, `floor_to` - диапазон этажей
- `lang` - `ru`, `en`

#### 1.4. Детальная информация о квартире
```
GET https://api.trendagent.ru/v4_29/apartments/{apartmentId}/
```

**Параметры:**
- `auth_token` (required)
- `lang` - `ru`, `en`

#### 1.5. Квартиры в комплексе
```
GET https://api.trendagent.ru/v4_29/apartments/block/{blockId}/search/
```

**Параметры:**
- `auth_token` (required)
- `count`, `offset` - пагинация
- `sort`, `sort_order` - сортировка
- `room` - фильтр по комнатам
- `lang` - `ru`, `en`

### 2. Паркинги

#### 2.1. Поиск паркингов
```
GET https://parkings-api.trendagent.ru/search/blocks
```

**Параметры:**
- `auth_token` (required)
- `city` - ID города
- `count`, `offset` - пагинация
- `sort` - `price`, `name`
- `sort_order` - `asc`, `desc`
- `lang` - `ru`, `en`

#### 2.2. Детальная информация о паркинге
```
GET https://parkings-api.trendagent.ru/parkings/{parkingId}/
```

**Параметры:**
- `auth_token` (required)
- `lang` - `ru`, `en`

#### 2.3. Места в паркинге
```
GET https://parkings-api.trendagent.ru/parkings/{parkingId}/places/
```

**Параметры:**
- `auth_token` (required)
- `count`, `offset` - пагинация
- `sort`, `sort_order` - сортировка
- `lang` - `ru`, `en`

### 3. Дома и Участки

#### 3.1. Поиск домов
```
GET https://api.trendagent.ru/v4_29/blocks/search/
```

**Параметры:**
- `auth_token` (required)
- `city` - ID города
- `room` - `[30, 40]` (30 - коттеджи, 40 - таунхаусы)
- `count`, `offset` - пагинация
- `sort`, `sort_order` - сортировка
- `lang` - `ru`, `en`

#### 3.2. Детальная информация о доме
```
GET https://api.trendagent.ru/v4_29/blocks/{blockId}/unified/
```

**Параметры:**
- `auth_token` (required)
- `lang` - `ru`, `en`

#### 3.3. Поиск участков
```
GET https://house-api.trendagent.ru/v1/search/plots
```

**Параметры:**
- `auth_token` (required)
- `city` - ID города
- `count`, `offset` - пагинация
- `sort`, `sort_order` - сортировка
- `lang` - `ru`, `en`

#### 3.4. Детальная информация об участке
```
GET https://house-api.trendagent.ru/v1/plots/{plotId}
```

**Параметры:**
- `auth_token` (required)
- `lang` - `ru`, `en`

#### 3.5. Поиск поселков
```
GET https://house-api.trendagent.ru/v1/search/villages
```

**Параметры:**
- `auth_token` (required)
- `city` - ID города
- `count`, `offset` - пагинация
- `sort`, `sort_order` - сортировка
- `lang` - `ru`, `en`

### 4. Коммерческая недвижимость

#### 4.1. Поиск коммерческих помещений
```
GET https://commerce.trendagent.ru/search/blocks
```

**Параметры:**
- `auth_token` (required)
- `city` - ID города
- `count`, `offset` - пагинация
- `sort`, `sort_order` - сортировка
- `lang` - `ru`, `en`

#### 4.2. Детальная информация о коммерческом помещении
```
GET https://api.trendagent.ru/v4_29/commerce_premises/{premiseId}/
```

**Параметры:**
- `auth_token` (required)
- `lang` - `ru`, `en`

### 5. Подрядчики и Проекты домов

#### 5.1. Поиск подрядчиков
```
GET https://api.trendagent.ru/v4_29/contractors/search/
```

**Параметры:**
- `auth_token` (required)
- `city` - ID города
- `count`, `offset` - пагинация
- `sort`, `sort_order` - сортировка
- `lang` - `ru`, `en`

#### 5.2. Поиск проектов домов
```
GET https://house-api.trendagent.ru/v1/projects/search
```

**Параметры:**
- `auth_token` (required)
- `city` - ID города
- `count`, `offset` - пагинация
- `sort`, `sort_order` - сортировка
- `lang` - `ru`, `en`

#### 5.3. Детальная информация о проекте дома
```
GET https://house-api.trendagent.ru/v1/projects/{projectId}
```

**Параметры:**
- `auth_token` (required)
- `lang` - `ru`, `en`

### 6. Дополнительные endpoints

#### 6.1. Планировки квартир
```
GET https://api.trendagent.ru/v4_29/apartments/floor_plan/directory/{blockId}
GET https://api.trendagent.ru/v4_29/apartments/floor_plan?building_id=&section_id=&floor_number=
```

#### 6.2. Геоданные комплекса
```
GET https://api.trendagent.ru/v4_29/blocks/{blockId}/geo/buildings/
```

#### 6.3. Медиа комплекса
```
GET https://api.trendagent.ru/v4_29/media/block/{blockId}/plans/
GET https://api.trendagent.ru/v4_29/media/block/{blockId}/progress/years/
GET https://api.trendagent.ru/v4_29/media/block/{blockId}/progress/{year}/
```

#### 6.4. Дополнительная информация
```
GET https://api.trendagent.ru/v4_29/finishings/block/{blockId}/
GET https://api.trendagent.ru/v4_29/blocks/{blockId}/advantages/
GET https://api.trendagent.ru/v4_29/blocks/{blockId}/nearby_places/
GET https://api.trendagent.ru/v4_29/blocks/{blockId}/apartments/min-price/
```

#### 6.5. Внешние сервисы
```
GET https://video.trendagent.ru/videos/block/{blockId}
GET https://files.trendagent.ru/fs/list/block/{blockId}
GET https://rewards-api.trendagent.ru/builder-reward-settings
GET https://discounts.trendagent.ru/blocks/{blockId}/discounts
GET https://mortgage-api.trendagent.ru/blocks/{blockId}/
GET https://tiny-installments-api.trendagent.ru/v1/blocks/{blockId}
GET https://api.trendagent.ru/v4_29/blocks/{blockId}/bank/
GET https://contacts-api.trendagent.ru/contacts/blocks/{blockId}
GET https://3d-tour-api.trendagent.ru/v1/blocks/{blockId}
```

---

## 💻 Примеры использования

### Пример 1: Полная авторизация и запрос

```php
use App\Services\TrendAgent\TrendSsoApiAuth;

// Создание экземпляра
$auth = new TrendSsoApiAuth();

// Авторизация
$authData = $auth->authenticate('+79045393434', 'nwBvh4q');

if ($authData['authenticated']) {
    // Получение токена
    $token = $auth->getAuthToken();
    
    // Выполнение запроса к API
    $blocks = $auth->getBlocksSearch([
        'city' => '58c665588b6aa52311afa01b',  // Санкт-Петербург
        'count' => 20,
        'offset' => 0,
        'sort' => 'price',
        'sort_order' => 'asc'
    ]);
    
    print_r($blocks);
}
```

### Пример 2: Прямой HTTP запрос (cURL)

```bash
# 1. Авторизация
curl -X POST "https://sso-api.trend.tech/v1/login?app_id=66d84f584c0168b8ccd281c3&lang=ru" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Origin: https://sso.trend.tech" \
  -H "Referer: https://sso.trend.tech/login?app_id=66d84f584c0168b8ccd281c3" \
  -d "phone=+79045393434&password=nwBvh4q&client=web" \
  -c cookies.txt

# 2. Извлечение токена из ответа (вручную или через jq)
TOKEN=$(cat response.json | jq -r '.auth_token')

# 3. Запрос к API
curl "https://api.trendagent.ru/v4_29/blocks/search/?auth_token=$TOKEN&city=58c665588b6aa52311afa01b&count=20&offset=0&sort=price&sort_order=asc&lang=ru" \
  -H "Accept: application/json" \
  -H "Origin: https://spb.trendagent.ru" \
  -H "Referer: https://spb.trendagent.ru/" \
  -b cookies.txt
```

### Пример 3: Использование TrendAgentApiClient

```php
use App\Services\TrendAgent\TrendAgentApiClient;

$client = new TrendAgentApiClient();

// Авторизация выполняется автоматически при первом запросе
$complexes = $client->getComplexes([
    'city' => '58c665588b6aa52311afa01b',
    'count' => 20
]);

$apartments = $client->getApartments([
    'city' => '58c665588b6aa52311afa01b',
    'count' => 50,
    'room' => [1, 2, 3],
    'price_from' => 3000000,
    'price_to' => 10000000
]);
```

---

## ⚠️ Обработка ошибок

### Ошибки авторизации

#### 401 Unauthorized
**Причина:** Токен истек или недействителен

**Решение:**
```php
try {
    $result = $auth->getBlocksSearch([...]);
} catch (\Exception $e) {
    if (strpos($e->getMessage(), '401') !== false) {
        // Переавторизация
        $auth->authenticate('+79045393434', 'nwBvh4q');
        // Повтор запроса
        $result = $auth->getBlocksSearch([...]);
    }
}
```

#### 403 Forbidden
**Причина:** Недостаточно прав или защита от ботов

**Решение:**
- Проверить правильность `app_id`
- Убедиться, что используются правильные заголовки
- Проверить cookies

#### 400 Bad Request
**Причина:** Неверные параметры запроса

**Решение:**
- Проверить формат параметров
- Убедиться, что `city` указан правильно
- Проверить типы данных (числа, строки)

### Автоматическая переавторизация

Класс `TrendAgentApiClient` автоматически выполняет переавторизацию при получении 401:

```php
protected function executeWithRetry(callable $callback, int $maxRetries = 1): mixed
{
    $attempts = 0;
    
    while ($attempts <= $maxRetries) {
        try {
            return $callback();
        } catch (\Exception $e) {
            if ($e->getCode() === 401 && $attempts < $maxRetries) {
                // Переавторизация
                $this->authenticated = false;
                $this->ensureAuthenticated();
                $attempts++;
                continue;
            }
            throw $e;
        }
    }
}
```

### Логирование

Все этапы авторизации и запросы логируются:

```php
// Логи находятся в:
storage/logs/laravel.log

// Уровни логирования:
Log::info('Авторизация успешна', [...]);
Log::error('Ошибка авторизации', [...]);
Log::warning('Токен не найден', [...]);
```

---

## 🔒 Безопасность

### Рекомендации

1. **Хранение учетных данных:**
   - Используйте переменные окружения (`.env`)
   - Никогда не коммитьте пароли в Git
   - Используйте секреты в production

2. **Токены:**
   - Токены хранятся в памяти (не в БД)
   - При перезапуске требуется повторная авторизация
   - Не логируйте полные токены

3. **SSL:**
   - В production используйте `'verify' => true`
   - Проверяйте сертификаты серверов

4. **Rate Limiting:**
   - Соблюдайте лимиты API
   - Используйте кэширование для снижения нагрузки

---

## 📊 Регионы и города

### Поддерживаемые регионы

| Ключ | ID города | Название | Base URL |
|------|-----------|----------|----------|
| `msk` | `5a5cb42159042faa9a218d04` | Москва | `https://msk.trendagent.ru` |
| `spb` | `58c665588b6aa52311afa01b` | Санкт-Петербург | `https://spb.trendagent.ru` |
| `krd` | `604b5243f9760700074ac345` | Краснодарский край, Сочи, Республика Адыгея | `https://krasnodar.trendagent.ru` |
| `rnd` | `61926fb5bb267a0008de132b` | Ростов-на-Дону | `https://rostov.trendagent.ru` |
| `crimea` | `682700dd0e7daf77097d0779` | Крым | `https://crimea.trendagent.ru` |
| `kzn` | `642157fca50429d21e3aa14f` | Казань | `https://kzn.trendagent.ru` |
| `ufa` | `674eff862307c824cf56ced3` | Уфа | `https://ufa.trendagent.ru` |
| `ekb` | `650974f78d34c0f790a012a9` | Екатеринбург | `https://ekb.trendagent.ru` |
| `nsk` | `618120c1a56997000866c4d8` | Новосибирск | `https://nsk.trendagent.ru` |
| `dubai` | `63d10e79a8975354f0d41c80` | Абу-Даби, Дубай, Рас-Эль-Хайма, Шарджа, Умм-эль-Кайвайн, Аджман | `https://trendagent.ae` |

### Использование CityService

```php
use App\Services\TrendAgent\CityService;

// Получить ID города
$cityId = CityService::getCityId('spb');  // '58c665588b6aa52311afa01b'

// Получить base URL
$baseUrl = CityService::getCityBaseUrl('spb');  // 'https://spb.trendagent.ru'

// Получить информацию о городе
$city = CityService::getCityByKey('spb');
// [
//     'id' => '58c665588b6aa52311afa01b',
//     'name' => 'Санкт-Петербург',
//     'base_url' => 'https://spb.trendagent.ru',
//     'subdomain' => 'spb'
// ]
```

---

## 📝 Конфигурация

### Переменные окружения (.env)

```env
# TrendAgent API Credentials
TRENDAGENT_PHONE=+79045393434
TRENDAGENT_PASSWORD=nwBvh4q

# Cache Configuration
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Настройка Guzzle Client

```php
$client = new Client([
    'cookies' => $cookieJar,
    'allow_redirects' => [
        'max' => 10,
        'strict' => false,
        'referer' => true,
        'protocols' => ['http', 'https'],
        'track_redirects' => true
    ],
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept' => 'application/json, text/plain, */*',
        'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7'
    ],
    'verify' => false,  // true в production
    'timeout' => 30
]);
```

---

## 🎯 Итоговая схема работы

```
1. Создание TrendSsoApiAuth
   ↓
2. authenticate(phone, password)
   ├─ GET /login (получение app_id)
   ├─ POST /v1/login (авторизация)
   └─ Извлечение auth_token
   ↓
3. Сохранение в authData и CookieJar
   ↓
4. Выполнение запросов к API
   ├─ Добавление auth_token в URL
   ├─ Отправка cookies автоматически
   └─ Использование правильных заголовков
   ↓
5. Обработка ответов
   ├─ 200 OK → успех
   ├─ 401 → переавторизация
   └─ 400/500 → ошибка
```

---

**Дата создания:** 2024-01-15  
**Версия:** 1.0  
**Автор:** AI Assistant
