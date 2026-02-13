# Все роуты TrendAgent — полные пути, параметры и пояснения

Базовый URL: **`https://ВАШ_ДОМЕН`** (например `https://api.siteaccess.ru` или `http://al.test`).

Все маршруты под **`/api/trendagent/v1`** требуют заголовок авторизации:
- **`Authorization: Bearer 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF`**

В теле запросов к данным (списки, детали) дополнительно передаются **`phone`** и **`password`** для вызова внешнего API TrendAgent (контроллеры сами выполняют авторизацию через SSO).

---

## 1. Публичные (без Bearer)

| Метод | Полный путь | Параметры | Назначение |
|-------|-------------|-----------|------------|
| **GET** | `https://ВАШ_ДОМЕН/api/trendagent/v1/swagger.json` | — | OpenAPI/Swagger описание TrendAgent API (JSON). |

---

## 2. Управление парсером (без middleware trendagent.auth)

Префикс: **`/api/trendagent/parser`**

| Метод | Полный путь | Параметры (body) | Назначение |
|-------|-------------|------------------|------------|
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/parser/start` | `region` (string, по умолчанию `spb`), `type` (string, по умолчанию `complexes`), `limit` (int, по умолчанию 100000), `details` (bool), `save_raw` (bool) | Запуск парсера по региону и типу объектов в фоне. |
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/parser/start-full` | `region`, `limit`, `details`, `save_raw`, `auto_analyze` (bool) | Запуск полного парсинга всех типов по региону с опциональным последующим анализом. |
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/parser/stop` | — | Остановка запущенного парсера. |
| **GET** | `https://ВАШ_ДОМЕН/api/trendagent/parser/status` | — | Текущий статус парсера (запущен/нет, PID, время старта). |
| **GET** | `https://ВАШ_ДОМЕН/api/trendagent/parser/logs` | Query: `lines` (int, по умолчанию 100) | Последние N строк лога парсера. |
| **GET** | `https://ВАШ_ДОМЕН/api/trendagent/parser/statistics` | — | Статистика последнего/текущего парсинга. |

---

## 3. Авторизация и справочники (v1, с Bearer)

| Метод | Полный путь | Параметры (body) | Назначение |
|-------|-------------|------------------|------------|
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/authenticate` | `phone` (обязательный), `password` (обязательный), `login_url` (необязательно) | Вход через Trend SSO; возвращает `auth_token` и данные сессии для последующих запросов к TrendAgent. |
| **GET** | `https://ВАШ_ДОМЕН/api/trendagent/v1/cities` | — | Список городов TrendAgent (для выбора city в запросах). |

---

## 4. Квартиры (apartments)

Префикс: **`/api/trendagent/v1/apartments`**

| Метод | Полный путь | Параметры (body) | Назначение |
|-------|-------------|------------------|------------|
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/apartments` | `phone`, `password`, `city`, `count`, `offset`, `page`, `sort` (price\|deadline\|name), `sort_order` (asc\|desc), `room` (массив), `price_from`, `price_to`, `area_from`, `area_to`, `floor_from`, `floor_to`, `finishing_types`, `deadline_key`, `text`, `subway`, `region`, `district` | Список квартир с пагинацией и фильтрами. |
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/apartments/{id}` | `phone`, `password`, в URL `id` — ID ЖК (block) или slug | Детальная информация по объекту (ЖК/квартира); `id` — 24-символьный hex или slug. |
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/apartments/{id}/flat/{apartmentId}` | `phone`, `password`; в URL `id` — ID блока, `apartmentId` — ID квартиры | Детальная информация по конкретной квартире в ЖК. |
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/apartments/{id}/floor-plan/directory` | `phone`, `password` | Справочник поэтажных планов по ЖК. |
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/apartments/{id}/floor-plan` | `phone`, `password`, параметры плана (building_id, section_id, floor и т.д.) | Данные поэтажного плана (этаж, секция). |
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/apartments/{id}/checkerboard/buildings` | `phone`, `password`; `id` — ID ЖК (24 hex) | Список корпусов для шахматки по ЖК. |
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/apartments/{id}/checkerboard/apartments` | `phone`, `password`, при необходимости `building_id`; `id` — ID ЖК | Квартиры для шахматки по ЖК (по корпусам). |

---

## 5. Паркинги (parkings)

Префикс: **`/api/trendagent/v1/parkings`**

| Метод | Полный путь | Параметры (body) | Назначение |
|-------|-------------|------------------|------------|
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/parkings` | `phone`, `password`, `city`, `count`, `offset`, `page`, `sort`, `sort_order`, `parking_type`, `price_from`, `price_to`, `text`, `subway`, `region`, `district` | Список паркингов/машиномест с фильтрами. |
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/parkings/{id}` | `phone`, `password`; в URL `id` — ID паркинга | Детали паркинга по ID. |
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/parkings/{id}/places` | `phone`, `password` | Список мест (машиномест) внутри паркинга. |

---

## 6. Дома (houses)

Префикс: **`/api/trendagent/v1/houses`**

