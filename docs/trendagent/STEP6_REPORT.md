# ШАГ 6 — DB API + Переключатель источника данных

**Дата:** 2026-02-14

## Выполнено

### 6.1. Переключатель источника

- **config/trendagent.php** — добавлен параметр `data_source`:
  - `env('TRENDAGENT_DATA_SOURCE', 'remote')`
  - `remote` — proxy к внешнему API (требует phone/password)
  - `db` — данные из локальной БД

- **TrendAgentAuthMiddleware** — при `data_source=db` аутентификация пропускается (Bearer токен не требуется).

- **Делегирование в контроллерах** — при `config('trendagent.data_source') === 'db'` вызываются Db-контроллеры:
  - TrendSsoController: authenticate, getCities, getObjectsList, getBlockDetails
  - ApartmentsController: index, show, flatDetail
  - ParkingsController: index, show, places
  - HousesController: index, show
  - PlotsController: index, show, plotDetail
  - CommercialController: index, show

### 6.2. DB контроллеры

| Контроллер | Endpoint | Описание |
|------------|----------|----------|
| CitiesDbController | GET /cities | Список городов (из CityService) |
| AuthenticateDbController | POST /authenticate | Заглушка { authenticated: true } |
| ObjectsDbController | POST /objects/list | Универсальный список по object_type |
| ApartmentsDbController | POST /apartments, /apartments/{id}, /apartments/{id}/flat/{apartmentId} | Квартиры, комплексы, детали |
| ParkingsDbController | POST /parkings, /parkings/{id}, /parkings/{id}/places | Паркинги |
| HousesDbController | POST /houses, /houses/{id} | Дома |
| PlotsDbController | POST /plots, /plots/{id}, /plots/{id}/plot/{plotId} | Участки, поселки |
| CommercialDbController | POST /commercial, /commercial/{id} | Коммерция |
| ContractorsDbController | POST /houseprojects, /houseprojects/{id} | Проекты подрядчиков |

Формат ответа: `{ success, data: { objects }, total_count, pagination: { has_more } }` по контракту db_api_contract.md.

### 6.3. API Resources

- **ApartmentListResource** — список квартир (ObjectCard, ApartmentsTable)
- **ApartmentDetailResource** — детали квартиры (FlatDetail)
- **ComplexResource** — комплексы (ObjectCard, ObjectHeader)
- **CityResource** — города (не используется в текущей реализации)
- **ImageResource** — изображения

Ресурсы не раскрывают raw_data. image_url берётся из accessor TrendAgentImage (Storage::url при local_path).

### 6.4. Фильтрация и сортировка

Используются scopes:
- `scopeRegion` — по city (маппинг через CityService.getCityKeyById)
- `scopePriceBetween`, `scopeAreaBetween`, `scopeRoomsIn` — Apartment
- `scopeSort` — Complex, Apartment
- `scopeRegion` — Parking, House, Commercial, PlotSettlement

Фильтры: city, room, price_from/to, area_from/to, text, sort, sort_order.

### 6.5. Проверка

```bash
# Включить DB-режим
TRENDAGENT_DATA_SOURCE=db

# Проверить
GET /api/trendagent/v1/cities
POST /api/trendagent/v1/apartments (body: { city: "58c665588b6aa52311afa01b", count: 20 })
POST /api/trendagent/v1/apartments/{id}
```

## Артефакты

- `config/trendagent.php`
- `app/Http/Controllers/TrendAgent/Db/*.php`
- `app/Http/Resources/TrendAgent/*.php`
- `docs/trendagent/STEP6_REPORT.md`

## Критерий готовности

UI работает при `TRENDAGENT_DATA_SOURCE=db` без изменений фронтенда.
