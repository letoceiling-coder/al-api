# План парсинга и проектирования БД для TrendAgent

## 📌 Статус: ОБНОВЛЁН после реорганизации проекта (2026-02-07)

## Цель
Создать систему парсинга всех типов объектов TrendAgent (Квартиры, Паркинги, Дома, Участки, Коммерция) для всех регионов с последующим анализом данных и проектированием гибкой структуры БД с правильными связями.

## 🔄 Изменения после реорганизации проекта

### Новая структура URL и маршрутов:
- **TrendAgent React App:** `https://api.siteaccess.ru/trendagent/`
- **TrendAgent API v1:** `https://api.siteaccess.ru/api/trendagent/v1/*`
- **TrendAgent Swagger:** `https://api.siteaccess.ru/swagger/trendagent`
- **TrendAgent Swagger JSON:** `https://api.siteaccess.ru/api/trendagent/v1/swagger.json`

### Новая структура директорий проекта:
```
AL/
├── projects/trendagent/          # Исходники React приложения
│   ├── src/
│   │   ├── services/api.js       # API client (базовый URL: /api/trendagent/v1)
│   │   ├── components/
│   │   ├── pages/
│   │   └── main.jsx              # BrowserRouter с basename="/trendagent"
│   ├── vite.config.js            # base: '/trendagent/', outDir: '../../public/trendagent'
│   └── package.json
│
├── public/trendagent/            # Собранное React приложение
│   ├── index.html
│   └── assets/
│       ├── index-*.js
│       └── index-*.css
│
├── app/Http/Controllers/TrendAgent/  # API контроллеры
│   ├── ApartmentsController.php
│   ├── ParkingsController.php
│   ├── HousesController.php
│   ├── PlotsController.php
│   ├── CommercialController.php
│   └── TrendSsoController.php
│
├── routes/
│   ├── web.php                   # /trendagent/{any} → ProjectController@show
│   └── trendagent.php            # /api/trendagent/v1/* → TrendAgent контроллеры
│
└── storage/
    ├── api-docs/
    │   └── trendagent-swagger.json    # OpenAPI спецификация
    └── trendagent/                    # Новое расположение для парсинга
        └── parsing/                   # (смотри ниже)
```

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

**Расположение контроллера:**
- `app/Console/Commands/TrendAgent/ParseCommand.php`

**Параметры:**
- `--region` - регион (spb, msk, и т.д.)
- `--type` - тип объекта (all, apartments, parkings, houses, plots, commercial, complexes)
- `--limit` - лимит объектов (для тестирования)
- `--offset` - смещение (для продолжения парсинга)
- `--details` - парсить детальные страницы (true/false)
- `--save-raw` - сохранять сырые данные (true/false)

**Использование API:**
- Команда будет использовать существующие контроллеры из `app/Http/Controllers/TrendAgent/`
- Или напрямую обращаться к TrendAgent API через сервис-класс
- Базовый URL API: `/api/trendagent/v1/*`

### 1.2 Парсинг списков объектов

#### 1.2.1 Парсинг комплексов (блоков)
**API Endpoint:** `POST /api/trendagent/v1/objects/list`
**Laravel Route:** `routes/trendagent.php` → `TrendSsoController@getObjectsList`
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
**API Endpoint:** `POST /api/trendagent/v1/apartments`
**Laravel Route:** `routes/trendagent.php` → `ApartmentsController@index`
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
**API Endpoint:** `POST /api/trendagent/v1/parkings`
**Laravel Route:** `routes/trendagent.php` → `ParkingsController@index`
**Файл:** `storage/trendagent/parsing/spb/raw/parkings/list_offset_{offset}.json`

#### 1.2.4 Парсинг домов
**API Endpoint:** `POST /api/trendagent/v1/houses`
**Laravel Route:** `routes/trendagent.php` → `HousesController@index`
**Файл:** `storage/trendagent/parsing/spb/raw/houses/list_offset_{offset}.json`

#### 1.2.5 Парсинг участков
**API Endpoint:** `POST /api/trendagent/v1/plots`
**Laravel Route:** `routes/trendagent.php` → `PlotsController@index`
**Файл:** `storage/trendagent/parsing/spb/raw/plots/list_offset_{offset}.json`