| Метод | Полный путь | Параметры (body) | Назначение |
|-------|-------------|------------------|------------|
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/houses` | `phone`, `password`, `city`, `count`, `offset`, `page`, `sort`, `sort_order`, `price_from`, `price_to`, `area_from`, `area_to`, `text`, `subway`, `region`, `district` | Список домов (коттеджи, таунхаусы) с фильтрами. |
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/houses/{id}` | `phone`, `password`, `options` (массив опций); в URL `id` — ID дома | Детальная информация по дому. |
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/houses/{id}/checkerboard/buildings` | `phone`, `password` | Корпуса для шахматки по дому. |
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/houses/{id}/checkerboard/apartments` | `phone`, `password`, `building_id` | Квартиры для шахматки по дому. |

---

## 7. Участки (plots)

Префикс: **`/api/trendagent/v1/plots`**

| Метод | Полный путь | Параметры (body) | Назначение |
|-------|-------------|------------------|------------|
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/plots` | `phone`, `password`, `city`, `count`, `offset`, `page`, `sort`, `sort_order`, `price_from`, `price_to`, `area_from`, `area_to`, `text`, `subway`, `region`, `district` | Список участков/поселков (по данным house-api). |
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/plots/{id}` | `phone`, `password`, `options`; в URL `id` — ID поселка | Детали поселка по ID. |
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/plots/{id}/plot/{plotId}` | `phone`, `password`, `city`; в URL `id` — ID поселка, `plotId` — ID участка | Детальная информация по конкретному участку в поселке. |

---

## 8. Коммерция (commercial)

Префикс: **`/api/trendagent/v1/commercial`**

| Метод | Полный путь | Параметры (body) | Назначение |
|-------|-------------|------------------|------------|
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/commercial` | `phone`, `password`, `city`, `count`, `offset`, `page`, `sort`, `sort_order`, `price_from`, `price_to`, `area_from`, `area_to`, `purpose`, `text`, `subway`, `region`, `district` | Список коммерческих помещений с фильтрами. |
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/commercial/{id}` | `phone`, `password`, `options`; в URL `id` — ID помещения | Детали коммерческого помещения. |

---

## 9. Проекты домов / подрядчики (houseprojects)

Префикс: **`/api/trendagent/v1/houseprojects`**

| Метод | Полный путь | Параметры (body) | Назначение |
|-------|-------------|------------------|------------|
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/houseprojects` | `phone`, `password`, `city`, `count`, `offset`, `page`, `sort`, `sort_order`, при необходимости `object_type=contractors` и фильтры (как в objects/list) | Список проектов домов (подрядчиков). |
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/houseprojects/{id}` | `phone`, `password`; в URL `id` — ID проекта | Детали проекта дома (подрядчика). |

---

## 10. Универсальный список и блоки (v1)

| Метод | Полный путь | Параметры (body) | Назначение |
|-------|-------------|------------------|------------|
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/objects/list` | `phone`, `password`, `city`, `count`, `offset`, `page`, `sort`, `sort_order`, `object_type` (apartments\|parking\|houses\|plots\|commercial\|contractors), `room`, `price_from`, `price_to`, `area_from`, `area_to`, `floor_from`, `floor_to`, `finishing_types`, `parking_type`, `purpose`, `text`, `deadline_key` | Универсальный список объектов по типу: квартиры, паркинги, дома, участки, коммерция, подрядчики. |
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/block/details` | `phone`, `password`, `id` (или slug/guid) | Детали блока (ЖК) по ID или slug. |
| **POST** | `https://ВАШ_ДОМЕН/api/trendagent/v1/block/{dataType}` | `phone`, `password`, `id`; в URL `dataType` — тип данных блока | Получение данных блока по типу (buildings, apartments, parkings, plans, progress и т.д.). |

---

## 11. Веб-интерфейс и тестовые эндпоинты (web, без Bearer)

| Метод | Полный путь | Параметры | Назначение |
|-------|-------------|-----------|------------|
| **GET** | `https://ВАШ_ДОМЕН/trendagent-db` | Query: `region`, `type`, `page` | Страница интерфейса БД TrendAgent (статистика, списки по типам). |
| **GET** | `https://ВАШ_ДОМЕН/trendagent/db` | То же | То же (альтернативный путь). |
| **GET** | `https://ВАШ_ДОМЕН/api/trendagent/db/apartment/{id}` | В URL `id` — внутренний ID квартиры в БД | JSON с деталями квартиры из БД (для выбранной записи). |
| **GET** | `https://ВАШ_ДОМЕН/api/trendagent/sample-data` | Query: `city` (по умолчанию spb), `per_type` (по умолчанию 2), `detail` (1/0) | Тестовый срез данных по всем типам объектов (каталог + детали по первому элементу); ответ — JSON. |

---

## Краткая сводка по параметрам

- **phone**, **password** — учётные данные TrendAgent SSO (в теле POST для v1 данных).
- **city** — код города (`spb`, `msk`) или ID города (MongoDB ObjectId, например `58c665588b6aa52311afa01b`).
- **count** — размер страницы (обычно 1–100).
- **offset** — смещение для пагинации; альтернатива — **page** (номер страницы).
- **sort** — поле сортировки (`price`, `deadline`, `name` и т.д.).
- **sort_order** — `asc` или `desc`.
- **id** в URL — идентификатор объекта (24-символьный hex или slug в зависимости от эндпоинта).

Авторизация доступа к API проекта: заголовок **`Authorization: Bearer 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF`** для всех маршрутов под `/api/trendagent/v1` и `/api/trendagent/parser`.
