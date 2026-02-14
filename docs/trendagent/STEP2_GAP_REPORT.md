# ШАГ 2. GAP-отчёт миграций TrendAgent

**Дата:** 2026-02-14  
**Цель:** Определить расхождения между существующей схемой и требованиями контракта/импорта.

---

## 1. Что есть (существующие миграции)

| Таблица | Поля | Индексы | FK/Unique |
|---------|------|---------|-----------|
| trendagent_regions | id, code, name, external_id | code (unique) | — |
| trendagent_finishing_types | id, code, name | code (unique) | — |
| trendagent_statuses | id, code, name, type | code, type | — |
| trendagent_parking_types | id, code, name | code | — |
| trendagent_commercial_types | id, code, name | code | — |
| trendagent_business_types | id, code, name | code | — |
| trendagent_balcony_types | id, code, name | code | — |
| trendagent_view_types | id, code, name | code | — |
| trendagent_complexes | id, region_id, external_id, guid, name, address, … | external_id, guid, (region_id,status), deadline | region_id→regions |
| trendagent_buildings | id, complex_id, external_id, … | complex_id, external_id | (complex_id, external_id) unique |
| trendagent_sections | id, building_id, external_id, … | building_id, external_id | (building_id, external_id) unique |
| trendagent_floors | id, section_id, external_id, number, … | section_id, number | (section_id, number) unique |
| trendagent_apartments | id, complex_id, building_id, section_id, floor_id, external_id, … | complex_id, price_base, area_total, floor, rooms, status_id, finishing_type_id, idx_complex_status_price, idx_rooms_area_price | external_id unique |
| trendagent_parkings | id, complex_id, external_id, … | external_id, complex_id, price_base | external_id unique |
| trendagent_parking_places | id, parking_id, external_id, … | parking_id, external_id, status_id | (parking_id, external_id) unique |
| trendagent_houses | id, region_id, external_id, guid, … | external_id, guid, region_id, price_base, land_area | region_id→regions, external_id unique |
| trendagent_plot_settlements | id, region_id, external_id, guid, … | external_id, guid, region_id | region_id→regions, external_id unique |
| trendagent_plots | id, region_id, settlement_id, external_id, … | external_id, settlement_id, region_id, price_base, area | region_id→regions, settlement_id, external_id unique |
| trendagent_commercial | id, complex_id, external_id, … | external_id, complex_id, price_base, rent_price, area_total | external_id unique |
| trendagent_contractors | id, external_id, name, … | external_id | external_id unique |
| trendagent_contractor_projects | id, contractor_id, external_id, guid, … | external_id, guid, contractor_id, min_price | contractor_id, external_id unique |
| trendagent_floor_plans | id, complex_id, building_id, section_id, floor_id, … | complex_id, idx_building_section_floor | unique_plan |
| trendagent_images | id, object_type, object_id, url, type, order_index, local_path | idx_object, type, order_index | — |
| trendagent_nearby_places | id, complex_id, name, type, distance, … | complex_id, type | — |

**add_indexes_to_trendagent_tables:** city_id_extracted (MySQL virtual), idx_created_at, idx_region_id, idx_complex_id на соответствующих таблицах.

---

## 2. Чего не хватает (GAP)

### 2.1. trendagent_apartments — region_id

**Проблема:** Контракт требует фильтрацию по city/region. CityService даёт city.id (MongoDB), Region — code (spb). Маппинг: region.code ↔ city. Апартаменты без `region_id` фильтруются через raw_data.city.id или complex→region, что медленно и не универсально.

**Решение:** Добавить `region_id` (nullable для обратной совместимости). ImportDataCommand уже устанавливает `region_id` при импорте.

**Индексы (контракт db_api_contract.md, фильтры):**
- `region_id`
- `(region_id, price_base)` — price_from/price_to
- `(region_id, rooms)` — room[]
- `(region_id, area_total)` — area_from/area_to

### 2.2. trendagent_images — метаданные

**Проблема:** ImportDataCommand использует поля `mime`, `size`, `hash`, `download_status`, `downloaded_at`. В миграции есть только `local_path`. Без них импорт падает или игнорирует эти поля.

**Решение:** Добавить колонки:
- `mime` varchar(100) nullable
- `size` bigint unsigned nullable
- `hash` varchar(64) nullable (SHA1)
- `download_status` varchar(20) default 'pending' (none|pending|ready|failed)
- `downloaded_at` timestamp nullable

**Индексы:** `hash` (дедупликация), `download_status` (опц.).

### 2.3. trendagent_sync_runs — отсутствует

**Проблема:** ImportDataCommand вызывает `SyncRun::create()` и `SyncRun::update()`, но миграции таблицы нет. Класс SyncRun может не существовать.

**Решение:** Создать таблицу `trendagent_sync_runs`:
- region (string), type (string), started_at, finished_at, status
- created_count, updated_count, skipped_count, error_count
- duration_ms, flags (json), error_summary (text)
- Индексы: region, type, started_at, status

---

## 3. Обязательные индексы по контракту

| Фильтр (контракт) | Таблица | Индекс |
|-------------------|---------|--------|
| city/region | apartments | region_id |
| price_from, price_to | apartments | price_base, (region_id, price_base) |
| area_from, area_to | apartments | area_total, (region_id, area_total) |
| room[] | apartments | rooms, (region_id, rooms) |
| sort (price, deadline, name) | apartments, complexes | price_base, deadline, name |
| external_id (upsert) | все | unique external_id |
| block_id (apartment→complex) | apartments | complex_id (есть) |

---

## 4. parkings и commercial — region_id

**Текущее:** Фильтрация по city — через complex→region. Для паркингов и коммерции можно JOIN complex.

**Рекомендация:** Не добавлять region_id в parkings/commercial в этом шаге. Достаточно apartments.region_id для основного каталога квартир. При необходимости — отдельная миграция.

---

## 5. SQLite-совместимость

- Избегать `GENERATED ALWAYS AS` (MySQL-специфика) в новых миграциях.
- Имена индексов — уникальные, без конфликтов с существующими.
- `bigint` — поддерживается SQLite как INTEGER.
