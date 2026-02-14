# ШАГ 1. ОТЧЁТ АУДИТА ШАБЛОНОВ И КОНТРАКТА API

**Дата:** 2026-02-14  
**Цель:** Зафиксировать, какие endpoints и поля реально использует UI TrendAgent, и подготовить контракт DB API.

---

## 1. Список страниц и компонентов

| Маршрут | Страница/компонент | Тип данных |
|---------|--------------------|------------|
| `/` | ObjectsList | Каталог (комплексы/квартиры/паркинги/дома/участки/коммерция) |
| `/objects/table` | ObjectsTable | Таблица квартир |
| `/objects/plans` | ObjectsPlans | Планировки |
| `/objects/map` | ObjectsMap | Карта комплексов |
| `/villages/list` | VillagesList | Список поселков |
| `/villages/plots` | VillagesPlots | Участки поселка |
| `/villages/map` | VillagesMap | Карта поселков |
| `/village/:slug/plot/:plotId` | PlotDetail | Детали участка |
| `/houseprojects` | HouseProjectsList | Список проектов домов |
| `/houseproject/:slug` | HouseProjectDetail | Детали проекта дома |
| `/:objectType/:id` | ObjectDetail | Детали объекта (ЖК/паркинг/дом/поселок/коммерция) |
| `/houses/:id/checkerboard` | HousesCheckerboard | Шахматка домов |
| `/apartments/:id/checkerboard` | ApartmentsCheckerboard | Шахматка квартир |
| `/apartments/:id/flat/:apartmentId` | FlatDetail | Детали квартиры |
| `/parser` | Parser | UI парсера |

**Blade (вне React):**
| Маршрут | Файл | Использование |
|---------|------|---------------|
| `/trendagent-db`, `/trendagent/db` | trendagent.db | Таблица БД по типам |
| — | — | `GET /api/trendagent/db/apartment/{id}` для модалки деталей квартиры |

---

## 2. Таблица: компонент → endpoint → поля → фильтры

| Компонент | Endpoint | Критичные поля ответа | Фильтры |
|-----------|----------|------------------------|---------|
| **ObjectsList** (blocks) | POST /objects/list (object_type=blocks) | data.objects, total_count, blocks_count; item: _id, id, guid, name, address, image/images/renderer, min_price, deadline, apart_count | city, count, offset |
| **ObjectsList** (apartments) | POST /apartments | data.objects/data; item: _id, id, name, image, price, rooms, area, floor | city, count, offset, room, price_from/to, area_from/to |
| **ObjectsList** (parkings/houses/plots/commercial) | POST /parkings, /houses, /plots, /commercial | data.objects; item: _id, id, guid, name, address, images, min_price, places_count | city, count, offset |
| **ObjectsList** (contractors) | POST /houseprojects (object_type=contractors) | data.objects | city, count, offset |
| **ObjectCard** | — (получает item из списка) | _id, id, guid, name, address, image/images/renderer/gallery/photo, min_price, min_prices, deadline, apart_count, places_count | — |
| **ObjectsTable** | POST /apartments | data.objects/data; item: number, rooms, area, floor, price, plan/plan_image/image, block_id, id, _id | city, count, offset, room, sort, sort_order |
| **ObjectDetail** | POST /apartments/{id}, /parkings/{id}, /houses/{id}, /plots/{id}, /commercial/{id} | data.unified.data, data.apartments, data.buildings, data.parkings, data.commerce, data.plans, data.block_id, data.block_guid | options: unified, buildings, apartments, plans, ... |
| **ObjectHeader** | unifiedData | name, address, description, min_price, min_prices, deadline, renderer, images, image | — |
| **ApartmentsTable** | apartmentsData | grouped_data или data; item: queue, building, section, deadline, base_price, number, rooms, area, plan, image | — |
| **FlatDetail** | POST /apartments/{id}/flat/{apartmentId} | number, floor, area, price, plan, images, status | — |
| **ApartmentsCheckerboard** | POST /apartments/{id}/checkerboard/buildings | data.results/buildings/data; item: id, _id, building_id, block_name | — |
| **ApartmentsCheckerboard** | POST /apartments/{id}/checkerboard/apartments | массив квартир | building_id |
| **ObjectsMap** | POST /apartments (show_type=map) | data.objects; item: lat, lon, id, name, apart_count, min_price, guid | city, room |
| **VillagesList** | POST /objects/list (object_type=villages) | data.objects | city, count, offset |
| **HouseProjectsList** | POST /houseprojects | data.objects | city, count, offset |
| **SearchFilters** | GET /cities | data: [{ id, name }] | — |
| **trendagent.db (Blade)** | GET /api/trendagent/db/apartment/{id} | success, data: number, rooms, area_total, area_living, area_kitchen, floor, price_base, price_full, price_per_sqm, plan_image_url, images, is_booked, raw_data | — |

---

## 3. Критичные поля (без которых UI ломается)