#### 1.2.6 Парсинг коммерции
**API Endpoint:** `POST /api/trendagent/v1/commercial`
**Laravel Route:** `routes/trendagent.php` → `CommercialController@index`
**Файл:** `storage/trendagent/parsing/spb/raw/commercial/list_offset_{offset}.json`

### 1.3 Парсинг детальных страниц

#### 1.3.1 Детали комплекса
**API Endpoint:** `POST /api/trendagent/v1/apartments/{id}` или `POST /api/trendagent/v1/block/details`
**Laravel Routes:** 
- `ApartmentsController@show` - детали комплекса
- `ApartmentsController@checkerboardBuildings` - шахматка корпусов
- `ApartmentsController@checkerboardApartments` - шахматка квартир
- `ApartmentsController@floorPlanDirectory` - директория планов
- `ApartmentsController@floorPlan` - поэтажный план

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
- Шахматка корпусов: `POST /api/trendagent/v1/apartments/{id}/checkerboard/buildings`
- Шахматка квартир: `POST /api/trendagent/v1/apartments/{id}/checkerboard/apartments`
- Поэтажный план: `POST /api/trendagent/v1/apartments/{id}/floor-plan/directory`
- Поэтажный план данные: `POST /api/trendagent/v1/apartments/{id}/floor-plan`

**⭐ ВАЖНО: Полный парсинг всех квартир комплекса**

При парсинге комплекса нужно сохранить **ВСЕ** квартиры со всеми деталями:

1. **Из шахматки получить полный список квартир:**
   - ID квартиры
   - Номер квартиры
   - Корпус, секция, этаж
   - Площадь (общая, кухня, жилая)
   - Количество комнат
   - Цены (базовая, полная, за м²)
   - Статус (свободна, бронь, продана)
   - Тип отделки
   - Вид из окон
   - Является ли эксклюзивной

2. **Для каждой квартиры сохранить:**
   - План квартиры (изображение)
   - Детальную информацию через API: `/apartments/{complexId}/flat/{apartmentId}`
   - Все фотографии (галерея, виды из окон)
   - Характеристики (балкон, лоджия, и т.д.)

3. **Сохранить фильтры и сортировку:**
   - По цене (от-до)
   - По площади (от-до)
   - По этажу (от-до)
   - По количеству комнат
   - По типу отделки
   - По статусу
   - По дедлайну сдачи

4. **Структура сохранения квартир комплекса:**
```
storage/trendagent/parsing/spb/details/complexes/{complex_id}/
├── complex_info.json              # Основная информация о комплексе
├── apartments_list.json           # Список всех квартир из шахматки
├── apartments/                    # Детальная информация каждой квартиры
│   ├── {apartment_id_1}.json
│   ├── {apartment_id_2}.json
│   └── ...
├── images/                        # Изображения комплекса
│   ├── gallery/
│   ├── plans/
│   └── views/
└── apartments_images/             # Изображения квартир
    ├── {apartment_id_1}/
    │   ├── plan.png
    │   ├── gallery/
    │   └── views/
    └── ...
```

#### 1.3.2 Детали квартиры
**API Endpoint:** `POST /api/trendagent/v1/apartments/{id}/flat/{apartmentId}`
**Laravel Route:** `ApartmentsController@flatDetail`
**Файл:** `storage/trendagent/parsing/spb/details/apartments/{apartment_id}.json`

#### 1.3.3 Детали паркинга
**API Endpoint:** 
- `POST /api/trendagent/v1/parkings/{id}` (детали)
- `POST /api/trendagent/v1/parkings/{id}/places` (места парковки)
**Laravel Routes:**
- `ParkingsController@show`
- `ParkingsController@places`
**Файл:** `storage/trendagent/parsing/spb/details/parkings/{parking_id}.json`

**⭐ ВАЖНО: Полный парсинг всех мест парковки**

При парсинге паркинга нужно сохранить **ВСЕ** места со всеми деталями:

1. **Из API получить:**
   - Информацию о паркинге (тип, всего мест, доступно)
   - Список всех парковочных мест
   - Для каждого места: номер, уровень, статус, цена
   - План парковки (если есть)
   - Фотографии парковки

2. **Структура сохранения:**
```
storage/trendagent/parsing/spb/details/parkings/{parking_id}/
├── parking_info.json              # Основная информация
├── places_list.json               # Список всех мест
├── images/                        # Изображения парковки
│   ├── gallery/
│   └── plans/
└── places/                        # Детали каждого места (если есть)
    └── {place_id}.json
```

#### 1.3.4 Детали дома
**API Endpoint:** `POST /api/trendagent/v1/houses/{id}`
**Laravel Route:** `HousesController@show`
**Дополнительно:** Шахматка (если есть)
- `HousesController@checkerboardBuildings`
- `HousesController@checkerboardApartments`
**Файл:** `storage/trendagent/parsing/spb/details/houses/{house_id}.json`

#### 1.3.5 Детали участка
**API Endpoint:** 
- `POST /api/trendagent/v1/plots/{id}` (детали поселка)
- `POST /api/trendagent/v1/plots/{id}/plot/{plotId}` (детали конкретного участка)
**Laravel Routes:**
- `PlotsController@show`
- `PlotsController@plotDetail`
**Файл:** `storage/trendagent/parsing/spb/details/plots/{plot_id}.json`

**⭐ ВАЖНО: Полный парсинг всех участков в поселке**

При парсинге поселка нужно сохранить **ВСЕ** участки со всеми деталями:

1. **Из API получить:**
   - Информацию о поселке
   - Список всех участков
   - Для каждого участка: номер, площадь, кадастровый номер, цена, коммуникации
   - План поселка (генплан)
   - Фотографии поселка и участков

2. **Структура сохранения:**
```
storage/trendagent/parsing/spb/details/plots/{settlement_id}/
├── settlement_info.json           # Информация о поселке
├── plots_list.json                # Список всех участков
├── images/                        # Изображения поселка
│   ├── gallery/
│   └── plans/
└── plots/                         # Детали каждого участка
    ├── {plot_id_1}.json
    ├── {plot_id_2}.json
    └── ...
```

#### 1.3.6 Детали коммерции
**API Endpoint:** `POST /api/trendagent/v1/commercial/{id}`
**Laravel Route:** `CommercialController@show`
**Файл:** `storage/trendagent/parsing/spb/details/commercial/{commercial_id}.json`

### 1.4 Скачивание и хранение изображений

#### 1.4.1 Структура хранения изображений
```
storage/app/public/trendagent/
├── images/
│   ├── complexes/
│   │   └── {complex_id}/
│   │       ├── gallery/          # Фото комплекса
│   │       │   ├── {image_hash}.jpg
│   │       │   └── ...
│   │       ├── plans/            # Планы комплекса (генплан, и т.д.)
│   │       └── apartments/       # Планы квартир
│   │           ├── {apt_id}_plan.png
│   │           └── ...
│   ├── apartments/
│   │   └── {apartment_id}/
│   │       ├── plan.png          # План квартиры
│   │       ├── gallery/          # Фото квартиры
│   │       └── views/            # Виды из окон
│   ├── parkings/
│   │   └── {parking_id}/
│   │       ├── gallery/
│   │       └── plans/
│   ├── houses/
│   │   └── {house_id}/
│   │       ├── gallery/
│   │       ├── plans/
│   │       └── views/
│   ├── plots/
│   │   └── {settlement_id}/
│   │       ├── gallery/          # Фото поселка
│   │       ├── genplan/          # Генплан поселка
│   │       └── plots/            # Фото участков
│   │           └── {plot_id}/
│   └── commercial/
│       └── {commercial_id}/
│           ├── gallery/
│           ├── plans/
│           └── views/
└── thumbnails/                    # Миниатюры для быстрой загрузки
    └── {same_structure}
```

### 1.5 Сохранение данных с поддержкой фильтров и сортировки

**⭐ КРИТИЧЕСКИ ВАЖНО:** При парсинге нужно сохранять данные так, чтобы можно было применять фильтры и сортировку БЕЗ повторного запроса к TrendAgent API.

