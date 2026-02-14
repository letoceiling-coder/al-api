# Контракт DB API для TrendAgent

**Версия:** 1.0  
**Дата:** 2026-02-14  
**Цель:** Спецификация эндпоинтов и форматов ответов DB API, совместимых с UI без изменений фронтенда.

---

## Базовый путь

```
/api/trendagent/v1
```

**Важно:** При переключении `TRENDAGENT_DATA_SOURCE=db` эти же эндпоинты должны возвращать данные из БД, сохраняя формат ответа remote API.

---

## Аутентификация

UI отправляет `phone` и `password` в body. При DB-источнике можно:
- возвращать моковый `{ success: true, data: { authenticated: true } }` без реальной проверки;
- или требовать Bearer-токен (если UI уже поддерживает заголовок).

**Заголовки:** `Authorization: Bearer {token}`, `Content-Type: application/json`.

---

## 1. Эндпоинты

### 1.1. GET /cities

**Описание:** Список городов (регионов) для фильтра.

**Параметры:** нет.

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": "58c665588b6aa52311afa01b",
      "name": "Санкт-Петербург"
    }
  ]
}
```

**Критичные поля:**
| Поле | Тип | Обязательное | Описание |
|------|-----|--------------|----------|
| id | string | да | Внешний ID города (TrendAgent) или code региона для маппинга |
| name | string | да | Название города |

---

### 1.2. POST /authenticate

**Описание:** Авторизация. При DB-источнике может быть заглушкой.

**Body:** `{ "phone": string, "password": string }`

**Ответ:**
```json
{
  "success": true,
  "data": {
    "authenticated": true,
    "auth_token": "optional"
  }
}
```

**Критичные поля:** `success`, `data.authenticated`.

---

### 1.3. POST /objects/list

**Описание:** Универсальный список объектов по типу.

**Body:**
| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| object_type | string | да | `blocks`, `apartments`, `parkings`, `houses`, `plots`, `commercial`, `contractors`, `villages` |
| phone | string | да* | *При remote; при DB может игнорироваться |
| password | string | да* | *При remote |
| city | string | да | ID города (из /cities) |
| count | int | нет | 20 (default) |
| offset | int | нет | 0 (default) |
| page | int | нет | 1 (взаимозаменяемо с offset) |
| sort | string | нет | `price`, `deadline`, `name` |
| sort_order | string | нет | `asc`, `desc` |
| room | array[int] | нет | Фильтр комнат (1..6) |
| price_from | int | нет | |
| price_to | int | нет | |
| area_from | float | нет | |
| area_to | float | нет | |
| floor_from | int | нет | |
| floor_to | int | нет | |
| finishing_types | array/string | нет | |
| parking_type | string | нет | |
| purpose | string | нет | Коммерция |
| text | string | нет | Поиск по тексту |
| deadline_key | string | нет | |

**Ответ (blocks/complexes):**
```json
{
  "success": true,
  "data": {
    "objects": [ /* массив блоков/комплексов */ ],
    "blocks_count": 123
  },
  "total_count": 123,
  "pagination": {
    "has_more": true
  }
}
```

**Ответ (остальные типы):**
```json
{
  "success": true,
  "data": {
    "objects": [ /* массив объектов */ ]
  },
  "total_count": 456,
  "pagination": {
    "has_more": true
  }
}
```

**Критичные поля ответа:** `success`, `data.objects` (или `data.data` как fallback), `total_count`.

---

### 1.4. POST /apartments

**Описание:** Список квартир (каталог). Используется на странице «Квартиры» (ObjectsTable).

**Body:** Как в objects/list + `show_type` (для карты: `map`).

**Ответ:** см. objects/list. Элементы — квартиры.

---

### 1.5. POST /apartments/{id}

**Описание:** Детали ЖК/блока (objectType=apartments). Используется ObjectDetail.

**URL:** `id` — 24-символьный hex (_id) или guid/slug.

**Body:** `{ "phone", "password", "options": { "unified": true, "buildings": true, ... } }`

**Ответ:**
```json
{
  "success": true,
  "data": {
    "unified": { "data": { /* unified block data */ } },
    "apartments": { "data": [] | "grouped_data": {} },
    "buildings": { "data": [] },
    "parkings": { "data": [] },
    "commerce": { "data": [] },
    "plans": { "data": [] },
    "progress": {},
    "finishings": {},
    "advantages": { "data": [] },
    "nearby_places": { "data": [] },
    "videos": {},
    "files": {},
    "rewards": {},
    "discounts": {},
    "mortgage": {},
    "installments": {},
    "banks": {},
    "contacts": {},
    "tour_3d": {},
    "block_id": "id",
    "block_guid": "guid"
  }
}
```

**unified.data (ObjectHeader):**
| Поле | Тип | Обязательное |
|------|-----|--------------|
| name | string | да |
| address | string | нет |
| description, about | string | нет |
| min_price | number | нет |
| min_prices | array | нет |
| price_from | number | нет |
| deadline | string/date | нет |
| renderer | array | нет |
| images | array | нет |
| image | object | нет |

---

### 1.6. POST /apartments/{id}/flat/{apartmentId}

**Описание:** Детали квартиры (FlatDetail).

**Ответ:**
```json
{
  "success": true,
  "data": {
    "number", "apartment_number", "floor", "total_floors",
    "section_name", "section", "building_name", "building", "corpus",
    "privArea", "area", "area_total", "calculated_area",
    "kitchenArea", "kitchen_area", "livingArea", "living_area",
    "balcony_type", "balcony", "finishing_name", "finishing",
    "windows", "view_type", "view", "base_price", "price", "full_price",
    "status", "booking_status", "is_booked",
    "plan", "plan_image", "images", "gallery_images",
    "id", "_id"
  }
}
```

---

### 1.7. POST /apartments/{id}/checkerboard/buildings

**Описание:** Корпуса для шахматки (ApartmentsCheckerboard).

**Ответ:**
```json
{
  "success": true,
  "data": {
    "results": [] | "buildings": [] | "data": []
  }
}
```
Элемент: `id`, `_id`, `building_id`, `block_name`, `object_name`.

---

### 1.8. POST /apartments/{id}/checkerboard/apartments

**Описание:** Квартиры для шахматки по корпусу.

**Body:** `{ "building_id": string }`

**Ответ:** массив квартир (см. квартира list item).

---

### 1.9. POST /apartments/{id}/floor-plan/directory

**Описание:** Справочник поэтажного плана (корпуса/секции/этажи).

**Ответ:**
```json
{
  "data": {
    "buildings": [],
    "sections": [],
    "floors": []
  }
}
```

---

### 1.10. POST /apartments/{id}/floor-plan

**Описание:** Изображение поэтажного плана этажа.

**Body:** `{ "building_id", "section_id", "floor" }`

**Ответ:** URL изображения плана.

---

### 1.11. POST /apartments/{id}/map

**Описание:** Данные блока для карты (координаты).

**Ответ:** массив объектов с `lat`, `lon`, `id`, `name`, `apart_count`, `min_price`, `guid`.

---

### 1.12. POST /apartments/{id}/gallery

**Описание:** Галерея изображений блока.

---

### 1.13. POST /parkings

**Описание:** Список паркингов.

**Body:** city, count, offset, parking_type, price_from, price_to, text, sort, sort_order.

---

### 1.14. POST /parkings/{id}

**Описание:** Детали паркинга.

---

### 1.15. POST /parkings/{id}/places

**Описание:** Места парковки.

---

### 1.16. POST /houses

**Описание:** Список домов.

---

### 1.17. POST /houses/{id}

**Описание:** Детали дома. Структура как apartments/{id}.

---

### 1.18. POST /houses/{id}/checkerboard/buildings

**Описание:** Корпуса для шахматки домов.

---

### 1.19. POST /houses/{id}/checkerboard/apartments

**Описание:** Квартиры для шахматки домов.

---

### 1.20. POST /plots

**Описание:** Список поселков/участков (objects/list object_type=plots или villages).

---

### 1.21. POST /plots/{id}

**Описание:** Детали поселка (VillageDetail). Структура как apartments.

---

### 1.22. POST /plots/{id}/plot/{plotId}

**Описание:** Детали участка.

---

### 1.23. POST /commercial

**Описание:** Список коммерческой недвижимости.

---

### 1.24. POST /commercial/{id}

**Описание:** Детали коммерции.

---

### 1.25. POST /houseprojects

**Описание:** Список проектов домов (подрядчики). Body: `object_type: 'contractors'`.

---

### 1.26. POST /houseprojects/{id}

**Описание:** Детали проекта дома.

---

### 1.27. POST /block/details

**Описание:** Универсальные детали блока по id/guid.

**Body:** `{ "id": string | guid }`

---

### 1.28. POST /block/{dataType}

**Описание:** Данные блока по типу (buildings, apartments, plans, progress, …).

---

## 2. Формат элемента списка (ObjectCard)

**Общие поля:**
| Поле | Тип | Обязательное | Использование |
|------|-----|--------------|---------------|
| _id | string | да* | Идентификатор (приоритет 1) |
| id | string | да* | Идентификатор (приоритет 2) |
| guid | string | нет | Slug для URL |
| name | string | да | Заголовок карточки |
| title, block_name, village_name | string | нет | Fallback названия |
| address | string | нет | Адрес |
| image | object | нет | { url, thumbnail, full } |
| images | array | нет | [{ path, file_name }, { url }, { thumbnail, full }] |
| renderer | array | нет | Галерея |
| min_price | number | нет | Цена |
| min_prices | array | нет | [{ price, value, formatted_value, label, unit }] |
| price, price_from | number | нет | |
| deadline | string/array | нет | Срок сдачи |
| apart_count | int | нет | Квартир (для блоков) |
| places_count | int | нет | Мест (для паркингов) |

**image_url / изображения:**
- Строка: полный URL (http/https).
- Объект: `url`, `thumbnail`, `full`, или `path` + `file_name` → `https://selcdn.trendagent.ru/images/{path}/m_{file_name}`.
- **При local_path:** API отдаёт `Storage::url(local_path)` вместо оригинального URL.

