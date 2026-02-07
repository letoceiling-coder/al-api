# План парсинга и проектирования БД для TrendAgent

## Цель
Создать систему парсинга всех типов объектов TrendAgent (Квартиры, Паркинги, Дома, Участки, Коммерция) для всех регионов с последующим анализом данных и проектированием гибкой структуры БД с правильными связями.

---

## ФАЗА 1: ПАРСИНГ ДАННЫХ (Санкт-Петербург)

### 1.1 Подготовка инфраструктуры парсинга

#### 1.1.1 Создание структуры директорий
```
storage/trendagent/parsing/
├── spb/                          # Регион Санкт-Петербург
│   ├── raw/                      # Сырые данные от API
│   │   ├── complexes/            # Комплексы (блоки)
│   │   ├── apartments/           # Квартиры
│   │   ├── parkings/            # Паркинги
│   │   ├── houses/              # Дома
│   │   ├── plots/               # Участки
│   │   └── commercial/          # Коммерция
│   ├── details/                  # Детальные данные
│   │   ├── complexes/           # Детали комплексов
│   │   ├── apartments/         # Детали квартир
│   │   ├── parkings/           # Детали паркингов
│   │   ├── houses/             # Детали домов
│   │   ├── plots/              # Детали участков
│   │   └── commercial/         # Детали коммерции
│   ├── analysis/                # Результаты анализа
│   │   ├── field_mapping.json  # Маппинг полей по типам
│   │   ├── common_fields.json  # Общие поля
│   │   ├── unique_fields.json  # Уникальные поля по типам
│   │   └── relationships.json  # Выявленные связи
│   └── metadata/                # Метаданные парсинга
│       ├── parsing_log.json    # Лог парсинга
│       ├── statistics.json     # Статистика
│       └── errors.json         # Ошибки
```

#### 1.1.2 Создание Artisan команды для парсинга
**Команда:** `php artisan trendagent:parse --region=spb --type=all`

**Параметры:**
- `--region` - регион (spb, msk, и т.д.)
- `--type` - тип объекта (all, apartments, parkings, houses, plots, commercial, complexes)
- `--limit` - лимит объектов (для тестирования)
- `--offset` - смещение (для продолжения парсинга)
- `--details` - парсить детальные страницы (true/false)
- `--save-raw` - сохранять сырые данные (true/false)

### 1.2 Парсинг списков объектов

#### 1.2.1 Парсинг комплексов (блоков)
**API Endpoint:** `POST /trendagent/objects/list`
**Параметры:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q",
  "city": "spb",
  "object_type": null,  // null = все типы
  "count": 100,
  "offset": 0
}
```

**Что сохранять:**
- Полный JSON ответ от API
- Метаданные запроса (timestamp, параметры, статус)
- Файл: `storage/trendagent/parsing/spb/raw/complexes/list_offset_{offset}.json`

**Структура файла:**
```json
{
  "metadata": {
    "region": "spb",
    "type": "complexes",
    "timestamp": "2026-02-07T10:00:00Z",
    "request_params": {...},
    "total_count": 150,
    "current_offset": 0,
    "items_count": 100
  },
  "data": [...]
}
```

#### 1.2.2 Парсинг квартир
**API Endpoint:** `POST /trendagent/apartments`
**Параметры:**
```json
{
  "phone": "+79045393434",
  "password": "nwBvh4q",
  "city": "spb",
  "count": 100,
  "offset": 0,
  "sort": "price",
  "sort_order": "asc"
}
```

**Что сохранять:**
- Полный JSON ответ
- Файл: `storage/trendagent/parsing/spb/raw/apartments/list_offset_{offset}.json`

#### 1.2.3 Парсинг паркингов
**API Endpoint:** `POST /trendagent/parkings`
**Файл:** `storage/trendagent/parsing/spb/raw/parkings/list_offset_{offset}.json`

#### 1.2.4 Парсинг домов
**API Endpoint:** `POST /trendagent/houses`
**Файл:** `storage/trendagent/parsing/spb/raw/houses/list_offset_{offset}.json`

#### 1.2.5 Парсинг участков
**API Endpoint:** `POST /trendagent/plots`
**Файл:** `storage/trendagent/parsing/spb/raw/plots/list_offset_{offset}.json`

#### 1.2.6 Парсинг коммерции
**API Endpoint:** `POST /trendagent/commercial`
**Файл:** `storage/trendagent/parsing/spb/raw/commercial/list_offset_{offset}.json`

### 1.3 Парсинг детальных страниц

#### 1.3.1 Детали комплекса
**API Endpoint:** `POST /trendagent/apartments/{id}` или `POST /trendagent/block/details`
**Параметры:**
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
    "files": true
  }
}
```

