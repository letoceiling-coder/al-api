# TrendAgent API — Руководство по использованию

**Base URL:** `https://api.siteaccess.ru/api/trendagent/v1`  
**Версия:** v1

---

## Quick Start

```bash
# 1. Health check
curl -sS https://api.siteaccess.ru/api/trendagent/v1/health | jq

# 2. Список городов
curl -sS -X GET https://api.siteaccess.ru/api/trendagent/v1/cities | jq

# 3. Список квартир (Санкт-Петербург)
curl -sS -X POST https://api.siteaccess.ru/api/trendagent/v1/apartments \
  -H "Content-Type: application/json" \
  -d '{"city":"58c665588b6aa52311afa01b","count":5,"page":1}' | jq
```

В режиме `TRENDAGENT_DATA_SOURCE=db` Bearer-токен не требуется.

---

## Полный сценарий (интеграция)

1. **cities** — получить список городов
2. **objects/list** — получить комплексы (blocks) для города
3. **apartments** — список квартир с фильтрами
4. **apartments/{blockId}** — детали комплекса
5. **apartments/{blockId}/flat/{apartmentId}** — детали квартиры

```bash
BASE="https://api.siteaccess.ru/api/trendagent/v1"
CITY="58c665588b6aa52311afa01b"  # СПб

# 1. Cities
curl -sS -X GET "$BASE/cities" | jq

# 2. Комплексы (blocks)
curl -sS -X POST "$BASE/objects/list" \
  -H "Content-Type: application/json" \
  -d "{\"object_type\":\"blocks\",\"city\":\"$CITY\",\"count\":10}" | jq

# 3. Список квартир
curl -sS -X POST "$BASE/apartments" \
  -H "Content-Type: application/json" \
  -d "{\"city\":\"$CITY\",\"count\":20,\"page\":1,\"room\":[1,2],\"price_from\":3000000}" | jq

# 4. Детали комплекса (подставьте block_id из шага 2)
BLOCK_ID="..." 
curl -sS -X POST "$BASE/apartments/$BLOCK_ID" \
  -H "Content-Type: application/json" \
  -d '{}' | jq

# 5. Детали квартиры (block_id и apartment_id из предыдущих ответов)
APARTMENT_ID="..."
curl -sS -X POST "$BASE/apartments/$BLOCK_ID/flat/$APARTMENT_ID" \
  -H "Content-Type: application/json" \
  -d '{}' | jq
```

---

## Переключение между remote и db режимом

| Режим | TRENDAGENT_DATA_SOURCE | Авторизация | Источник данных |
|-------|------------------------|-------------|-----------------|
| **DB** | `db` | Не требуется | Локальная БД (импорт из парсинга) |
| **Remote** | `remote` | Bearer-токен | API trendagent.ru |

### Как переключить

1. Отредактировать `.env`:
   ```
   TRENDAGENT_DATA_SOURCE=db      # или remote
   ```
2. Применить конфиг:
   ```bash
   php artisan config:cache
   ```
3. Перезапустить PHP-FPM (если нужно):
   ```bash
   sudo systemctl reload php8.3-fpm
   ```

### Риски

- **remote:** зависит от доступности trendagent.ru, требует токен.
- **db:** данные только после импорта; при пустой БД API вернёт пустые списки.

---

## 1. Общие сведения

### Формат ответа (remote-style)

Все успешные ответы возвращают JSON:

```json
{
  "success": true,
  "data": { ... },
  "total_count": 100,
  "pagination": {
    "page": 1,
    "count": 20,
    "per_page": 20,
    "offset": 0,
    "current_page": 1,
    "has_more": true
  }
}
```

- **success** — признак успешного запроса
- **data** — данные (objects, unified, apartments и т.д.)
- **total_count** — общее количество записей
- **pagination** — пагинация (page, count/per_page, offset, has_more)

---

## 2. Авторизация

### Режим `TRENDAGENT_DATA_SOURCE=db`

При `TRENDAGENT_DATA_SOURCE=db`:
- **Middleware bypass** — Bearer-токен не требуется
- Все эндпоинты доступны без авторизации
- POST /authenticate возвращает заглушку `{ authenticated: true }`

### Режим `TRENDAGENT_DATA_SOURCE=remote`

При remote-источнике требуется Bearer-токен:

```http
Authorization: Bearer 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF
```

Токен передаётся в заголовке или получается через POST /authenticate.

---

## 3. Список эндпоинтов

