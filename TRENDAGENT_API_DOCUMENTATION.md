# Полная документация API TrendAgent

**Базовый URL:** `https://api.siteaccess.ru/trendagent/`  
**Версия:** 1.0  
**Дата:** 2026-02-07

---

## Содержание

1. [Аутентификация](#аутентификация)
2. [Типы объектов](#типы-объектов)
3. [Роуты по типам объектов](#роуты-по-типам-объектов)
4. [Универсальные роуты](#универсальные-роуты)
5. [Формат запросов и ответов](#формат-запросов-и-ответов)
6. [Кэширование](#кэширование)

---

## Аутентификация

### POST /trendagent/authenticate

Авторизация через SSO API TrendAgent.

**Запрос:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q"
}
```

**Ответ:**
```json
{
  "success": true,
  "authenticated": true,
  "auth_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": "...",
    "name": "..."
  }
}
```

**Ошибки:**
- `422` - Ошибка валидации
- `401` - Неверные учетные данные
- `500` - Ошибка сервера

---

### GET /trendagent/cities

Получение списка доступных городов.

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": "58c665588b6aa52311afa01b",
      "name": "Санкт-Петербург",
      "code": "spb"
    }
  ]
}
```

---

## Типы объектов

API поддерживает следующие типы объектов:

1. **apartments** - Квартиры
2. **houses** - Дома (коттеджи, таунхаусы)
3. **plots** - Участки (поселки)
4. **parkings** - Паркинги
5. **commercial** - Коммерческая недвижимость
6. **contractors** / **houseprojects** - Проекты домов / Подрядчики

---

## Роуты по типам объектов

### Квартиры (Apartments)

#### POST /trendagent/apartments

Получение списка квартир с фильтрами.

**Параметры запроса:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q",
  "city": "58c665588b6aa52311afa01b",
  "count": 20,
  "offset": 0,
  "page": 1,
  "sort": "price",
  "sort_order": "asc",
  "room": [30, 40],
  "price_from": 5000000,
  "price_to": 15000000,
  "area_from": 30,
  "area_to": 100,
  "floor_from": 1,
  "floor_to": 25,
  "finishing_types": ["white", "premium"],
  "deadline_key": "2024",
  "text": "поисковый запрос",
  "subway": "subway_id",
  "region": "region_id",
  "district": "district_id"
}
```

**Ответ:**
```json
{
  "success": true,
  "total_count": 150,
  "data": {
    "objects": [
      {
        "id": "...",
        "_id": "...",
        "name": "Название ЖК",
        "address": "Адрес",
        "image": "https://selcdn.trendagent.ru/...",
        "images": [
          {
            "thumbnail": "https://selcdn.trendagent.ru/.../m_image.jpg",
            "full": "https://selcdn.trendagent.ru/.../image.jpg"
          }
        ],
        "price": 8500000,
        "min_price": 7500000,
        "deadline": "2024-12-31",
        "apart_count": 150
      }
    ]
  },
  "pagination": {
    "count": 20,
    "offset": 0,
    "page": 1,
    "has_more": true,
    "returned_count": 20
  }
}
```

---

#### POST /trendagent/apartments/{id}

Получение детальной информации об объекте (квартиры).

**Параметры запроса:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q",
  "options": {
    "unified": true,
    "buildings": true,
    "apartments": true,
    "plans": true,
    "progress": true,
    "finishings": true,
    "advantages": true,
    "nearby_places": true,
    "min_price": true,
    "videos": true,
    "files": true,
    "rewards": true,
    "discounts": true,
    "mortgage": true,
    "installments": true,
    "banks": true,
    "contacts": true,
    "3d_tour": true
  }
}
```

**Ответ:**
```json
{
  "success": true,
  "block_id": "64db7ab977be523b31f3f533",
  "block_guid": "belaya-dacha",
  "data": {
    "unified": {
      "data": {
        "name": "Название ЖК",
        "address": "Адрес",
        "description": "Описание",
        "images": [...],
        "min_prices": [...]
      }
    },
    "apartments": {...},
    "plans": {...},
    "rewards": {...},
    "discounts": {...},
    "mortgage": {...},
    "installments": {...},
    "banks": {...},
    "contacts": {...},
    "tour_3d": {...}
  }
}
```

---

#### POST /trendagent/apartments/{id}/checkerboard/buildings

Получение списка корпусов для шахматки.

**Параметры запроса:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q",
  "room": [30, 40]
}
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": "...",
      "_id": "...",
      "name": "Корпус 1",
      "building_name": "Корпус 1",
      "apartments_count": 50
    }
  ]
}
```

---

#### POST /trendagent/apartments/{id}/checkerboard/apartments

Получение квартир для выбранного корпуса (шахматка).

**Параметры запроса:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q",
  "building_id": "building_id_here"
}
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "floors": [
      {
        "floor": 1,
        "apartments": [
          {
            "id": "...",
            "number": "1",
            "floor": 1,
            "area": 45.5,
            "price": 8500000,
            "status": "Свободна",
            "image": "..."
          }
        ]
      }
    ]
  }
}
```