**Что сохранять:**
- Полный JSON ответ
- Файл: `storage/trendagent/parsing/spb/details/complexes/{complex_id}.json`

**Дополнительно парсить:**
- Шахматка корпусов: `POST /trendagent/apartments/{id}/checkerboard/buildings`
- Шахматка квартир: `POST /trendagent/apartments/{id}/checkerboard/apartments`
- Поэтажный план: `POST /trendagent/apartments/{id}/floor-plan/directory`
- Поэтажный план данные: `POST /trendagent/apartments/{id}/floor-plan`

#### 1.3.2 Детали квартиры
**API Endpoint:** `POST /trendagent/apartments/{id}/flat/{apartmentId}`
**Файл:** `storage/trendagent/parsing/spb/details/apartments/{apartment_id}.json`

#### 1.3.3 Детали паркинга
**API Endpoint:** `POST /trendagent/parkings/{id}`
**Дополнительно:** `POST /trendagent/parkings/{id}/places` (места парковки)
**Файл:** `storage/trendagent/parsing/spb/details/parkings/{parking_id}.json`

#### 1.3.4 Детали дома
**API Endpoint:** `POST /trendagent/houses/{id}`
**Дополнительно:** Шахматка (если есть)
**Файл:** `storage/trendagent/parsing/spb/details/houses/{house_id}.json`

#### 1.3.5 Детали участка
**API Endpoint:** `POST /trendagent/plots/{id}`
**Дополнительно:** `POST /trendagent/plots/{id}/plot/{plotId}` (детали конкретного участка)
**Файл:** `storage/trendagent/parsing/spb/details/plots/{plot_id}.json`

#### 1.3.6 Детали коммерции
**API Endpoint:** `POST /trendagent/commercial/{id}`
**Файл:** `storage/trendagent/parsing/spb/details/commercial/{commercial_id}.json`

### 1.4 Скачивание и хранение изображений

#### 1.4.1 Структура хранения изображений
```
storage/app/public/trendagent/
├── images/
│   ├── complexes/
│   │   └── {complex_id}/
│   │       ├── gallery/
│   │       │   ├── {image_hash}.jpg
│   │       │   └── ...
│   │       └── plans/
│   ├── apartments/
│   │   └── {apartment_id}/
│   │       ├── gallery/
│   │       ├── plans/
│   │       └── views/
│   ├── parkings/
│   ├── houses/
│   ├── plots/
│   └── commercial/
└── thumbnails/  # Миниатюры для быстрой загрузки
    └── {same_structure}
```

#### 1.4.2 Логика скачивания изображений
**Принципы:**
1. Скачивать все изображения с донора (selcdn.trendagent.ru, api.trendagent.ru)
2. Сохранять с оригинальным именем или хешем URL
3. Создавать миниатюры для галерей
4. Обновлять URL в JSON данных на локальные пути
5. Проверять существование перед скачиванием (избегать дубликатов)

**Формат сохранения:**
- Оригиналы: `storage/app/public/trendagent/images/{type}/{object_id}/gallery/{hash}.{ext}`
- Миниатюры: `storage/app/public/trendagent/thumbnails/{type}/{object_id}/gallery/{hash}_thumb.{ext}`
- Публичный URL: `/storage/trendagent/images/{type}/{object_id}/gallery/{hash}.{ext}`

