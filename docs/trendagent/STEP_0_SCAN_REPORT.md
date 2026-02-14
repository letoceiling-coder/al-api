# ШАГ 0. ОТЧЁТ СКАНИРОВАНИЯ ПРОЕКТА TRENDAGENT

**Дата:** 2026-02-14  
**Цель:** Точки интеграции, пути хранения, существующие компоненты, типы данных.

---

## 0.1. СТРУКТУРА ПРОЕКТА

### Команды парсинга / импорта

| Файл | Описание |
|------|----------|
| `app/Console/Commands/TrendAgentParse.php` | Основной парсер: `php artisan trendagent:parse --region=spb --type=all --details --save-raw` |
| `app/Console/Commands/TrendAgent/AnalyzeDataCommand.php` | Анализ данных: `php artisan trendagent:analyze-data --region=spb` |
| `app/Console/Commands/TrendAgent/ImportDataCommand.php` | Импорт в БД: `php artisan trendagent:import-data --region=spb --download-images=0` |
| `app/Console/Commands/TrendAgent/CheckDataCommand.php` | Проверка данных |
| `app/Console/Commands/TrendAgent/FetchSampleDataCommand.php` | Выборка образцов |
| `app/Console/Commands/DeployTrendagentCommand.php` | Деплой `projects/trendagent` |

### Пути хранения результатов парсинга

**Основные пути (проверяются оба):**
- `storage/trendagent/parsing/{region}/`
- `storage/app/private/trendagent/parsing/{region}/`

**Структура:**
```
parsing/{region}/
├── raw/{type}/           # list_offset_N.json — списки
│   ├── apartments/
│   ├── complexes/
│   ├── parkings/
│   ├── houses/
│   ├── plots/
│   ├── commercial/
│   └── contractors/
├── details/{type}/       # {id}.json — детали объекта
│   ├── apartments/
│   ├── complexes/
│   ├── parkings/
│   ├── parkings/{id}/    # места парковки
│   ├── houses/
│   ├── plots/
│   ├── commercial/
│   └── contractors/
├── metadata/
│   ├── statistics.json
│   └── errors.json
└── analysis/             # от trendagent:analyze-data
    ├── field_mapping.json
    ├── common_fields.json
    ├── unique_fields.json
    ├── relationships.json
    └── db_schema_proposal.json
```

### Контроллеры и роуты TrendAgent

| Роут | Контроллер | Метод | Описание |
|------|------------|-------|----------|
| `/trendagent-db`, `/trendagent/db` | TrendAgentDbController | index | UI таблицы БД (apartments, complexes, parkings, houses, plots, commercial, contractors) |
| `/api/trendagent/db/apartment/{id}` | TrendAgentDbController | getApartmentDetails | Детали квартиры по внутреннему ID |
| `/api/trendagent/sample-data` | TrendAgentDbController | sampleData | Тестовые данные из remote API (CatalogService + DetailService) |
| `/api/trendagent/v1/*` | ApartmentsController, ParkingsController, HousesController, PlotsController, CommercialController, TrendSsoController | — | Proxy к remote TrendAgent API (требует trendagent.auth) |

### Шаблоны / фронтенд

- **Blade:** `resources/views/trendagent/db.blade.php` — таблица по типам, фильтр region, пагинация.
- **Проект:** `projects/trendagent` — отдельный frontend (React и т.п.), деплоится через `deploy:trendagent`. Данные берёт через API (v1 или sample-data).
- **API v1** — POST-запросы к remote API; `/api/trendagent/db/*` — данные из БД.

---

## 0.2. ФАЙЛЫ-ПЛАНЫ СХЕМЫ БД

| Файл | Содержание |
|------|------------|
| `ANALYSIS_AND_DB_PLAN_COMPLETE.md` | Отчёт анализа, 24 таблицы, связи, пути сохранения |
| `DB_SCHEMA_UPDATED_PLAN.md` | Детальная схема (CREATE TABLE, индексы, FK) |

---

## 0.3. СУЩЕСТВУЮЩИЕ МИГРАЦИИ

Уже созданы и соответствуют плану:

1. `create_trendagent_regions_table`
2. `create_trendagent_reference_tables` (finishing_types, statuses, parking_types, commercial_types, business_types, balcony_types, view_types)
3. `create_trendagent_complexes_table`
4. `create_trendagent_buildings_sections_floors_tables`
5. `create_trendagent_apartments_table`
6. `create_trendagent_parkings_tables`
7. `create_trendagent_houses_table`
8. `create_trendagent_plots_tables` (plot_settlements + plots)
9. `create_trendagent_commercial_table`
10. `create_trendagent_contractors_tables` (contractors + contractor_projects)
11. `create_trendagent_additional_tables` (floor_plans, images, nearby_places)
12. `add_indexes_to_trendagent_tables`