### 3.1. Список (ObjectCard)

| Поле | Тип | Где используется | Альтернативы |
|------|-----|------------------|--------------|
| _id или id | string | Идентификатор, ссылка | Один из двух обязателен |
| name | string | Заголовок карточки | title, block_name, village_name |
| image / images / renderer | object/array | Превью | Без них — placeholder |
| min_price / price / min_prices | number/array | Цена | Может быть null («Цена не указана») |
| address | string | Адрес | Может быть пустым |
| guid | string | URL slug (опц.) | Для /apartments/{guid} |

### 3.2. Детали объекта (ObjectDetail)

| Поле | Тип | Компонент |
|------|-----|-----------|
| unified.data | object | ObjectHeader |
| unified.data.name | string | ObjectHeader |
| apartments.data / grouped_data | array/object | ApartmentsTable |
| block_id, block_guid | string | Ссылки на квартиру |

### 3.3. Квартира (FlatDetail, ApartmentsTable, Blade modal)

| Поле | Тип | Описание |
|------|-----|----------|
| number | string | Номер квартиры |
| rooms | int | Комнаты |
| area_total / area / privArea | number | Площадь |
| floor | int | Этаж |
| price_base / base_price | number | Базовая цена |
| price_full / price | number | Полная цена |
| plan_image_url / plan / plan_image | string/object | План |
| images | array | Галерея |
| id / _id | string | Идентификатор |

### 3.4. Города

| Поле | Тип |
|------|-----|
| id | string |
| name | string |

---

## 4. Минимальный набор endpoints для первого запуска

**Приоритет 1 (MVP — каталог + детали ЖК):**

1. `GET /cities` — список регионов
2. `POST /authenticate` — заглушка
3. `POST /objects/list` с `object_type=blocks` — комплексы (главная карусель)
4. `POST /apartments/{id}` — детали комплекса (ObjectDetail для apartments)
5. `GET /api/trendagent/db/apartment/{id}` — уже реализован (Blade)

**Приоритет 2 (таблица квартир + остальные типы):**

6. `POST /apartments` — список квартир (ObjectsTable)
7. `POST /objects/list` с object_type=parkings, houses, plots, commercial, contractors, villages
8. `POST /parkings/{id}`, `POST /houses/{id}`, `POST /plots/{id}`, `POST /commercial/{id}` — детали

**Приоритет 3 (расширенные страницы):**

9. `POST /apartments/{id}/checkerboard/buildings` + `checkerboard/apartments`
10. `POST /apartments/{id}/flat/{apartmentId}`
11. `POST /apartments` с `show_type=map` (карта)
12. `POST /houseprojects`, `POST /houseprojects/{id}`
13. `POST /apartments/{id}/floor-plan/directory`, `POST /apartments/{id}/floor-plan`
14. `POST /apartments/{id}/map`, `POST /apartments/{id}/gallery`

---

## 5. Что можно отложить

| Функция | Причина |
|---------|---------|
| Шахматка (checkerboard) | Сложная логика, не блокирует базовый просмотр |
| Поэтажный план (floor-plan) | Требует интерактивного режима, можно подгружать позже |
| Карта | Отдельный слой, работает через show_type=map |
| 3D-тур, ипотека, рассрочка, банки | Доп. секции, часто пустые |
| Villages/Plots detail (PlotDetail) | Отдельный поток, меньше трафика |
| HouseProjects | Проекты домов — нишевый раздел |

---

## 6. Соответствие remote-style формату

Контракт DB API должен отдавать ответы в **том же формате**, что и proxy-контроллеры:

- `{ success: true, data: { objects: [...] }, total_count, pagination }` для списков
- `{ success: true, data: { unified: {...}, apartments: {...}, ... } }` для деталей
- Структура элемента: `_id`, `id`, `name`, `image`/`images`, `min_price`, `address`, `guid`, и т.д.

Это позволит переключить источник данных через `TRENDAGENT_DATA_SOURCE=db` без изменений UI.

---

## 7. Артефакты

| Файл | Описание |
|------|----------|
| `docs/trendagent/db_api_contract.md` | Полный контракт эндпоинтов, полей, фильтров, image_url |
| `docs/trendagent/STEP1_REPORT.md` | Этот отчёт |
| `docs/trendagent/UI_FIELD_MAP.md` | Опционально — детальная карта полей по компонентам |

---

## 8. Критерий готовности

По контракту можно реализовать DB API так, что:

- ObjectsList (комплексы) загружается и отображает карточки
- ObjectDetail (ЖК) открывается и показывает секции (квартиры, паркинги, описание и т.д.)
- ObjectsTable (квартиры) работает с пагинацией и фильтрами
- Blade db view по клику на квартиру получает данные через GET /api/trendagent/db/apartment/{id}
- Фильтры (city, room, price, area, sort) применяются без ошибок
- Изображения: при local_path отдаётся Storage::url, иначе — оригинальный URL