**Метаданные изображений:**
- Сохранять в БД таблицу `trendagent_images`:
  - original_url (URL с донора)
  - local_path (локальный путь)
  - public_url (публичный URL)
  - width, height
  - file_size
  - mime_type
  - downloaded_at

#### 1.4.3 Обработка изображений
**Типы изображений:**
- **Галерея** - основные фото объектов
- **Планы** - планировки квартир, поэтажные планы
- **Виды** - виды из окон
- **Иконки** - маленькие превью

**Оптимизация:**
- Создавать миниатюры (300x300, 800x800)
- Сжимать JPEG (quality 85)
- Конвертировать в WebP для современных браузеров
- Ленивая загрузка больших изображений

### 1.5 Логика парсинга

#### 1.5.1 Последовательность парсинга
1. **Шаг 1:** Получить список всех комплексов (блоков)
2. **Шаг 2:** Для каждого комплекса получить детальную информацию
3. **Шаг 3:** Скачать все изображения комплекса
4. **Шаг 4:** Из детальной информации комплекса извлечь связанные объекты:
   - Квартиры (если есть)
   - Паркинги (если есть)
   - Коммерция (если есть)
5. **Шаг 5:** Для каждого связанного объекта скачать изображения
6. **Шаг 6:** Парсить списки объектов по типам (для объектов без комплекса)
7. **Шаг 7:** Для каждого объекта получить детальную информацию и скачать изображения

#### 1.4.2 Обработка ошибок
- Логировать все ошибки в `metadata/errors.json`
- Продолжать парсинг при ошибках отдельных объектов
- Сохранять статус парсинга для возможности продолжения
- Retry логика для временных ошибок (3 попытки с задержкой)

#### 1.4.3 Прогресс и статистика
- Сохранять прогресс в `metadata/parsing_log.json`
- Обновлять статистику в `metadata/statistics.json`:
  - Количество обработанных объектов по типам
  - Количество ошибок
  - Время парсинга
  - Размер данных

---

## ФАЗА 2: АНАЛИЗ ДАННЫХ

### 2.1 Анализ структуры данных

#### 2.1.1 Извлечение всех полей
**Команда:** `php artisan trendagent:analyze --region=spb`

**Что делать:**
1. Прочитать все JSON файлы из `raw/` и `details/`
2. Рекурсивно извлечь все ключи из JSON объектов
3. Сохранить в `analysis/field_mapping.json`:
```json
{
  "complexes": {
    "list": ["id", "name", "address", "city", ...],
    "details": ["id", "name", "address", "buildings", "apartments", ...]
  },
  "apartments": {
    "list": ["id", "name", "price", "area", "rooms", ...],
    "details": ["id", "name", "price", "area", "rooms", "building", "section", ...]
  },
  ...
}
```

#### 2.1.2 Выявление общих полей
**Цель:** Найти поля, которые есть у всех или большинства типов объектов

**Алгоритм:**
1. Собрать все уникальные поля по типам
2. Найти пересечения (поля, которые есть в нескольких типах)
3. Сохранить в `analysis/common_fields.json`:
```json
{
  "all_types": ["id", "name", "city", "region", "created_at", "updated_at"],
  "most_types": ["address", "price", "status", "images"],
  "some_types": ["area", "floor", "rooms"]
}
```

#### 2.1.3 Выявление уникальных полей
**Цель:** Найти поля, специфичные для каждого типа

**Сохранить в `analysis/unique_fields.json`:**
```json
{
  "apartments": ["rooms", "kitchen_area", "living_area", "balcony", "finishing"],
  "parkings": ["parking_type", "places", "place_number"],
  "houses": ["land_area", "house_area", "floors_count"],
  "plots": ["plot_area", "cadastral_number", "utilities"],
  "commercial": ["commercial_type", "business_type", "rent_price"]
}
```