#### 1.5.1 Структура данных для фильтрации

Для каждого типа объектов сохранять:

**Квартиры:**
```json
{
  "metadata": {
    "complex_id": "63c50acc9a85d53360f63a76",
    "complex_name": "Дом на Набережной",
    "total_apartments": 150,
    "available_apartments": 39,
    "timestamp": "2026-02-07T15:00:00Z"
  },
  "filters": {
    "rooms": [0, 1, 2, 3, 4],
    "price_range": {"min": 2201500, "max": 15000000},
    "area_range": {"min": 23.5, "max": 95.0},
    "floor_range": {"min": 1, "max": 14},
    "finishing_types": ["Без отделки", "С отделкой", "White Box"],
    "statuses": ["Свободная", "Бронь", "Продана"],
    "deadlines": ["2023-Q2", "2023-Q4", "2024-Q1"],
    "buildings": ["1", "2", "3"],
    "sections": ["1", "2", "3", "4"]
  },
  "apartments": [
    {
      "id": "63c5614728d3bcf2420860b1",
      "number": "169",
      "rooms": 0,
      "corpus": "1",
      "section": "4",
      "floor": 7,
      "area_total": 25.9,
      "area_kitchen": 9.1,
      "area_living": null,
      "price_base": 2201500,
      "price_full": 2201500,
      "price_per_sqm": 85000,
      "finishing_type": "С отделкой",
      "finishing_type_id": 2,
      "status": "Свободная",
      "status_id": 1,
      "is_exclusive": false,
      "view_type": "Во двор",
      "view_type_id": 1,
      "deadline": "2023-Q2",
      "has_balcony": false,
      "has_loggia": true,
      "plan_image_url": "https://selcdn.trendagent.ru/images/9s/ry/m_b7eb828fbd2cf76ed684c93e1855787a.png",
      "plan_image_local": "/storage/trendagent/images/apartments/63c5614728d3bcf2420860b1/plan.png",
      "gallery_images": [
        "https://...",
        "..."
      ],
      "detail_url": "/trendagent/apartments/63c50acc9a85d53360f63a76/flat/63c5614728d3bcf2420860b1",
      "raw_data": {...}  // Полный JSON от API
    },
    // ... остальные квартиры
  ]
}
```

**Паркинги:**
```json
{
  "metadata": {...},
  "filters": {
    "parking_types": ["Подземный", "Наземный", "Крытый"],
    "price_range": {"min": 500000, "max": 3000000},
    "levels": [-2, -1, 1, 2],
    "statuses": ["Свободно", "Бронь", "Продано"]
  },
  "places": [
    {
      "id": "...",
      "number": "A-123",
      "level": -1,
      "parking_type": "Подземный",
      "price": 800000,
      "status": "Свободно",
      "plan_image_url": "...",
      "raw_data": {...}
    }
  ]
}
```

**Дома:**
```json
{
  "metadata": {...},
  "filters": {
    "price_range": {"min": 5000000, "max": 50000000},
    "land_area_range": {"min": 500, "max": 5000},
    "house_area_range": {"min": 100, "max": 500},
    "floors": [1, 2, 3],
    "rooms": [3, 4, 5, 6],
    "statuses": [...]
  },
  "houses": [...]
}
```

**Участки:**
```json
{
  "metadata": {...},
  "filters": {
    "price_range": {"min": 1000000, "max": 10000000},
    "area_range": {"min": 600, "max": 3000},
    "utilities": ["Электричество", "Газ", "Вода", "Канализация"],
    "statuses": [...]
  },
  "plots": [...]
}
```

**Коммерция:**
```json
{
  "metadata": {...},
  "filters": {
    "commercial_types": ["Офис", "Торговое помещение", "Склад"],
    "business_types": ["Продажа", "Аренда"],
    "price_range": {...},
    "area_range": {...},
    "floor_range": {...},
    "statuses": [...]
  },
  "commercial": [...]
}
```

#### 1.5.2 Поддержка сортировки

Для каждого списка сохранять несколько версий с разной сортировкой:

```
storage/trendagent/parsing/spb/details/complexes/{complex_id}/
├── apartments_sorted_by_price_asc.json
├── apartments_sorted_by_price_desc.json
├── apartments_sorted_by_area_asc.json
├── apartments_sorted_by_area_desc.json
├── apartments_sorted_by_floor_asc.json
├── apartments_sorted_by_floor_desc.json
└── apartments_sorted_by_deadline.json
```

Или сохранять один файл и сортировать на фронтенде:
```json
{
  "metadata": {...},
  "sort_options": [
    {"field": "price_base", "direction": "asc", "label": "По цене ↑"},
    {"field": "price_base", "direction": "desc", "label": "По цене ↓"},
    {"field": "area_total", "direction": "asc", "label": "По площади ↑"},
    {"field": "area_total", "direction": "desc", "label": "По площади ↓"},
    {"field": "floor", "direction": "asc", "label": "По этажу ↑"},
    {"field": "deadline", "direction": "asc", "label": "По сроку сдачи"}
  ],
  "apartments": [...]
}
```

#### 1.4.2 Логика скачивания изображений
**Принципы:**
1. Скачивать все изображения с донора (selcdn.trendagent.ru, api.trendagent.ru)
2. Сохранять с оригинальным именем или хешем URL
3. Создавать миниатюры для галерей (300x300, 800x800)
4. Обновлять URL в JSON данных на локальные пути
5. Проверять существование перед скачиванием (избегать дубликатов)
6. **Сохранять ВСЕ типы изображений:**
   - Планы квартир/домов/участков
   - Фотогалереи
   - Виды из окон
   - Генпланы комплексов/поселков
   - Поэтажные планы
   - 3D-визуализации (если есть)

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
  - image_type (gallery, plan, view, genplan)
  - object_type (complex, apartment, parking, house, plot, commercial)
  - object_id
  - downloaded_at

#### 1.4.3 Обработка изображений
**Типы изображений:**
- **Галерея** - основные фото объектов
- **Планы** - планировки квартир, поэтажные планы
- **Виды** - виды из окон
- **Иконки** - маленькие превью
- **Генпланы** - планы комплексов, поселков

**Оптимизация:**
- Создавать миниатюры (300x300, 800x800)
- Сжимать JPEG (quality 85)
- Конвертировать в WebP для современных браузеров
- Ленивая загрузка больших изображений

### 1.6 Логика парсинга (обновлённая)

#### 1.6.1 Последовательность парсинга (полная)
1. **Шаг 1:** Получить список всех комплексов (блоков)
   ```
   POST /api/trendagent/v1/objects/list
   → storage/.../raw/complexes/list_offset_0.json
   ```

2. **Шаг 2:** Для каждого комплекса получить детальную информацию
   ```
   POST /api/trendagent/v1/apartments/{id}
   → storage/.../details/complexes/{id}/complex_info.json
   ```

3. **Шаг 3:** Для каждого комплекса получить **ВСЕ квартиры** через шахматку
   ```
   POST /api/trendagent/v1/apartments/{id}/checkerboard/apartments
   → storage/.../details/complexes/{id}/apartments_list.json
   ```

4. **Шаг 4:** Для **КАЖДОЙ** квартиры получить детальную информацию
   ```
   POST /api/trendagent/v1/apartments/{complexId}/flat/{apartmentId}
   → storage/.../details/complexes/{id}/apartments/{apt_id}.json
   ```

5. **Шаг 5:** Скачать **ВСЕ** изображения комплекса и квартир
   - Фото комплекса (галерея)
   - Планы каждой квартиры
   - Виды из окон
   - Генплан комплекса

6. **Шаг 6:** Аналогично для паркингов:
   ```
   POST /api/trendagent/v1/parkings
   → список паркингов
   
   POST /api/trendagent/v1/parkings/{id}
   → детали паркинга
   
   POST /api/trendagent/v1/parkings/{id}/places
   → ВСЕ места парковки
   
   Скачать изображения
   ```

7. **Шаг 7:** Аналогично для домов:
   ```
   POST /api/trendagent/v1/houses
   → список домов
   
   POST /api/trendagent/v1/houses/{id}
   → детали дома + изображения
   ```