| Метод | Путь | Описание |
|-------|------|----------|
| GET | /health | Health check (без auth) |
| GET | /cities | Список городов |
| POST | /authenticate | Авторизация (в db — заглушка) |
| POST | /objects/list | Универсальный список по object_type |
| POST | /apartments | Список квартир |
| POST | /apartments/{id} | Детали комплекса (блока) |
| POST | /apartments/{id}/flat/{apartmentId} | Детали квартиры |
| POST | /apartments/{id}/floor-plan/directory | Справочник поэтажного плана |
| POST | /apartments/{id}/floor-plan | Данные плана этажа |
| POST | /apartments/{id}/checkerboard/buildings | Корпуса для шахматки |
| POST | /apartments/{id}/checkerboard/apartments | Квартиры для шахматки |
| POST | /apartments/{id}/map | Данные блока для карты |
| POST | /apartments/{id}/gallery | Галерея блока |
| POST | /parkings | Список паркингов |
| POST | /parkings/{id} | Детали паркинга |
| POST | /parkings/{id}/places | Места паркинга |
| POST | /houses | Список домов с участками |
| POST | /houses/{id} | Детали дома |
| POST | /houses/{id}/checkerboard/buildings | Корпуса для шахматки |
| POST | /houses/{id}/checkerboard/apartments | Квартиры для шахматки |
| POST | /plots | Список участков |
| POST | /plots/{id} | Детали посёлка |
| POST | /plots/{id}/plot/{plotId} | Детали участка |
| POST | /commercial | Список коммерческой недвижимости |
| POST | /commercial/{id} | Детали коммерческой недвижимости |
| POST | /houseprojects | Список проектов домов (contractors) |
| POST | /houseprojects/{id} | Детали проекта |

---

## 4. Фильтры и параметры

### Общие для списков

| Параметр | Тип | Описание |
|----------|-----|----------|
| city | string | ID города или код (58c665588b6aa52311afa01b, spb, msk) |
| count | int | Кол-во записей на странице (1–100, default: 20) |
| page | int | Номер страницы (default: 1) |
| offset | int | Смещение (если page не задан) |
| sort | string | price \| deadline \| name |
| sort_order | string | asc \| desc |
| text | string | Поиск по номеру/названию |
| include_inactive | int | 1 — включить неактивные записи (is_active=false) |

### Квартиры (apartments)

| Параметр | Тип | Описание |
|----------|-----|----------|
| room | array | Фильтр по количеству комнат [1, 2, 3] |
| price_from | int | Минимальная цена |
| price_to | int | Максимальная цена |
| area_from | float | Минимальная площадь |
| area_to | float | Максимальная площадь |

### Паркинги (parkings)

| Параметр | Тип | Описание |
|----------|-----|----------|
| parking_type | string | Тип паркинга |
| price_from | int | Минимальная цена |
| price_to | int | Максимальная цена |

### Коммерция (commercial)

| Параметр | Тип | Описание |
|----------|-----|----------|
| purpose | string | Назначение |
| price_from | int | Минимальная цена |

### objects/list

| Параметр | Тип | Описание |
|----------|-----|----------|
| object_type | string | blocks \| apartments \| parkings \| houses \| plots \| commercial \| contractors |

---

## 5. Примеры curl

### Health (без auth)

```bash
curl -sS https://api.siteaccess.ru/api/trendagent/v1/health | jq
```

### Cities

```bash
curl -sS -X GET "https://api.siteaccess.ru/api/trendagent/v1/cities" | jq
```

### Authenticate (в db-режиме)

```bash
curl -sS -X POST "https://api.siteaccess.ru/api/trendagent/v1/authenticate" \
  -H "Content-Type: application/json" \
  -d '{}' | jq
```

### Список квартир

```bash
curl -sS -X POST "https://api.siteaccess.ru/api/trendagent/v1/apartments" \
  -H "Content-Type: application/json" \
  -d '{"city":"58c665588b6aa52311afa01b","count":5,"page":1}' | jq
```

### Список квартир с фильтрами

```bash
curl -sS -X POST "https://api.siteaccess.ru/api/trendagent/v1/apartments" \
  -H "Content-Type: application/json" \
  -d '{"city":"spb","count":10,"room":[1,2],"price_from":3000000,"price_to":15000000,"sort":"price","sort_order":"asc"}' | jq
```

### objects/list — комплексы (blocks)

```bash
curl -sS -X POST "https://api.siteaccess.ru/api/trendagent/v1/objects/list" \
  -H "Content-Type: application/json" \
  -d '{"object_type":"blocks","city":"58c665588b6aa52311afa01b","count":5}' | jq
```

### Детали комплекса (apartments/{blockId})

```bash
curl -sS -X POST "https://api.siteaccess.ru/api/trendagent/v1/apartments/BLOCK_EXTERNAL_ID" \
  -H "Content-Type: application/json" \
  -d '{}' | jq
```

### Детали квартиры