---

#### POST /trendagent/apartments/{id}/flat/{apartmentId}

Получение детальной информации о квартире.

**Параметры запроса:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q"
}
```

**Ответ:**
```json
{
  "success": true,
  "block_id": "...",
  "block_guid": "...",
  "data": {
    "apartment": {
      "id": "...",
      "number": "1",
      "floor": 5,
      "area": 45.5,
      "kitchen_area": 12.5,
      "rooms": 2,
      "price": 8500000,
      "plan": "https://selcdn.trendagent.ru/...",
      "status": "Свободна"
    },
    "block": {
      "name": "Название ЖК",
      "address": "Адрес"
    }
  }
}
```

---

### Дома (Houses)

#### POST /trendagent/houses

Получение списка домов.

**Параметры запроса:** (аналогично apartments)
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q",
  "city": "58c665588b6aa52311afa01b",
  "count": 20,
  "offset": 0,
  "sort": "price",
  "sort_order": "asc",
  "room": [30, 40]
}
```

**Ответ:**
```json
{
  "success": true,
  "total_count": 50,
  "data": {
    "objects": [
      {
        "id": "...",
        "name": "Название поселка",
        "min_prices": [
          {
            "label": "от",
            "value": 5000000,
            "formatted_value": "5 000 000",
            "unit": "₽"
          }
        ],
        "images": [
          {
            "thumbnail": "https://selcdn.trendagent.ru/.../m_image.jpg",
            "full": "https://selcdn.trendagent.ru/.../image.jpg"
          }
        ]
      }
    ]
  }
}
```

---

#### POST /trendagent/houses/{id}

Получение детальной информации о доме.

**Параметры запроса:** (аналогично apartments/{id})

**Ответ:** (аналогично apartments/{id})

---

#### POST /trendagent/houses/{id}/checkerboard/buildings

Получение корпусов для шахматки домов.

**Параметры запроса:** (аналогично apartments/{id}/checkerboard/buildings)

---

#### POST /trendagent/houses/{id}/checkerboard/apartments

Получение квартир для шахматки домов.

**Параметры запроса:** (аналогично apartments/{id}/checkerboard/apartments)

---

### Участки (Plots)

#### POST /trendagent/plots

Получение списка участков (поселков).

**Параметры запроса:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q",
  "city": "58c665588b6aa52311afa01b",
  "count": 20,
  "offset": 0,
  "sort": "price",
  "sort_order": "asc",
  "price_from": 1000000,
  "price_to": 5000000,
  "area_from": 500,
  "area_to": 2000
}
```

**Ответ:**
```json
{
  "success": true,
  "total_count": 30,
  "data": {
    "objects": [
      {
        "id": "...",
        "name": "Название поселка",
        "address": "Адрес",
        "min_prices": [
          {
            "label": "от",
            "value": 2000000,
            "unit": "₽"
          }
        ],
        "images": [
          {
            "thumbnail": "https://selcdn.trendagent.ru/.../m_image.jpg",
            "full": "https://selcdn.trendagent.ru/.../image.jpg"
          }
        ],
        "plots_count": 150,
        "deadline": "2024-12-31"
      }
    ]
  }
}
```

---

#### POST /trendagent/plots/{id}

Получение детальной информации о поселке.

**Параметры запроса:** (аналогично apartments/{id})

**Ответ:** (аналогично apartments/{id}, но с данными поселка)

---

#### POST /trendagent/plots/{id}/plot/{plotId}

Получение детальной информации об участке.

**Параметры запроса:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q",
  "city": "58c665588b6aa52311afa01b"
}
```