8. **Шаг 8:** Аналогично для участков:
   ```
   POST /api/trendagent/v1/plots
   → список поселков
   
   POST /api/trendagent/v1/plots/{id}
   → детали поселка
   
   POST /api/trendagent/v1/plots/{id}/plot/{plotId}
   → детали КАЖДОГО участка
   
   Скачать изображения (генплан, фото)
   ```

9. **Шаг 9:** Аналогично для коммерции:
   ```
   POST /api/trendagent/v1/commercial
   → список коммерции
   
   POST /api/trendagent/v1/commercial/{id}
   → детали + изображения
   ```

10. **Шаг 10:** Создать индексные файлы с фильтрами и сортировкой
    ```
    → apartments_with_filters.json
    → parkings_with_filters.json
    → houses_with_filters.json
    → plots_with_filters.json
    → commercial_with_filters.json
    ```

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

#### 5.2.1 Существующие контроллеры API (уже реализованы)
**Расположение:** `app/Http/Controllers/TrendAgent/`

Используются для прокси-запросов к TrendAgent API:
- `TrendSsoController` - аутентификация, города, списки объектов
- `ApartmentsController` - квартиры и комплексы
- `ParkingsController` - паркинги
- `HousesController` - дома
- `PlotsController` - участки
- `CommercialController` - коммерческая недвижимость

**Middleware:** `app/Http/Middleware/TrendAgentAuthMiddleware.php`
- Проверяет Bearer токен: `8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF`

#### 5.2.2 Новые сервисы парсинга (нужно создать)
**Расположение:** `app/Services/TrendAgent/Parser/`

Эти классы будут использовать существующие контроллеры или напрямую обращаться к TrendAgent API:
- `ComplexParser` - парсинг комплексов
- `ApartmentParser` - парсинг квартир
- `ParkingParser` - парсинг паркингов
- `HouseParser` - парсинг домов
- `PlotParser` - парсинг участков
- `CommercialParser` - парсинг коммерции
- `ImageDownloader` - скачивание изображений

**Базовый класс:**
```php
abstract class BaseParser
{
    protected TrendAgentApiClient $apiClient;
    protected FileStorage $storage;
    
    abstract public function parseList(string $region, int $offset, int $limit): array;
    abstract public function parseDetails(string $id): array;
    abstract public function downloadImages(array $data): void;
}
```

#### 5.2.3 Сервисы анализа (нужно создать)
**Расположение:** `app/Services/TrendAgent/Analyzer/`

- `FieldExtractor` - извлечение всех полей из JSON
- `RelationshipFinder` - поиск связей между объектами
- `TypeAnalyzer` - анализ типов данных
- `IndexRecommender` - рекомендации по индексам

#### 5.2.4 Генераторы миграций (нужно создать)
**Расположение:** `app/Services/TrendAgent/Migration/`

- `MigrationGenerator` - генерация миграций на основе анализа
- `TableBuilder` - построение структуры таблиц
- `IndexBuilder` - создание индексов

#### 5.2.5 Artisan команды (нужно создать)
**Расположение:** `app/Console/Commands/TrendAgent/`

- `ParseCommand` - `php artisan trendagent:parse`
- `AnalyzeCommand` - `php artisan trendagent:analyze`
- `GenerateMigrationsCommand` - `php artisan trendagent:generate-migrations`
- `ImportToDbCommand` - `php artisan trendagent:import` (будущее)

---

## ВЫВОДЫ И РЕКОМЕНДАЦИИ

### Ключевые принципы:
1. **Гибкость через JSON** - хранить полные данные в `raw_data`
2. **Мягкие связи** - использовать `external_id` для связи с TrendAgent
3. **Нормализация справочников** - отдельные таблицы для типов
4. **Денормализация для производительности** - дублировать часто используемые поля
5. **Индексы для сортировки и фильтрации** - на всех полях для выборки

### Интеграция с существующей архитектурой:

#### Использование существующих компонентов:
1. **API контроллеры** (`app/Http/Controllers/TrendAgent/`) - уже готовы и работают
2. **Маршруты** (`routes/trendagent.php`) - все endpoints под `/api/trendagent/v1/*`
3. **Middleware** (`TrendAgentAuthMiddleware`) - аутентификация через Bearer токен
4. **React приложение** (`projects/trendagent/`) - UI для просмотра данных

#### Новые компоненты для парсера:
1. **Artisan команды** - для запуска парсинга и анализа
2. **Сервисы парсинга** - для обработки данных и сохранения в файлы
3. **Модели БД** - после анализа данных и создания миграций
4. **API endpoints для парсера** - возможно добавить в `/api/trendagent/v1/parser/*`

#### Рабочий процесс:
```
1. Парсинг данных
   ↓
   php artisan trendagent:parse --region=spb
   ↓
   Сохранение в storage/trendagent/parsing/spb/
   
2. Анализ данных
   ↓
   php artisan trendagent:analyze --region=spb
   ↓
   Генерация анализа в storage/trendagent/parsing/spb/analysis/
   
3. Создание миграций
   ↓
   php artisan trendagent:generate-migrations
   ↓
   Генерация миграций в database/migrations/
   
4. Применение миграций
   ↓
   php artisan migrate
   
5. Импорт данных в БД
   ↓
   php artisan trendagent:import --region=spb
   
6. Отображение в React приложении
   ↓
   https://api.siteaccess.ru/trendagent/
```

### Следующие шаги:
1. ✅ **Структура проекта обновлена** (2026-02-07)
2. ✅ **API endpoints работают** под `/api/trendagent/v1/*`
3. ✅ **React приложение развёрнуто** на `/trendagent/`
4. ⏳ **Создать Artisan команду парсинга** - `php artisan trendagent:parse`
5. ⏳ **Выполнить парсинг данных** для Санкт-Петербурга
6. ⏳ **Провести анализ структуры данных**
7. ⏳ **Сгенерировать миграции** на основе анализа
8. ⏳ **Протестировать миграции** на тестовых данных
9. ⏳ **Оптимизировать структуру** на основе реальных запросов
10. ⏳ **Интегрировать с React приложением** для отображения данных из БД

### Технические детали реализации:

#### Создание API клиента для парсера:
```php
// app/Services/TrendAgent/TrendAgentApiClient.php
class TrendAgentApiClient
{
    private string $baseUrl = 'https://api.trendagent.ru';
    private string $phone = '+79045393434';
    private string $password = 'nwBvh4q';
    
    public function authenticate(string $city): array;
    public function getObjectsList(string $city, ?string $objectType, int $count, int $offset): array;
    public function getApartments(string $city, array $filters, int $count, int $offset): array;
    public function getApartmentDetails(string $id, array $options): array;
    // ... другие методы
}
```

#### Интеграция с существующими контроллерами:
Парсер может использовать существующие контроллеры через внутренние запросы:
```php
use App\Http\Controllers\TrendAgent\ApartmentsController;

$controller = app(ApartmentsController::class);
$request = Request::create('/api/trendagent/v1/apartments', 'POST', [
    'phone' => '+79045393434',
    'password' => 'nwBvh4q',
    'city' => 'spb',
    'count' => 100,
    'offset' => 0,
]);
$response = $controller->index($request);
```

#### Сохранение результатов:
```php
// Структура сохранения
Storage::put(
    "trendagent/parsing/{$region}/raw/{$type}/list_offset_{$offset}.json",
    json_encode([
        'metadata' => [
            'region' => $region,
            'type' => $type,
            'timestamp' => now()->toIso8601String(),
            'request_params' => $params,
            'total_count' => $totalCount,
            'current_offset' => $offset,
            'items_count' => count($items),
        ],
        'data' => $items,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);
```

---

## 📝 Обновления плана

### 2026-02-07: Реорганизация проекта
- ✅ Обновлены все пути API endpoints на `/api/trendagent/v1/*`
- ✅ Добавлены ссылки на существующие контроллеры Laravel
- ✅ Описана интеграция с текущей архитектурой проекта
- ✅ Добавлены детали о использовании существующих компонентов
- ✅ Обновлена структура директорий проекта