---

## 3. Пагинация и сортировка

**Запрос:**
- `count` (default 20)
- `offset` (default 0)
- `page` — при page>1: offset = (page-1)*count
- `sort`: `price`, `deadline`, `name`
- `sort_order`: `asc`, `desc`

**Ответ:**
- `total_count`: int
- `pagination.has_more`: boolean
- `data.blocks_count` (только для blocks)

---

## 4. Фильтры (строго по UI)

| Контекст | Поле | Тип | Описание |
|----------|------|-----|----------|
| Общий | city | string | ID города |
| Общий | text | string | Поиск |
| Квартиры | room | array[int] | 1..6 (студия=1) |
| Квартиры | price_from | int | |
| Квартиры | price_to | int | |
| Квартиры | area_from | float | |
| Квартиры | area_from | float | |
| Паркинги | parking_type | string | |
| Коммерция | purpose | string | |
| Сортировка | sort | string | price, deadline, name |
| Сортировка | sort_order | string | asc, desc |

---

## 5. Обработка image_url

**Правило:** Если изображение сохранено локально (`local_path` заполнен), API возвращает `Storage::url(local_path)`. Иначе — оригинальный URL.

**Форматы в ответе:**
- Строка: `"https://..."`
- Объект: `{ "url": "https://...", "thumbnail": "https://..." }`
- Объект TrendAgent: `{ "path": "...", "file_name": "..." }` → URL формируется на бэкенде.

---

## 6. Кодировка и nullability

- Все строки: UTF-8.
- Числа: int/float, не строки.
- Отсутствующие опциональные поля: `null` или не включать.
- Обязательные поля: не null (кроме оговорённых).

---

## 7. Минимальный набор для первого запуска

Для MVP DB API достаточно реализовать:

1. **GET /cities** — список регионов из БД
2. **POST /authenticate** — заглушка
3. **POST /objects/list** (object_type=blocks) — комплексы (ObjectsList)
4. **POST /objects/list** (apartments, parkings, houses, plots, commercial, contractors, villages)
5. **POST /apartments/{id}** — детали комплекса (ObjectDetail)
6. **POST /apartments** — список квартир (ObjectsTable)
7. **GET /api/trendagent/db/apartment/{id}** — уже есть (Blade db view)

Остальные эндпоинты (checkerboard, floor-plan, map, gallery, parkings/houses/plots/commercial/houseprojects detail) можно добавлять поэтапно.