**Ответ:**
```json
{
  "success": true,
  "block_id": "plot_id",
  "block_guid": "plot_guid",
  "data": {
    "unified": {
      "data": {
        "id": "...",
        "name": "Участок №1",
        "area": 800,
        "price": 3000000,
        "status": "Свободен",
        "plan": "https://selcdn.trendagent.ru/...",
        "village_id": "..."
      }
    },
    "village": {
      "name": "Название поселка",
      "address": "Адрес"
    }
  }
}
```

---

### Паркинги (Parkings)

#### POST /trendagent/parkings

Получение списка паркингов.

**Параметры запроса:** (аналогично apartments)

**Ответ:**
```json
{
  "success": true,
  "data": {
    "objects": [
      {
        "id": "...",
        "name": "Название паркинга",
        "places_count": 100,
        "price": 1500000
      }
    ]
  }
}
```

---

#### POST /trendagent/parkings/{id}

Получение детальной информации о паркинге.

---

#### POST /trendagent/parkings/{id}/places

Получение мест парковки.

---

### Коммерческая недвижимость (Commercial)

#### POST /trendagent/commercial

Получение списка коммерческой недвижимости.

**Параметры запроса:** (аналогично apartments)

---

#### POST /trendagent/commercial/{id}

Получение детальной информации о коммерческой недвижимости.

---

### Проекты домов (House Projects)

#### POST /trendagent/houseprojects

Получение списка проектов домов.

**Параметры запроса:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q",
  "object_type": "contractors",
  "count": 20,
  "offset": 0,
  "sort": "price",
  "sort_order": "asc"
}
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "objects": [
      {
        "id": "...",
        "name": "Название проекта",
        "images": [...],
        "price": 5000000,
        "area_total": 150
      }
    ]
  }
}
```

---

#### POST /trendagent/houseprojects/{id}

Получение детальной информации о проекте дома.

**Параметры запроса:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q",
  "object_type": "contractors"
}
```

---

## Универсальные роуты

### POST /trendagent/objects/list

Универсальный роут для получения списка объектов любого типа.

**Параметры запроса:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q",
  "object_type": "apartments|houses|plots|parkings|commercial|contractors",
  "count": 20,
  "offset": 0,
  "sort": "price",
  "sort_order": "asc"
}
```

---

### POST /trendagent/block/details

Получение детальной информации о блоке (объекте).

**Параметры запроса:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q",
  "block_id": "64db7ab977be523b31f3f533",
  "block_guid": "belaya-dacha",
  "object_type": "apartments|houses|plots|...",
  "options": {
    "unified": true,
    "buildings": true,
    "apartments": true,
    "plans": true,
    "progress": true,
    "finishings": true,
    "advantages": true,
    "nearby_places": true,
    "min_price": true,
    "videos": true,
    "files": true,
    "rewards": true,
    "discounts": true,
    "mortgage": true,
    "installments": true,
    "banks": true,
    "contacts": true,
    "3d_tour": true
  }
}
```

---

### POST /trendagent/block/{dataType}

Получение данных блока по типу.

**dataType может быть:**
- `unified` - Основные данные
- `buildings` - Корпуса
- `apartments` - Квартиры
- `plans` - Планировки
- `progress` - Ход строительства
- `finishings` - Отделка
- `advantages` - Преимущества
- `nearby_places` - Ближайшие места
- `min_price` - Минимальная цена
- `videos` - Видео
- `files` - Файлы
- `rewards` - Вознаграждения
- `discounts` - Акции и скидки
- `mortgage` - Ипотека
- `installments` - Рассрочка
- `banks` - Банки эскроу
- `contacts` - Контакты
- `3d_tour` - 3D-тур

---

## Формат запросов и ответов

### Общие параметры

Все запросы требуют:
- `phone` (string, required) - Телефон для авторизации
- `password` (string, required) - Пароль для авторизации

### Пагинация

- `count` (integer, optional) - Количество элементов на странице (1-100, по умолчанию 20)
- `offset` (integer, optional) - Смещение (по умолчанию 0)
- `page` (integer, optional) - Номер страницы (по умолчанию 1)

### Сортировка

- `sort` (string, optional) - Поле для сортировки (`price`, `deadline`, `name`)
- `sort_order` (string, optional) - Порядок сортировки (`asc`, `desc`)

