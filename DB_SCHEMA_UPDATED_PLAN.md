# 📊 ОБНОВЛЕННЫЙ ПЛАН СТРУКТУРЫ БД ДЛЯ TRENDAGENT

**Дата обновления:** 2026-02-08  
**Основа:** Анализ кода, Swagger документации, существующего плана и unified эндпоинтов

---

## ✅ ОСНОВНЫЕ ИЗМЕНЕНИЯ

### 1. Добавлена поддержка подрядчиков (Contractors)
- ✅ Новая таблица `trendagent_contractors` для подрядчиков
- ✅ Новая таблица `trendagent_contractor_projects` для проектов домов
- ✅ Связи между подрядчиками и проектами

### 2. Обновлены эндпоинты для детальных данных
- ✅ Все детальные данные теперь через unified эндпоинты
- ✅ Добавлена поддержка GUID → ID конвертации для некоторых типов

### 3. Уточнена структура на основе реальных API ответов
- ✅ Поля из unified эндпоинтов
- ✅ Структура данных из checkerboard
- ✅ Структура данных из floor_plan

---

## 📋 ПОЛНАЯ СТРУКТУРА БД

### 📊 БАЗОВЫЕ ТАБЛИЦЫ

#### 1. `trendagent_regions`
Регионы (города)

```sql
CREATE TABLE trendagent_regions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) UNIQUE NOT NULL COMMENT 'Код региона (spb, msk)',
    name VARCHAR(255) NOT NULL COMMENT 'Название региона',
    external_id VARCHAR(255) NULL COMMENT 'ID из TrendAgent API',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 2. `trendagent_complexes`
Комплексы/ЖК (блоки)

```sql
CREATE TABLE trendagent_complexes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    region_id BIGINT UNSIGNED NOT NULL,
    external_id VARCHAR(255) UNIQUE NOT NULL COMMENT 'ID из API (например: 63c50acc9a85d53360f63a76)',
    guid VARCHAR(255) NULL COMMENT 'Slug (например: dom-na-naberezhnoy-st)',
    name VARCHAR(500) NOT NULL,
    address TEXT NULL,
    description TEXT NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    developer_name VARCHAR(255) NULL,
    class_type VARCHAR(50) NULL COMMENT 'Класс жилья',
    deadline VARCHAR(255) NULL COMMENT 'Срок сдачи',
    status VARCHAR(50) NULL COMMENT 'Статус комплекса',
    min_price INTEGER NULL COMMENT 'Минимальная цена',
    images JSON NULL COMMENT 'Массив URL изображений',
    advantages JSON NULL COMMENT 'Преимущества комплекса',
    nearby_places JSON NULL COMMENT 'Ближайшие места',
    videos JSON NULL COMMENT 'Видео',
    files JSON NULL COMMENT 'Файлы (документы)',
    raw_data JSON NULL COMMENT 'Полные данные от unified API',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (region_id) REFERENCES trendagent_regions(id) ON DELETE CASCADE,
    INDEX idx_external_id (external_id),
    INDEX idx_guid (guid),
    INDEX idx_region_status (region_id, status),
    INDEX idx_deadline (deadline)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3. `trendagent_buildings`
Корпуса в комплексах