```bash
curl -sS -X POST "https://api.siteaccess.ru/api/trendagent/v1/apartments/BLOCK_ID/flat/APARTMENT_EXTERNAL_ID" \
  -H "Content-Type: application/json" \
  -d '{}' | jq
```

### Паркинги

```bash
curl -sS -X POST "https://api.siteaccess.ru/api/trendagent/v1/parkings" \
  -H "Content-Type: application/json" \
  -d '{"city":"spb","count":5}' | jq
```

### Дома (houses)

```bash
curl -sS -X POST "https://api.siteaccess.ru/api/trendagent/v1/houses" \
  -H "Content-Type: application/json" \
  -d '{"city":"spb","count":5}' | jq
```

### Участки (plots)

```bash
curl -sS -X POST "https://api.siteaccess.ru/api/trendagent/v1/plots" \
  -H "Content-Type: application/json" \
  -d '{"city":"spb","count":5}' | jq
```

### Коммерция (commercial)

```bash
curl -sS -X POST "https://api.siteaccess.ru/api/trendagent/v1/commercial" \
  -H "Content-Type: application/json" \
  -d '{"city":"spb","count":5}' | jq
```

### include_inactive=1

```bash
curl -sS -X POST "https://api.siteaccess.ru/api/trendagent/v1/apartments" \
  -H "Content-Type: application/json" \
  -d '{"city":"spb","count":5,"include_inactive":1}' | jq
```

---

## 6. Примеры JSON-ответов

### Health

```json
{
  "ok": true,
  "last_sync_runs": [
    {
      "region": "spb",
      "type": "apartments",
      "started_at": "2026-02-14T10:00:00+00:00",
      "status": "success",
      "created_count": 10,
      "updated_count": 5
    }
  ],
  "counts": {
    "apartments": {"active": 100, "total": 120},
    "complexes": {"active": 15, "total": 18},
    "parkings": {"active": 8, "total": 10}
  },
  "version": "abc1234"
}
```

### Cities

```json
{
  "success": true,
  "data": [
    {"id": "58c665588b6aa52311afa01b", "name": "Санкт-Петербург"},
    {"id": "5a5cb42159042faa9a218d04", "name": "Москва"}
  ]
}
```

### Apartments list (data.objects)

```json
{
  "success": true,
  "total_count": 150,
  "data": {
    "objects": [
      {
        "_id": "5f123...",
        "id": "123",
        "number": "101",
        "rooms": 2,
        "area_total": 45.5,
        "floor": 5,
        "base_price": 5500000,
        "plan_image_url": "https://...",
        "block_id": "5f456...",
        "building_name": "ЖК Пример"
      }
    ],
    "apartments_count": 150,
    "objects_count": 20
  },
  "pagination": {
    "page": 1,
    "count": 20,
    "per_page": 20,
    "has_more": true
  }
}
```

---

## 7. Изображения

### Логика image_url

Для `TrendAgentImage` (morph-связь complex, apartment и т.д.):

- **local_path есть** → `Storage::url(local_path)` — локальный URL
- **local_path нет** → используется оригинальный `url` (внешний CDN)

### Скачивание при импорте

При `php artisan trendagent:import-data --download-images=1` изображения сохраняются локально. После этого API отдаёт `Storage::url(local_path)` вместо внешнего URL.

---

## 8. Ошибки

| Код | Описание |
|-----|----------|
| 400 | Bad Request — неверный формат запроса |
| 401 | Unauthorized — неверный/отсутствующий токен (в remote-режиме) |
| 403 | Forbidden |
| 404 | Not Found — объект не найден |
| 422 | Validation Error — ошибки валидации |
| 429 | Too Many Requests — превышен rate limit (60 req/min на API, health не ограничен) |

### Формат ошибок Laravel (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "city": ["The city field is required."]
  }
}
```

### 401 (remote-режим)

```json
{
  "success": false,
  "message": "Unauthorized. Invalid or missing TrendAgent API token.",
  "error": {
    "type": "UnauthorizedException",
    "code": "TRENDAGENT_INVALID_TOKEN"
  }
}
```

---

## 9. Идентификаторы городов

| Город | ID (city) |
|-------|-----------|
| Санкт-Петербург | 58c665588b6aa52311afa01b или spb |
| Москва | 5a5cb42159042faa9a218d04 или msk |
| Краснодар/Сочи | 604b5243f9760700074ac345 или krd |
| Ростов-на-Дону | 61926fb5bb267a0008de132b или rnd |
| Казань | 642157fca50429d21e3aa14f или kzn |
| Уфа | 64f09bfdb07c24000780ebdd или ufa |
| Крым | 682700dd0e7daf77097d0779 или crimea |