### Фильтры

- `room` (array, optional) - Фильтр по количеству комнат (30 = 3к, 40 = 4к)
- `price_from` (integer, optional) - Минимальная цена
- `price_to` (integer, optional) - Максимальная цена
- `area_from` (numeric, optional) - Минимальная площадь
- `area_to` (numeric, optional) - Максимальная площадь
- `floor_from` (integer, optional) - Минимальный этаж
- `floor_to` (integer, optional) - Максимальный этаж
- `text` (string, optional) - Поисковый запрос
- `subway` (string, optional) - ID метро
- `region` (string, optional) - ID региона
- `district` (string, optional) - ID района

### Формат ответа

**Успешный ответ:**
```json
{
  "success": true,
  "data": {...},
  "total_count": 100,
  "pagination": {...}
}
```

**Ошибка:**
```json
{
  "success": false,
  "message": "Описание ошибки",
  "errors": {...}
}
```

### Коды ответов

- `200` - Успешный запрос
- `422` - Ошибка валидации
- `401` - Ошибка авторизации
- `404` - Ресурс не найден
- `500` - Ошибка сервера

---

## Кэширование

Все запросы к TrendAgent API кэшируются на **60 минут**.

**Кэшируются:**
- Списки объектов
- Детальная информация об объектах
- Данные шахматок
- Все дополнительные данные (планы, видео, файлы и т.д.)

**Не кэшируются:**
- Аутентификация
- Список городов

**Ключ кэша формируется на основе:**
- URL запроса
- Параметров запроса
- Типа объекта
- ID объекта

**Очистка кэша:**
Кэш автоматически очищается через 60 минут или может быть очищен вручную через:
```php
Cache::forget('trendagent:cache_key');
```

---

## Примеры использования

### Получение списка квартир

```bash
curl -X POST https://api.siteaccess.ru/trendagent/apartments \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF" \
  -d '{
    "phone": "+79045393434",
    "password": "nwBvh4q",
    "count": 20,
    "room": [30, 40],
    "sort": "price",
    "sort_order": "asc"
  }'
```

### Получение детальной информации об объекте

```bash
curl -X POST https://api.siteaccess.ru/trendagent/apartments/64db7ab977be523b31f3f533 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF" \
  -d '{
    "phone": "+79045393434",
    "password": "nwBvh4q",
    "options": {
      "unified": true,
      "apartments": true,
      "plans": true
    }
  }'
```

### Получение корпусов для шахматки

```bash
curl -X POST https://api.siteaccess.ru/trendagent/apartments/64db7ab977be523b31f3f533/checkerboard/buildings \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF" \
  -d '{
    "phone": "+79045393434",
    "password": "nwBvh4q",
    "room": [30, 40]
  }'
```

---

## Обработка изображений

Все изображения загружаются с CDN: `https://selcdn.trendagent.ru`

**Формат URL:**
- Миниатюра: `https://selcdn.trendagent.ru/images/{path}/m_{file_name}`
- Полное: `https://selcdn.trendagent.ru/images/{path}/{file_name}`

**В ответах API изображения могут быть:**
- Строка (URL)
- Объект с полями `thumbnail` и `full`
- Объект с полями `path` и `file_name`
- Массив изображений

---

## Обработка цен

**Формат цен:**
- Для домов: массив `min_prices` с объектами `{label, value, formatted_value, unit}`
- Для участков: аналогично домам
- Для квартир: поля `price`, `min_price`, `price_from`

**Отображение:**
- Дома: "от X ₽" (из `min_prices[0]`)
- Участки: "от X ₽" или с label (из `min_prices[0]`)
- Квартиры: "X ₽" или "от X ₽"

---

## Обработка ошибок

Все ошибки возвращаются в формате:
```json
{
  "success": false,
  "message": "Описание ошибки",
  "errors": {
    "field": ["Сообщение об ошибке"]
  }
}
```

**Типичные ошибки:**
- `Ошибка валидации` (422) - Неверные параметры запроса
- `Авторизация не удалась` (401) - Неверные учетные данные
- `Ресурс не найден` (404) - Объект с указанным ID не найден
- `Ошибка при запросе к API` (500) - Ошибка на стороне TrendAgent API

---

**Документация обновлена:** 2026-02-07