#### 2.1.4 Анализ связей
**Цель:** Выявить связи между объектами

**Что искать:**
1. **Комплекс → Объекты:**
   - В деталях комплекса искать массивы `apartments`, `parkings`, `commercial`
   - Извлекать ID связанных объектов
   - Сохранить в `analysis/relationships.json`:
```json
{
  "complex_to_apartments": {
    "complex_id_field": "id",
    "apartments_field": "apartments",
    "apartment_id_field": "id"
  },
  "complex_to_parkings": {...},
  "complex_to_commercial": {...},
  "apartment_to_building": {
    "apartment_id_field": "id",
    "building_field": "building",
    "building_id_field": "id"
  },
  "apartment_to_section": {...},
  "parking_to_places": {...}
}
```

2. **Вложенные объекты:**
   - Здания в комплексах
   - Секции в зданиях
   - Этажи в секциях
   - Квартиры на этажах

3. **Справочники:**
   - Типы отделки
   - Типы комнат
   - Статусы
   - Регионы, районы

### 2.2 Анализ типов данных

#### 2.2.1 Определение типов полей
**Цель:** Определить SQL типы для каждого поля

**Алгоритм:**
1. Для каждого поля проанализировать все значения
2. Определить тип:
   - `string` / `text` / `varchar(n)`
   - `integer` / `bigint`
   - `decimal(n,m)` / `float`
   - `boolean`
   - `date` / `datetime` / `timestamp`
   - `json` / `jsonb`
3. Сохранить в `analysis/field_types.json`

#### 2.2.2 Анализ ограничений
**Цель:** Определить NULL, UNIQUE, INDEX требования

**Что анализировать:**
- Может ли поле быть NULL?
- Должно ли поле быть UNIQUE?
- Нужен ли INDEX для сортировки/фильтрации?
- Какие поля используются для поиска?

### 2.3 Анализ для сортировки и выборки

#### 2.3.1 Поля для сортировки
**Цель:** Выявить все поля, по которым возможна сортировка

**Что искать в API:**
- Параметры `sort` в запросах
- Поля с префиксом `sort_` или `order_by`
- Числовые поля (цена, площадь, этаж)
- Дата поля (срок сдачи, дата создания)

**Сохранить в `analysis/sortable_fields.json`:**
```json
{
  "apartments": {
    "price": {"type": "integer", "indexed": true},
    "area": {"type": "decimal", "indexed": true},
    "floor": {"type": "integer", "indexed": true},
    "deadline": {"type": "date", "indexed": true},
    "name": {"type": "string", "indexed": false}
  },
  ...
}
```

#### 2.3.2 Поля для фильтрации
**Цель:** Выявить все поля, по которым возможна фильтрация

**Что искать:**
- Параметры фильтров в API запросах
- Массивы значений (room, finishing_types)
- Диапазоны (price_from, price_to, area_from, area_to)

**Сохранить в `analysis/filterable_fields.json`**

---

## ФАЗА 3: ПРОЕКТИРОВАНИЕ БД

### 3.1 Концептуальная модель данных

#### 3.1.1 Основные сущности
```
┌─────────────────┐
│   Regions       │ (Регионы: СПб, Москва, и т.д.)
└────────┬────────┘
         │
         │ 1:N
         ▼
┌─────────────────┐
│   Complexes     │ (Комплексы/Блоки - ЖК)
└────────┬────────┘
         │
         │ 1:N
         ├─────────────────┬─────────────────┐
         │                 │                 │
         ▼                 ▼                 ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│  Apartments  │  │   Parkings   │  │  Commercial   │
└──────────────┘  └──────────────┘  └──────────────┘
         │                 │                 │
         │                 │                 │
         ▼                 ▼                 ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│  Buildings   │  │  Places      │  │  (свои связи)│
│  Sections    │  │              │  │              │
│  Floors      │  │              │  │              │
└──────────────┘  └──────────────┘  └──────────────┘

┌──────────────┐
│    Houses    │ (Дома - независимые от комплексов)
└──────────────┘

┌──────────────┐
│    Plots     │ (Участки - независимые от комплексов)
└──────────────┘
```