```sql
CREATE TABLE trendagent_buildings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complex_id BIGINT UNSIGNED NOT NULL,
    external_id VARCHAR(255) NOT NULL,
    name VARCHAR(255) NULL,
    number VARCHAR(50) NULL,
    sections_count INTEGER NULL,
    floors_count INTEGER NULL,
    apartments_count INTEGER NULL,
    raw_data JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (complex_id) REFERENCES trendagent_complexes(id) ON DELETE CASCADE,
    INDEX idx_complex_id (complex_id),
    INDEX idx_external_id (external_id),
    UNIQUE KEY unique_complex_building (complex_id, external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 4. `trendagent_sections`
Секции в корпусах

```sql
CREATE TABLE trendagent_sections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    building_id BIGINT UNSIGNED NOT NULL,
    external_id VARCHAR(255) NOT NULL,
    name VARCHAR(255) NULL,
    number VARCHAR(50) NULL,
    floors_count INTEGER NULL,
    apartments_count INTEGER NULL,
    raw_data JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (building_id) REFERENCES trendagent_buildings(id) ON DELETE CASCADE,
    INDEX idx_building_id (building_id),
    INDEX idx_external_id (external_id),
    UNIQUE KEY unique_building_section (building_id, external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5. `trendagent_floors`
Этажи в секциях

```sql
CREATE TABLE trendagent_floors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id BIGINT UNSIGNED NOT NULL,
    external_id VARCHAR(255) NULL,
    number INTEGER NOT NULL,
    apartments_count INTEGER NULL,
    plan_image_url TEXT NULL,
    raw_data JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (section_id) REFERENCES trendagent_sections(id) ON DELETE CASCADE,
    INDEX idx_section_id (section_id),
    INDEX idx_number (number),
    UNIQUE KEY unique_section_floor (section_id, number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 🏠 ТАБЛИЦЫ ОБЪЕКТОВ

#### 6. `trendagent_apartments`
Квартиры

```sql
CREATE TABLE trendagent_apartments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complex_id BIGINT UNSIGNED NULL COMMENT 'Может быть NULL для отдельных квартир',
    building_id BIGINT UNSIGNED NULL,
    section_id BIGINT UNSIGNED NULL,
    floor_id BIGINT UNSIGNED NULL,
    external_id VARCHAR(255) UNIQUE NOT NULL,
    number VARCHAR(50) NULL COMMENT 'Номер квартиры',
    rooms INTEGER NULL COMMENT 'Количество комнат',
    area_total DECIMAL(10, 2) NULL COMMENT 'Общая площадь',
    area_living DECIMAL(10, 2) NULL COMMENT 'Жилая площадь',
    area_kitchen DECIMAL(10, 2) NULL COMMENT 'Площадь кухни',
    floor INTEGER NULL COMMENT 'Этаж',
    price_base INTEGER NULL COMMENT 'Базовая цена',
    price_full INTEGER NULL COMMENT 'Полная цена (100%)',
    price_per_sqm INTEGER NULL COMMENT 'Цена за м²',
    finishing_type_id BIGINT UNSIGNED NULL,
    status_id BIGINT UNSIGNED NULL,
    balcony_type_id BIGINT UNSIGNED NULL,
    view_type_id BIGINT UNSIGNED NULL,
    is_exclusive BOOLEAN DEFAULT FALSE,
    is_booked BOOLEAN DEFAULT FALSE,
    is_on_request BOOLEAN DEFAULT FALSE,
    plan_image_url TEXT NULL,
    images JSON NULL,
    raw_data JSON NULL COMMENT 'Полные данные от unified API',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (complex_id) REFERENCES trendagent_complexes(id) ON DELETE SET NULL,
    FOREIGN KEY (building_id) REFERENCES trendagent_buildings(id) ON DELETE SET NULL,
    FOREIGN KEY (section_id) REFERENCES trendagent_sections(id) ON DELETE SET NULL,
    FOREIGN KEY (floor_id) REFERENCES trendagent_floors(id) ON DELETE SET NULL,
    FOREIGN KEY (finishing_type_id) REFERENCES trendagent_finishing_types(id) ON DELETE SET NULL,
    FOREIGN KEY (status_id) REFERENCES trendagent_statuses(id) ON DELETE SET NULL,
    FOREIGN KEY (balcony_type_id) REFERENCES trendagent_balcony_types(id) ON DELETE SET NULL,
    FOREIGN KEY (view_type_id) REFERENCES trendagent_view_types(id) ON DELETE SET NULL,
    INDEX idx_external_id (external_id),
    INDEX idx_complex_id (complex_id),
    INDEX idx_price_base (price_base),
    INDEX idx_area_total (area_total),
    INDEX idx_floor (floor),
    INDEX idx_rooms (rooms),
    INDEX idx_status_id (status_id),
    INDEX idx_finishing_type_id (finishing_type_id),
    INDEX idx_complex_status_price (complex_id, status_id, price_base),
    INDEX idx_rooms_area_price (rooms, area_total, price_base)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 7. `trendagent_parkings`
Паркинги (комплексы паркингов)

```sql
CREATE TABLE trendagent_parkings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complex_id BIGINT UNSIGNED NULL,
    external_id VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(500) NULL,
    parking_type_id BIGINT UNSIGNED NULL,
    total_places INTEGER NULL,
    available_places INTEGER NULL,
    price_base INTEGER NULL COMMENT 'Базовая цена',
    price_per_month INTEGER NULL COMMENT 'Цена за месяц',
    images JSON NULL,
    raw_data JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (complex_id) REFERENCES trendagent_complexes(id) ON DELETE SET NULL,
    FOREIGN KEY (parking_type_id) REFERENCES trendagent_parking_types(id) ON DELETE SET NULL,
    INDEX idx_external_id (external_id),
    INDEX idx_complex_id (complex_id),
    INDEX idx_price_base (price_base)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 8. `trendagent_parking_places`
Места парковки

```sql
CREATE TABLE trendagent_parking_places (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parking_id BIGINT UNSIGNED NOT NULL,
    external_id VARCHAR(255) NOT NULL,
    number VARCHAR(50) NULL,
    level INTEGER NULL COMMENT 'Уровень парковки',
    status_id BIGINT UNSIGNED NULL,
    price INTEGER NULL,
    raw_data JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (parking_id) REFERENCES trendagent_parkings(id) ON DELETE CASCADE,
    FOREIGN KEY (status_id) REFERENCES trendagent_statuses(id) ON DELETE SET NULL,
    INDEX idx_parking_id (parking_id),
    INDEX idx_external_id (external_id),
    INDEX idx_status_id (status_id),
    UNIQUE KEY unique_parking_place (parking_id, external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 9. `trendagent_houses`
Дома (коттеджи, таунхаусы)

```sql
CREATE TABLE trendagent_houses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    region_id BIGINT UNSIGNED NOT NULL,
    external_id VARCHAR(255) UNIQUE NOT NULL,
    guid VARCHAR(255) NULL COMMENT 'Slug',
    name VARCHAR(500) NULL,
    address TEXT NULL,
    land_area DECIMAL(10, 2) NULL COMMENT 'Площадь участка',
    house_area DECIMAL(10, 2) NULL COMMENT 'Площадь дома',
    floors_count INTEGER NULL,
    rooms_count INTEGER NULL,
    price_base INTEGER NULL,
    images JSON NULL,
    raw_data JSON NULL COMMENT 'Полные данные от unified API',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (region_id) REFERENCES trendagent_regions(id) ON DELETE CASCADE,
    INDEX idx_external_id (external_id),
    INDEX idx_guid (guid),
    INDEX idx_region_id (region_id),
    INDEX idx_price_base (price_base),
    INDEX idx_land_area (land_area)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 10. `trendagent_plot_settlements`
Поселки (для участков)

```sql
CREATE TABLE trendagent_plot_settlements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    region_id BIGINT UNSIGNED NOT NULL,
    external_id VARCHAR(255) UNIQUE NOT NULL,
    guid VARCHAR(255) NULL COMMENT 'Slug',
    name VARCHAR(500) NOT NULL,
    address TEXT NULL,
    description TEXT NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    images JSON NULL,
    raw_data JSON NULL COMMENT 'Полные данные от unified API',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (region_id) REFERENCES trendagent_regions(id) ON DELETE CASCADE,
    INDEX idx_external_id (external_id),
    INDEX idx_guid (guid),
    INDEX idx_region_id (region_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 11. `trendagent_plots`
Участки

```sql
CREATE TABLE trendagent_plots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    region_id BIGINT UNSIGNED NOT NULL,
    settlement_id BIGINT UNSIGNED NOT NULL COMMENT 'Поселок',
    external_id VARCHAR(255) UNIQUE NOT NULL,
    number VARCHAR(50) NULL,
    area DECIMAL(10, 2) NOT NULL COMMENT 'Площадь участка',
    cadastral_number VARCHAR(255) NULL COMMENT 'Кадастровый номер',
    price_base INTEGER NULL,
    utilities JSON NULL COMMENT 'Коммуникации',
    raw_data JSON NULL COMMENT 'Полные данные от unified API',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (region_id) REFERENCES trendagent_regions(id) ON DELETE CASCADE,
    FOREIGN KEY (settlement_id) REFERENCES trendagent_plot_settlements(id) ON DELETE CASCADE,
    INDEX idx_external_id (external_id),
    INDEX idx_settlement_id (settlement_id),
    INDEX idx_region_id (region_id),
    INDEX idx_price_base (price_base),
    INDEX idx_area (area)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 12. `trendagent_commercial`
Коммерческая недвижимость (помещения)

```sql
CREATE TABLE trendagent_commercial (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complex_id BIGINT UNSIGNED NULL,
    external_id VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(500) NULL,
    commercial_type_id BIGINT UNSIGNED NULL,
    business_type_id BIGINT UNSIGNED NULL,
    area_total DECIMAL(10, 2) NULL,
    price_base INTEGER NULL,
    rent_price INTEGER NULL COMMENT 'Цена аренды',
    floor INTEGER NULL,
    images JSON NULL,
    raw_data JSON NULL COMMENT 'Полные данные от unified API',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (complex_id) REFERENCES trendagent_complexes(id) ON DELETE SET NULL,
    FOREIGN KEY (commercial_type_id) REFERENCES trendagent_commercial_types(id) ON DELETE SET NULL,
    FOREIGN KEY (business_type_id) REFERENCES trendagent_business_types(id) ON DELETE SET NULL,
    INDEX idx_external_id (external_id),
    INDEX idx_complex_id (complex_id),
    INDEX idx_price_base (price_base),
    INDEX idx_rent_price (rent_price),
    INDEX idx_area_total (area_total)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 13. `trendagent_contractors` ⭐ НОВОЕ
Подрядчики

```sql
CREATE TABLE trendagent_contractors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    external_id VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(500) NOT NULL,
    description TEXT NULL,
    logo_url TEXT NULL,
    website VARCHAR(500) NULL,
    contact_phone VARCHAR(50) NULL,
    contact_email VARCHAR(255) NULL,
    raw_data JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_external_id (external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 14. `trendagent_contractor_projects` ⭐ НОВОЕ
Проекты домов подрядчиков

```sql
CREATE TABLE trendagent_contractor_projects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contractor_id BIGINT UNSIGNED NOT NULL,
    external_id VARCHAR(255) UNIQUE NOT NULL,
    guid VARCHAR(255) NULL COMMENT 'Slug',
    name VARCHAR(500) NOT NULL,
    description TEXT NULL,
    min_price INTEGER NULL,
    area_total DECIMAL(10, 2) NULL,
    area_living DECIMAL(10, 2) NULL,
    construction_time VARCHAR(255) NULL COMMENT 'Сроки строительства',
    technology VARCHAR(255) NULL COMMENT 'Технология строительства',
    images JSON NULL,
    raw_data JSON NULL COMMENT 'Полные данные от unified API',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (contractor_id) REFERENCES trendagent_contractors(id) ON DELETE CASCADE,
    INDEX idx_external_id (external_id),
    INDEX idx_guid (guid),
    INDEX idx_contractor_id (contractor_id),
    INDEX idx_min_price (min_price)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 📚 СПРАВОЧНИКИ

#### 15. `trendagent_finishing_types`
Типы отделки

```sql
CREATE TABLE trendagent_finishing_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 16. `trendagent_statuses`
Статусы объектов

```sql
CREATE TABLE trendagent_statuses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NULL COMMENT 'Тип объекта (apartment, parking, house, plot, commercial)',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_code (code),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 17. `trendagent_parking_types`
Типы паркингов

```sql
CREATE TABLE trendagent_parking_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 18. `trendagent_commercial_types`
Типы коммерческой недвижимости

```sql
CREATE TABLE trendagent_commercial_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 19. `trendagent_business_types`
Типы бизнеса для коммерции

```sql
CREATE TABLE trendagent_business_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 20. `trendagent_balcony_types`
Типы балконов

```sql
CREATE TABLE trendagent_balcony_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 21. `trendagent_view_types`
Типы видов из окон

```sql
CREATE TABLE trendagent_view_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 📎 ДОПОЛНИТЕЛЬНЫЕ ТАБЛИЦЫ

#### 22. `trendagent_floor_plans`
Поэтажные планы

```sql
CREATE TABLE trendagent_floor_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complex_id BIGINT UNSIGNED NOT NULL,
    building_id BIGINT UNSIGNED NULL,
    section_id BIGINT UNSIGNED NULL,
    floor_id BIGINT UNSIGNED NULL,
    floor_number INTEGER NOT NULL,
    image_url TEXT NULL,
    interactive_data JSON NULL COMMENT 'Интерактивные данные плана',
    raw_data JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (complex_id) REFERENCES trendagent_complexes(id) ON DELETE CASCADE,
    FOREIGN KEY (building_id) REFERENCES trendagent_buildings(id) ON DELETE SET NULL,
    FOREIGN KEY (section_id) REFERENCES trendagent_sections(id) ON DELETE SET NULL,
    FOREIGN KEY (floor_id) REFERENCES trendagent_floors(id) ON DELETE SET NULL,
    INDEX idx_complex_id (complex_id),
    INDEX idx_building_section_floor (building_id, section_id, floor_number),
    UNIQUE KEY unique_plan (complex_id, building_id, section_id, floor_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 23. `trendagent_images`
Изображения (опционально, можно хранить только URL в JSON)

```sql
CREATE TABLE trendagent_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_type VARCHAR(50) NOT NULL COMMENT 'apartment, complex, house, plot, commercial',
    object_id BIGINT UNSIGNED NOT NULL,
    url TEXT NOT NULL,
    type VARCHAR(50) NULL COMMENT 'gallery, plan, view',
    order_index INTEGER DEFAULT 0,
    local_path VARCHAR(500) NULL COMMENT 'Локальный путь, если изображение скачано',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_object (object_type, object_id),
    INDEX idx_type (type),
    INDEX idx_order (order_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 24. `trendagent_nearby_places`
Ближайшие места

```sql
CREATE TABLE trendagent_nearby_places (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complex_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NULL COMMENT 'subway, school, shop, и т.д.',
    distance INTEGER NULL COMMENT 'Расстояние в метрах',
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (complex_id) REFERENCES trendagent_complexes(id) ON DELETE CASCADE,
    INDEX idx_complex_id (complex_id),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🔗 СВЯЗИ МЕЖДУ ТАБЛИЦАМИ

```
trendagent_regions
    ├── trendagent_complexes
    │   ├── trendagent_buildings
    │   │   ├── trendagent_sections
    │   │   │   ├── trendagent_floors
    │   │   │   └── trendagent_apartments
    │   │   └── trendagent_floor_plans
    │   ├── trendagent_apartments
    │   ├── trendagent_parkings
    │   │   └── trendagent_parking_places
    │   ├── trendagent_commercial
    │   └── trendagent_nearby_places
    ├── trendagent_houses
    ├── trendagent_plot_settlements
    │   └── trendagent_plots
    └── (contractors не привязаны к регионам)

trendagent_contractors
    └── trendagent_contractor_projects

Справочники:
    ├── trendagent_finishing_types → trendagent_apartments
    ├── trendagent_statuses → trendagent_apartments, trendagent_parking_places
    ├── trendagent_parking_types → trendagent_parkings
    ├── trendagent_commercial_types → trendagent_commercial
    ├── trendagent_business_types → trendagent_commercial
    ├── trendagent_balcony_types → trendagent_apartments
    └── trendagent_view_types → trendagent_apartments
```

---

## 📝 ПРИМЕЧАНИЯ

1. **JSON поля:** Все таблицы имеют поле `raw_data JSON` для хранения полных данных от unified API. Это позволяет:
   - Сохранить все данные без потерь
   - Легко добавлять новые поля без миграций
   - Анализировать изменения в структуре API

2. **GUID поддержка:** Некоторые таблицы имеют поле `guid` для хранения slug (человекочитаемых идентификаторов), которые используются для конвертации в ID через специальные эндпоинты.

3. **Индексы:** Добавлены индексы для:
   - Сортировки (price, area, floor)
   - Фильтрации (complex_id, status_id, finishing_type_id)
   - Составные индексы для частых запросов

4. **Внешние ключи:** Используются ON DELETE CASCADE для зависимых записей и ON DELETE SET NULL для опциональных связей.

---

## ✅ СЛЕДУЮЩИЕ ШАГИ

1. ✅ Создать миграции для всех таблиц
2. ✅ Создать модели Eloquent с правильными связями
3. ✅ Запустить полный парсинг данных
4. ✅ Загрузить данные в БД
5. ✅ Создать API endpoints для работы с данными

---

**План готов к реализации!** 🚀
