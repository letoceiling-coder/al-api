# TrendAgent: Данные парсера и отображение в UI

## Сравнение: API vs DB

| Аспект | Прямой API | Парсер (DB) |
|--------|------------|-------------|
| Актуальность | В реальном времени | На момент последнего парсинга |
| Скорость | Один запрос на страницу | Чтение из БД (быстро) |
| Офлайн | Требует доступ к TrendAgent | Работает без внешнего API |

---

## Какие данные нужны для каждой страницы

### 1. Список комплексов (`/trendagent`)
- **Источник:** `trendagent_complexes`
- **Поля:** name, address, min_price, images, apart_count, deadline
- **Парсер:** `parseComplexes()` → `saveComplexToDb()`

### 2. Детали комплекса + таблица квартир + планировки (`/trendagent/apartments/{id}`)
- **Источник:** `trendagent_complexes`, `trendagent_apartments`
- **Квартиры:** number, rooms, floor, area_total, area_kitchen, base_price, full_price, plan_image, building_name, section_name, finishing_name, status, images
- **Парсер:** 
  - `parseComplexDetails()` → checkerboard apartments → `saveApartmentToDb()`
  - `parseApartments()` → global list → `saveApartmentToDb()`

### 3. Таблица квартир по городу (`/objects/table`)
- **Источник:** `trendagent_apartments` (фильтр по region/city)
- **Парсер:** `parseApartments()` для каждого региона

### 4. Планировки по городу (`/objects/plans`)
- **Источник:** тот же `trendagent_apartments`

### 5. Шахматка (`/trendagent/apartments/{id}/checkerboard`)
- **Источник:** API (прямой вызов при TRENDAGENT_DATA_SOURCE=api) или checkerboard buildings + apartments из API
- При `data_source=db` шахматка обычно идёт через API (ApartmentsController → TrendSsoApiAuth)

---

## Маппинг полей UI ↔ БД ↔ Парсер

| UI (ApartmentsTable, HousesPlans) | ApartmentListResource | БД / raw_data |
|----------------------------------|------------------------|---------------|
| number | number | number, flat_number |
| rooms | rooms | rooms, room.crm_id |
| floor | floor | floor |
| area_total, privArea, area | area_total | area_total, area_given, area |
| area_kitchen | — | area_kitchen |
| base_price, price | price_base | price_base, price |
| full_price | price_full | price_full |
| plan_image, plan | plan_image_url | plan_image.url, plan.url, images[0] |
| building_name | building_name | raw_data.building_name, complex.name |
| section_name | section_name | raw_data.section_name, section.name |
| finishing | finishing_name | raw_data.finishing |
| status | status | raw_data.status |
| queue, deadline | queue, deadline | raw_data, complex.deadline |
| images | images | images |

---

## Что изменено для полноты данных

1. **saveApartmentToDb:**
   - `plan_image_url` — из plan, plan_image, images[0]
   - `region_id` — для фильтрации по городу
   - `number` — fallback на flat_number

2. **TrendAgentApiClient.getApartmentCheckerboardApartments:**
   - Добавлены `building_id`, `building_name` в каждый элемент (для raw_data)

3. **ApartmentListResource:**
   - `building_name`, `section_name`, `finishing_name`, `status`, `queue`, `deadline` — из raw_data
   - Добавлено `full_price`

---

## Скорость парсера

**Почему долго:**
- 10 регионов × 9 типов
- Последовательные HTTP-запросы
- Для каждого комплекса: 1 запрос деталей + 1 buildings + N запросов apartments (по корпусам)
- Оценка: 3–8+ часов на полный прогон

**Как ускорить:**
1. Запуск по одному региону: `--region=spb`
2. Запуск в `screen`, чтобы не обрывать по SSH
3. Скрипт `scripts/trendagent_parse_spb.sh` для быстрого обновления СПб

---

## Проверка цепочки

1. **Парсер** → сохраняет в `trendagent_apartments` с `complex_id`, `region_id`, `raw_data`
2. **ApartmentsDbController::show** → `$complex->apartments()` → ApartmentListResource
3. **UI** → получает apartments с полями для таблицы и планировок