#### 3.1.2 Принципы проектирования

**1. Полиморфизм для общих полей:**
- Создать базовую таблицу `trendagent_objects` с общими полями
- Использовать полиморфные связи для типов объектов

**2. Гибкость через JSON:**
- Для полей, которые могут отличаться по типам, использовать JSON колонки
- Сохранять полные данные в JSON для возможности расширения

**3. Нормализация справочников:**
- Отдельные таблицы для справочников (типы отделки, статусы, и т.д.)
- Связи через foreign keys

**4. Денормализация для производительности:**
- Дублировать часто используемые поля (цена, площадь) в основные таблицы
- Индексы на полях для сортировки и фильтрации

### 3.2 Структура таблиц (концептуальная)

#### 3.2.1 Базовые таблицы

**`trendagent_regions`**
- id (PK)
- name (название региона)
- code (код: spb, msk)
- created_at, updated_at

**`trendagent_complexes`** (Комплексы/Блоки)
- id (PK)
- region_id (FK)
- external_id (ID из TrendAgent API)
- guid (slug: villa-marina)
- name
- address
- description (text)
- latitude, longitude
- developer_name
- class_type
- deadline
- status
- images (JSON)
- advantages (JSON)
- nearby_places (JSON)
- raw_data (JSON) - полные данные от API
- created_at, updated_at

**`trendagent_buildings`** (Корпуса в комплексах)
- id (PK)
- complex_id (FK)
- external_id
- name
- number
- sections_count
- floors_count
- raw_data (JSON)
- created_at, updated_at

**`trendagent_sections`** (Секции в корпусах)
- id (PK)
- building_id (FK)
- external_id
- name
- number
- floors_count
- raw_data (JSON)
- created_at, updated_at

**`trendagent_floors`** (Этажи в секциях)
- id (PK)
- section_id (FK)
- external_id
- number
- apartments_count
- plan_image_url
- raw_data (JSON)
- created_at, updated_at

#### 3.2.2 Таблицы объектов

**`trendagent_apartments`** (Квартиры)
- id (PK)
- complex_id (FK, nullable) - может быть в комплексе или отдельно
- building_id (FK, nullable)
- section_id (FK, nullable)
- floor_id (FK, nullable)
- external_id
- name
- number
- rooms (integer)
- area_total (decimal)
- area_living (decimal)
- area_kitchen (decimal)
- floor (integer)
- price_base (integer)
- price_full (integer)
- price_per_sqm (integer)
- finishing_type_id (FK)
- status_id (FK)
- balcony_type_id (FK)
- view_type_id (FK)
- is_exclusive (boolean)
- is_booked (boolean)
- plan_image_url
- images (JSON)
- raw_data (JSON)
- created_at, updated_at

**`trendagent_parkings`** (Паркинги)
- id (PK)
- complex_id (FK, nullable)
- external_id
- name
- parking_type_id (FK)
- total_places (integer)
- available_places (integer)
- price_base (integer)
- price_per_month (integer)
- raw_data (JSON)
- created_at, updated_at

**`trendagent_parking_places`** (Места парковки)
- id (PK)
- parking_id (FK)
- external_id
- number
- level (integer)
- status_id (FK)
- price (integer)
- raw_data (JSON)
- created_at, updated_at

**`trendagent_houses`** (Дома)
- id (PK)
- region_id (FK)
- external_id
- name
- address
- land_area (decimal)
- house_area (decimal)
- floors_count (integer)
- rooms_count (integer)
- price_base (integer)
- images (JSON)
- raw_data (JSON)
- created_at, updated_at

**`trendagent_plots`** (Участки)
- id (PK)
- region_id (FK)
- settlement_id (FK) - поселок
- external_id
- number
- area (decimal)
- cadastral_number
- price_base (integer)
- utilities (JSON)
- raw_data (JSON)
- created_at, updated_at