---

## 0.4. МОДЕЛИ ELOQUENT

| Модель | Таблица |
|--------|---------|
| Region | trendagent_regions |
| Complex | trendagent_complexes |
| Building | trendagent_buildings |
| Section | trendagent_sections |
| Floor | trendagent_floors |
| Apartment | trendagent_apartments |
| Parking | trendagent_parkings |
| ParkingPlace | trendagent_parking_places |
| House | trendagent_houses |
| PlotSettlement | trendagent_plot_settlements |
| Plot | trendagent_plots |
| Commercial | trendagent_commercial |
| Contractor | trendagent_contractors |
| ContractorProject | trendagent_contractor_projects |
| FloorPlan | trendagent_floor_plans |
| TrendAgentImage | trendagent_images |
| NearbyPlace | trendagent_nearby_places |

---

## 0.5. ТИПЫ ДАННЫХ (СУЩНОСТИ)

| Тип | Описание | Путь details | Путь raw |
|-----|----------|--------------|----------|
| complexes | ЖК | details/complexes/{id}.json | raw/complexes/list_offset_N.json |
| apartments | Квартиры | details/apartments/{id}.json | raw/apartments/list_offset_N.json |
| parkings | Паркинги | details/parkings/{id}.json | raw/parkings/ |
| parking_places | Места парковки | details/parkings/{id}/*.json или из raw | raw/parkings/list_offset_N.json |
| houses | Дома/коттеджи | details/houses/{id}.json | raw/houses/ |
| plot_settlements | Посёлки | — (создаются при импорте plots) | villages |
| plots | Участки | details/plots/{id}.json | raw/plots/ |
| commercial | Коммерция | details/commercial/{id}.json | raw/commercial/ |
| contractors | Подрядчики | details/contractors/{id}.json | raw/contractors/ |
| contractor_projects | Проекты домов | — (часть contractors) | house_projects |
| floor_plans | Поэтажные планы | из complex detail | — |
| images | Изображения | в raw_data сущностей | — |
| nearby_places | Ближайшие места | из complex detail | — |

---

## 0.6. ТОЧКИ ИНТЕГРАЦИИ

1. **Импорт** — `ImportDataCommand` уже делает upsert по `external_id`, поддерживает `--download-images=1`, `--dry-run`, batch.
2. **Хранение изображений** — `TrendAgentImageStoreService`, таблица `trendagent_images` с `local_path`.
3. **API из БД** — `TrendAgentDbController`: только `getApartmentDetails` по внутреннему ID; для списков используется view (Blade) без REST API.
4. **Связь с фронтом** — `SampleDataService` ходит в remote API; для БД нужен новый API-слой с тем же контрактом.

---

## 0.7. РАСХОЖДЕНИЯ / ЗАМЕЧАНИЯ

1. **Apartment.region_id** — в миграции apartments нет `region_id`, в ImportDataCommand он задаётся; возможно нужна миграция или фильтр только через raw_data.city.
2. **TrendAgentImage** — в миграции нет полей `mime`, `size`, `hash`, `download_status`, `downloaded_at`; в ImportDataCommand они используются. Нужна миграция или упрощение логики.
3. **SyncRun** — в ImportDataCommand используется, таблица может отсутствовать. Проверить миграции.
4. **Команда импорта** — в задании `trendagent:import-db`, в проекте — `trendagent:import-data`. Нужно уточнить имя или добавить alias.

---

## 0.8. РЕЗЮМЕ ДЛЯ СЛЕДУЮЩИХ ШАГОВ

- **ШАГ 1:** Аудит шаблонов и API — какие поля ждёт фронт (CatalogService/DetailService, Blade db.blade).
- **ШАГ 2:** Проверить миграции, при необходимости добавить `region_id` в apartments, поля в trendagent_images, таблицу sync_runs.
- **ШАГ 3:** Дополнить модели (accessor image_url: local_path ? Storage::url : url).
- **ШАГ 4:** Импорт в целом реализован, возможна доработка порядка и idempotency.
- **ШАГ 5:** TrendAgentImageStoreService уже используется в ImportDataCommand.
- **ШАГ 6:** Добавить REST API (list/detail + фильтры) по контракту для фронта.
- **ШАГ 7:** Конфиг TRENDAGENT_DATA_SOURCE=db|remote — переключение источника.