**`trendagent_plot_settlements`** (Поселки)
- id (PK)
- region_id (FK)
- external_id
- name
- address
- description
- images (JSON)
- raw_data (JSON)
- created_at, updated_at

**`trendagent_commercial`** (Коммерческая недвижимость)
- id (PK)
- complex_id (FK, nullable)
- external_id
- name
- commercial_type_id (FK)
- business_type_id (FK)
- area_total (decimal)
- price_base (integer)
- rent_price (integer)
- floor (integer)
- images (JSON)
- raw_data (JSON)
- created_at, updated_at

#### 3.2.3 Справочники

**`trendagent_finishing_types`**
- id (PK)
- name
- code
- created_at, updated_at

**`trendagent_statuses`**
- id (PK)
- name
- code
- type (apartment, parking, house, plot, commercial)
- created_at, updated_at

**`trendagent_parking_types`**
- id (PK)
- name
- code
- created_at, updated_at

**`trendagent_commercial_types`**
- id (PK)
- name
- code
- created_at, updated_at

**`trendagent_business_types`**
- id (PK)
- name
- code
- created_at, updated_at

**`trendagent_balcony_types`**
- id (PK)
- name
- code
- created_at, updated_at

**`trendagent_view_types`**
- id (PK)
- name
- code
- created_at, updated_at

#### 3.2.4 Дополнительные таблицы

**`trendagent_floor_plans`** (Поэтажные планы)
- id (PK)
- complex_id (FK)
- building_id (FK)
- section_id (FK)
- floor_id (FK)
- floor_number (integer)
- image_url
- interactive_data (JSON)
- raw_data (JSON)
- created_at, updated_at

**`trendagent_images`** (Изображения)
- id (PK)
- object_type (apartment, complex, house, plot, commercial)
- object_id
- url
- type (gallery, plan, view)
- order (integer)
- created_at, updated_at

**`trendagent_nearby_places`** (Ближайшие места)
- id (PK)
- complex_id (FK)
- name
- type (subway, school, shop, и т.д.)
- distance (integer) - метры
- latitude, longitude
- created_at, updated_at

### 3.3 Индексы для производительности

#### 3.3.1 Индексы для сортировки
- `trendagent_apartments`: price_base, area_total, floor, rooms
- `trendagent_complexes`: deadline, created_at
- `trendagent_parkings`: price_base
- `trendagent_houses`: price_base, land_area
- `trendagent_plots`: price_base, area

#### 3.3.2 Индексы для фильтрации
- `trendagent_apartments`: complex_id, building_id, section_id, status_id, finishing_type_id
- `trendagent_parkings`: complex_id, parking_type_id
- `trendagent_commercial`: complex_id, commercial_type_id
- Все таблицы: region_id, external_id

#### 3.3.3 Составные индексы
- `trendagent_apartments`: (complex_id, status_id, price_base)
- `trendagent_apartments`: (rooms, area_total, price_base)
- `trendagent_complexes`: (region_id, status)

---

## ФАЗА 4: ПЛАН МИГРАЦИЙ

### 4.1 Последовательность создания миграций

#### 4.1.1 Базовые таблицы (1-5)
1. `create_trendagent_regions_table`
2. `create_trendagent_complexes_table`
3. `create_trendagent_buildings_table`
4. `create_trendagent_sections_table`
5. `create_trendagent_floors_table`

#### 4.1.2 Справочники (6-12)
6. `create_trendagent_finishing_types_table`
7. `create_trendagent_statuses_table`
8. `create_trendagent_parking_types_table`
9. `create_trendagent_commercial_types_table`
10. `create_trendagent_business_types_table`
11. `create_trendagent_balcony_types_table`
12. `create_trendagent_view_types_table`

#### 4.1.3 Таблицы объектов (13-18)
13. `create_trendagent_apartments_table`
14. `create_trendagent_parkings_table`
15. `create_trendagent_parking_places_table`
16. `create_trendagent_houses_table`
17. `create_trendagent_plot_settlements_table`
18. `create_trendagent_plots_table`
19. `create_trendagent_commercial_table`

#### 4.1.4 Дополнительные таблицы (20-22)
20. `create_trendagent_floor_plans_table`
21. `create_trendagent_images_table`
22. `create_trendagent_nearby_places_table`

#### 4.1.5 Индексы (23+)
23. `add_indexes_to_trendagent_tables`

### 4.2 Принципы создания миграций

#### 4.2.1 Гибкость через JSON
- Все таблицы должны иметь колонку `raw_data JSON` для хранения полных данных от API
- Это позволит:
  - Сохранить все данные без потерь
  - Легко добавлять новые поля без миграций
  - Анализировать изменения в структуре API

#### 4.2.2 Версионирование структуры
- Добавить колонку `data_version` для отслеживания версии структуры данных
- Позволит мигрировать данные при изменении структуры API

#### 4.2.3 Мягкие связи
- Использовать `external_id` для связи с данными TrendAgent
- Foreign keys только для внутренних связей (complex_id → complexes.id)
- Это позволит импортировать данные без строгой зависимости от порядка

#### 4.2.4 Аудит и синхронизация
- Колонки `synced_at` для отслеживания последней синхронизации
- Колонки `created_at`, `updated_at` для аудита
- Возможность добавить `deleted_at` для soft deletes

---

## ФАЗА 5: РЕАЛИЗАЦИЯ

### 5.1 Создание Artisan команд

#### 5.1.1 Команда парсинга
```bash
php artisan trendagent:parse
  --region=spb
  --type=all
  --limit=100
  --details=true
  --save-raw=true
```

#### 5.1.2 Команда анализа
```bash
php artisan trendagent:analyze
  --region=spb
  --output=storage/trendagent/parsing/spb/analysis/
```

#### 5.1.3 Команда генерации миграций
```bash
php artisan trendagent:generate-migrations
  --analysis=storage/trendagent/parsing/spb/analysis/
  --output=database/migrations/
```

### 5.2 Структура классов

#### 5.2.1 Сервисы парсинга
- `App\Services\TrendAgent\Parser\ComplexParser`
- `App\Services\TrendAgent\Parser\ApartmentParser`
- `App\Services\TrendAgent\Parser\ParkingParser`
- `App\Services\TrendAgent\Parser\HouseParser`
- `App\Services\TrendAgent\Parser\PlotParser`
- `App\Services\TrendAgent\Parser\CommercialParser`

#### 5.2.2 Сервисы анализа
- `App\Services\TrendAgent\Analyzer\FieldExtractor`
- `App\Services\TrendAgent\Analyzer\RelationshipFinder`
- `App\Services\TrendAgent\Analyzer\TypeAnalyzer`
- `App\Services\TrendAgent\Analyzer\IndexRecommender`

#### 5.2.3 Генераторы миграций
- `App\Services\TrendAgent\Migration\MigrationGenerator`
- `App\Services\TrendAgent\Migration\TableBuilder`
- `App\Services\TrendAgent\Migration\IndexBuilder`

---

## ВЫВОДЫ И РЕКОМЕНДАЦИИ

### Ключевые принципы:
1. **Гибкость через JSON** - хранить полные данные в `raw_data`
2. **Мягкие связи** - использовать `external_id` для связи с TrendAgent
3. **Нормализация справочников** - отдельные таблицы для типов
4. **Денормализация для производительности** - дублировать часто используемые поля
5. **Индексы для сортировки и фильтрации** - на всех полях для выборки

### Следующие шаги:
1. Выполнить парсинг данных для Санкт-Петербурга
2. Провести анализ структуры данных
3. Сгенерировать миграции на основе анализа
4. Протестировать миграции на тестовых данных
5. Оптимизировать структуру на основе реальных запросов
