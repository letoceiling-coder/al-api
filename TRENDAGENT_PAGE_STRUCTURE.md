# Полная структура страниц и API TrendAgent

Документ описывает **страницу списка объектов** (вкладки Комплексы / Квартиры / Планировки / На карте) и **детальную страницу объекта** (на примере Villa Marina и Белая дача): секции, роуты фронта и API, откуда приходят данные.

**City ID (СПб):** `58c665588b6aa52311afa01b`  
**Авторизация:** SSO (sso.trend.tech), затем редирект на `spb.trendagent.ru` с `auth_token` в query.

---

# ЧАСТЬ I. СТРАНИЦА СПИСКА ОБЪЕКТОВ (objects)

**Базовый URL:** `https://spb.trendagent.ru/objects/`

## 1.1 Роуты и вкладки (objects-header__view)

| Вкладка       | Роут (href)       | Описание                          | Источник данных (API) |
|---------------|-------------------|-----------------------------------|------------------------|
| **Комплексы** | `/objects/list/`  | Карточки ЖК (список комплексов)  | `api.trendagent.ru/v4_29/blocks/search/` |
| **Квартиры**  | `/objects/table/` | Таблица квартир по всем объектам | `api.trendagent.ru/v4_29/apartments/search/` |
| **Планировки**| `/objects/plans/` | Планировки квартир (сетка карточек) | `api.trendagent.ru/v4_29/apartments/search/` |
| **На карте**  | `/objects/map/`   | Объекты на карте                  | те же блоки/квартиры + геоданные |

**Query-параметры** (сохраняются при переключении вкладок):  
`apartments-room=30&apartments-room=40` — фильтр по числу комнат (30 = 3к, 40 = 4к).

---

## 1.2 API по вкладкам страницы objects

### Вкладка «Комплексы» (`/objects/list/`)

| API (домен)            | Роут (без auth_token) | Описание |
|------------------------|------------------------|----------|
| **api.trendagent.ru**  | `GET /v4_29/blocks/search/?show_type=list&room=30&room=40&sort=price&sort_order=asc&count=20` | Список блоков (комплексов) с пагинацией. Параметры: `show_type=list`, `room`, `sort`, `sort_order`, `count`. |
| **api.trendagent.ru**  | `GET /v4_29/prelaunches/exists` | Проверка наличия анонсов (фильтр «Анонсы»). |
| **api.trendagent.ru**  | `GET /v4_29/unit_measurements` | Единицы измерения (м², ₽). |
| **apartment-api.trendagent.ru** | `GET /v1/directories?types=subway_distances&types=rooms&types=balcony_types&...&city=...` | Справочники: rooms, balcony_types, banks, building_types, cardinals, contracts, deadlines, elevator_types, escrow_banks, finishings, level_types, locations, mortgage_types, parking_types, payment_types, premise_types, regions, subways, view_places, window_views, window_types. |

Изображения карточек: **selcdn.trendagent.ru** (пути вида `/images/.../m_*.jpg`, `.png`).

---

### Вкладка «Квартиры» (`/objects/table/`)

| API (домен)            | Роут | Описание |
|------------------------|------|----------|
| **api.trendagent.ru**  | `GET /v4_29/apartments/search/?room=30&room=40&sort=price&sort_order=asc&count=50` | Поиск квартир по всем объектам; данные для таблицы. |
| **api.trendagent.ru**  | `GET /v4_29/blocks/search/count/?room=30&room=40&sort=price&sort_order=asc&count=50` | Общее количество (для пагинации/заголовка). |
| **apartment-api.trendagent.ru** | `GET /v1/directories?types=...&city=...` | Те же справочники, что и для списка. |
| **api.trendagent.ru**  | `GET /v4_29/unit_measurements` | Единицы измерения. |
| **api.trendagent.ru**  | `GET /v4_29/prelaunches/exists` | Анонсы. |

---

### Вкладка «Планировки» (`/objects/plans/`)

| API (домен)            | Роут | Описание |
|------------------------|------|----------|
| **api.trendagent.ru**  | `GET /v4_29/apartments/search/?room=30&room=40&sort=price&sort_order=asc&count=30` | Те же квартиры, что и для таблицы; отображение в виде карточек планировок. |
| **api.trendagent.ru**  | `GET /v4_29/blocks/search/count/?room=30&room=40&...` | Количество. |
| **apartment-api.trendagent.ru** | `GET /v1/directories?types=...` | Справочники. |

Изображения планировок: **selcdn.trendagent.ru**.

---

### Вкладка «На карте» (`/objects/map/`)

Роут: `/objects/map/`. Данные для меток/кластеров на карте строятся на основе тех же **blocks** и/или **apartments** (по текущим фильтрам); геоданные корпусов — см. раздел детальной страницы (секция «Карта»). Карта: **api-maps.yandex.ru** (Яндекс.Карты API).

---

## 1.3 Общие запросы для всех вкладок objects

При любой вкладке подгружаются (общие для приложения):  
- **sso-api.trend.tech** `GET /v1/status` — сессия  
- **api.trendagent.ru** `GET /v4_29/notices`, `GET /v4_29/tariffs`  
- **user-api.trendagent.ru** `GET /v1/profile/documents`, `GET /v1/settings`  
- **presentations.trendagent.ru** `GET /v3/presentation_favorites`  
- **comparisons.trendagent.ru** `GET /favorites`  
- **rating-api.trendagent.ru** `GET /my`  
- **webinars-api.trendagent.ru** `GET /v1/events`, `GET /v1/webinar_types`  
- **online.trendagent.ru** socket.io  
- **chat.trendagent.ru** `GET /chats/unread-messages/`  
- Навигация: **modules.trendagent.ru** `GET /navigation/production/header/ru/config.json`, `footer/ru/config.json`

---

# ЧАСТЬ II. ДЕТАЛЬНАЯ СТРАНИЦА ОБЪЕКТА (object)

**Примеры URL:**  
- https://spb.trendagent.ru/object/belaya-dacha/?apartments-room=30&apartments-room=40  
- https://spb.trendagent.ru/object/villa-marina/

**Block ID (villa-marina):** `65c8b45523bccfa820bfaf73`  
**Block ID (belaya-dacha):** `64db7ab977be523b31f3f533`

---

## 2.1 Роуты страницы объекта

| Роут | Описание |
|------|----------|
| `/object/{slug}/` | Главная страница объекта (шапка, галерея, все секции, вкладки Квартиры). |
| `/object/{slug}/checkerboard?...` | Страница «Квартиры на шахматке» (отдельная вкладка/страница). |

Query: `apartments-room=30`, `apartments-room=40`; для checkerboard возможно `apartments-onrequest=true` и т.п.

---

## 2.2 Вкладки секции «Квартиры» (object-apartments__controls)

На детальной странице в блоке квартир три режима:

| Вкладка | Тип/кнопка | Описание | API для данных |
|---------|------------|----------|----------------|
| **Таблица** | `value="table"` | Таблица квартир по корпусам/очередям | `apartments/block/{blockId}/search/`, `blocks/{blockId}/apartments/min-price/`, directories (apartment-api) |
| **Планировки** | `value="plan"` | Планировки по корпусам/этажам | `media/block/{blockId}/plans/` + те же `apartments/block/{blockId}/search/` |
| **Квартиры на шахматке** | Ссылка | Открывается в новой вкладке | Страница `/object/{slug}/checkerboard` → `checkerboards/{blockId}/apartments/buildings/`, `checkerboards/{blockId}/apartments/?building_id=...` |

---

## 2.3 API по секциям детальной страницы объекта

### Инициализация

| API | Роут | Описание |
|-----|------|----------|
| **api.trendagent.ru** | `GET /v4_29/blocks/search/id/?guid={slug}` | Поиск блока по slug (villa-marina, belaya-dacha). Возвращает `block_id`. |
| **api.trendagent.ru** | `GET /v4_29/unit_measurements` | Единицы измерения. |

### Шапка, галерея, описание

| API | Роут | Описание |
|-----|------|----------|
| **api.trendagent.ru** | `GET /v4_29/blocks/{blockId}/unified/?ch=false&formating=true` | Основные данные: название, адрес, застройщик, галерея, описание, характеристики (класс, срок сдачи, тип дома, фасад, отделка, паркинг, лифт, оплата, договор, виды, эскроу). |
| **api.trendagent.ru** | `GET /v4_29/blocks/{blockId}/advantages` | Преимущества. |
| **api.trendagent.ru** | `GET /v4_29/blocks/{blockId}/nearby_places` | Ближайшие места. |

### Секция «Квартиры» — вкладка «Таблица»

| API | Роут | Описание |
|-----|------|----------|
| **api.trendagent.ru** | `GET /v4_29/apartments/block/{blockId}/search/?room=30&room=40` | Квартиры объекта для таблицы. |
| **api.trendagent.ru** | `GET /v4_29/blocks/{blockId}/apartments/min-price/?onrequest=true&reservation=true` | Минимальная цена («от X ₽»). |
| **apartment-api.trendagent.ru** | `GET /v1/directories/blocks/{blockId}?types=rooms&types=balcony_types&types=banks&types=buildings&...` | Справочники по объекту. |
| **api.trendagent.ru** | `GET /v4_29/directories/rooms` | Типы квартир. |
| **api.trendagent.ru** | `GET /v4_29/directories/finishing` | Типы отделки. |

### Секция «Квартиры» — вкладка «Планировки»

| API | Роут | Описание |
|-----|------|----------|
| **api.trendagent.ru** | `GET /v4_29/media/block/{blockId}/plans/?cache=false&formating=true` | Планировки этажей по корпусам. |
| **api.trendagent.ru** | `GET /v4_29/apartments/block/{blockId}/search/` | Квартиры (связь с планировками). |

### Секция «Квартиры на шахматке» (страница checkerboard)

| API | Роут | Описание |
|-----|------|----------|
| **api.trendagent.ru** | `GET /v4_29/checkerboards/{blockId}/apartments/buildings/?room=30&room=40` | Корпуса для шахматки. |
| **api.trendagent.ru** | `GET /v4_29/checkerboards/{blockId}/apartments/?building_id={id}` | Квартиры по этажам для выбранного корпуса. |
| **apartment-api.trendagent.ru** | `GET /v1/directories/blocks/{blockId}/checkerboards?types=...` | Справочники для шахматки. |

---

## 2.3.1 Данные и структура отображения вкладок (object-apartments__controls)

Для страницы https://spb.trendagent.ru/object/villa-marina/ блок переключателей (`object-apartments__controls`) содержит три элемента: **Таблица** (`value="table"`), **Планировки** (`value="plan"`), **Квартиры на шахматке** (ссылка в новую вкладку). Ниже — откуда берутся данные и как они отображаются.

### Вкладка «Таблица»

**Источники данных:**
- `GET /v4_29/apartments/block/{blockId}/search/` — список квартир (опционально `?room=30&room=40` и др.).
- Ответ может содержать:
  - **grouped_data** — объект, ключи типа `"1#4 кв. 2024"` (очередь/срок и т.п.); значение — массив групп, у каждой группы есть `apartments[]`.
  - Либо плоский массив: `data[]`, `apartments.data[]`, `apartments.results[]`, `results[]`.

**Структура одной квартиры (для строки таблицы):**
- Идентификация: `name`, `block_name`, `title`, `id`, `_id`.
- Группировка: `building_name` / `building` / `corpus`, `section_name` / `section`, `queue` / `queue_name`, `deadline` (объект или массив с `deadline`/`value`).
- План/картинка: `image.url`, `images[0]`, `image` (строка), `plan`, `plan_image`.
- Характеристики: `floor`, `number` / `apartment_number`, `privArea` / `area` / `area_total`, `kitchenArea` / `kitchen_area`, `finishing` / `finishing_name`.
- Цены: `base_price`, `price`, `full_price`, `min_prices[].price` / `min_prices[].value`.
- Статус: `status` (строка или объект с `name`), `booking_status`, `is_booked`.
- Прочее: `exclusive` / `is_exclusive`, `view_image` / `view`.

**Отображение:**
- Группы (очередь • корпус • срок сдачи) — сворачиваемые блоки; заголовок группы и количество квартир.
- В развёрнутой группе — таблица: превью плана, корпус, секция, этаж, № кв., S прив., S кухни, отделка, базовая цена, цена при 100%, за м², экскл., статус, вид, действия (сравнение).
- Сортировка по цене (asc/desc) внутри групп.

### Вкладка «Планировки»

**Источники данных:**
- `GET /v4_29/media/block/{blockId}/plans/?cache=false&formating=true` — планировки этажей по корпусам. Ожидаемая структура ответа: массив в `data` или `results`.
- Дополнительно те же данные квартир: `GET /v4_29/apartments/block/{blockId}/search/` (если планы подставляются из квартир: у квартиры поля `plan`, `plan_image`).

**Структура одного плана (для карточки):**
- `image.url`, `images[0]`, `image` (строка), `plan`, `plan_image`.
- `name` / `plan_name`, `area` / `area_from` / `area_to`, `rooms` / `room_count`, `apartment_id` / `id` / `_id`.

**Отображение:**
- Сетка карточек (plans-grid).
- В карточке: изображение плана, название, площадь (м²), количество комнат.

Если в ответе `media/.../plans` нет записей, фронт может строить карточки из квартир с полями `plan`/`plan_image`.

### Вкладка «Квартиры на шахматке»

**Роут:** открывается отдельная страница (новая вкладка):  
`/object/villa-marina/checkerboard?apartments-onrequest=true` (или с `apartments-room=...`).

**Источники данных:**
- `GET /v4_29/checkerboards/{blockId}/apartments/buildings/?room=30&room=40` — список корпусов с количеством квартир.
- `GET /v4_29/checkerboards/{blockId}/apartments/?building_id={buildingId}` — квартиры по этажам/секциям для выбранного корпуса (сетка «шахматка»).
- Справочники: `apartment-api .../directories/blocks/{blockId}/checkerboards?types=...`.

**Отображение:** на странице checkerboard — выбор корпуса, затем сетка по этажам/квартирам (ячейки по этажу и номеру); детали квартиры при клике/наведении. Структура ячеек и полей определяется ответами API checkerboards.

---

### Сводка по вкладкам (Villa Marina)

| Вкладка              | Кнопка/ссылка      | Данные (API) | Структура отображения |
|----------------------|--------------------|--------------|------------------------|
| **Таблица**          | `button value="table"` | `apartments/block/{blockId}/search/` (+ min-price, directories) | Группы по корпусу/очереди/сроку → таблица строк (план, корпус, секция, этаж, № кв., площади, отделка, цены, статус, вид). |
| **Планировки**       | `button value="plan"`  | `media/block/{blockId}/plans/` + те же `apartments/.../search/` | Сетка карточек: изображение плана, название, площадь, комнаты. |
| **Квартиры на шахматке** | `a[href="/object/villa-marina/checkerboard?..."]` | `checkerboards/{blockId}/apartments/buildings/`, `checkerboards/{blockId}/apartments/?building_id=` | Отдельная страница: выбор корпуса → сетка по этажам/квартирам. |

---

### Вознаграждения

| API | Роут | Описание |
|-----|------|----------|
| **rewards-api.trendagent.ru** | `GET /builder-reward-settings?block={blockId}` | Настройки вознаграждений. |
| **rewards-api.trendagent.ru** | `GET /blocks/{blockId}/apartments?builder={builderId}` | Вознаграждения по квартирам. |
| **rewards-api.trendagent.ru** | `GET /directories` | Справочники вознаграждений. |

### Акции и скидки

| API | Роут | Описание |
|-----|------|----------|
| **discounts.trendagent.ru** | `GET /blocks/{blockId}/discounts?builder={builderId}` | Акции и скидки в ЖК. |

### Ипотека

| API | Роут | Описание |
|-----|------|----------|
| **mortgage-api.trendagent.ru** | `GET /types` | Типы ипотечных программ. |
| **mortgage-api.trendagent.ru** | `GET /blocks/{blockId}/?premiseType=apartment` | Программы ипотеки по объекту. |

### Рассрочка

| API | Роут | Описание |
|-----|------|----------|
| **tiny-installments-api.trendagent.ru** | `GET /v1/blocks/{blockId}` | Программы рассрочки по объекту. |

### Отделка

| API | Роут | Описание |
|-----|------|----------|
| **api.trendagent.ru** | `GET /v4_29/finishings/block/{blockId}` | Типы отделки в объекте. |
| **api.trendagent.ru** | `GET /v4_29/finishings/{finishingId}/block/{blockId}` | Детали отделки. |
| **api.trendagent.ru** | `GET /v4_29/media/block/{blockId}/finishing/withtypes` | Медиа отделок. |

### Видео

| API | Роут | Описание |
|-----|------|----------|
| **video.trendagent.ru** | `GET /videos/block/{blockId}` | Видео (шоу-румы, вебинары). |
| **video.trendagent.ru** | `GET /categories` | Категории видео. |

Видеоплеер: **player.vimeo.com**.

### Аэропанорама / 3D-тур

| API | Роут | Описание |
|-----|------|----------|
| **3d-tour-api.trendagent.ru** | `GET /v1/blocks/{blockId}` | Метаданные 3D тура и аэропанорамы. |
| **api.trendagent.ru** | `GET /v4_29/media/block/{blockId}/progress/years` | Годы для таймлайна панорам. |
| **api.trendagent.ru** | `GET /v4_29/media/block/{blockId}/progress/{year}` | Данные по году (например 2024). |

Панорамы: **pano2.trendagent.ru**, **pano-api.trendagent.ru**, превью — **selcdn.trendagent.ru**.

### Карта (секция «На карте»)

| API | Роут | Описание |
|-----|------|----------|
| **api.trendagent.ru** | `GET /v4_29/blocks/{blockId}/geo/buildings` | Геоданные корпусов. |
| **parkings-api.trendagent.ru** | `GET /parkings/block/{blockId}/geo` | Геоданные паркингов. |
| **commerce-api.trendagent.ru** | `GET /search/map/buildings?blocks={blockId}` | Здания коммерции на карте. |

Карта: **api-maps.yandex.ru** (Яндекс.Карты).

### Файлы объекта

| API | Роут | Описание |
|-----|------|----------|
| **files.trendagent.ru** | `GET /fs/breadcrumbs/block/{blockId}` | Хлебные крошки и структура папок. |
| **files.trendagent.ru** | `GET /fs/list/block/{blockId}` | Список файлов (документы, шаблоны). |

### Контакты

| API | Роут | Описание |
|-----|------|----------|
| **contacts-api.trendagent.ru** | `GET /contacts/blocks/{blockId}` | Контакты объекта. |
| **api.trendagent.ru** | `GET /v4_29/contacts/group/?group_code=agency_manager` | Контакты менеджера агентства. |

### Паркинги (если есть)

| API | Роут | Описание |
|-----|------|----------|
| **parkings-api.trendagent.ru** | `GET /blocks/{blockId}` | Паркинги объекта. |
| **parkings-api.trendagent.ru** | `GET /parkings/block/{blockId}` | Список паркингов. |
| **parkings-api.trendagent.ru** | `GET /enums/contract_types`, `/directories/deadlines/`, `/enums/parking_types`, `/enums/payment_types`, `/enums/place_types`, `/directories/sales_start/` | Справочники паркингов. |

### Коммерция (если есть)

| API | Роут | Описание |
|-----|------|----------|
| **commerce-api.trendagent.ru** | `GET /filters?block_id={blockId}&name=buildings&name=...` | Фильтры коммерции. |
| **commerce-api.trendagent.ru** | `GET /search/{blockId}/premises` | Помещения коммерции. |

### Банки эскроу

| API | Роут | Описание |
|-----|------|----------|
| **api.trendagent.ru** | `GET /v4_29/blocks/{blockId}/bank/?cache=false&formating=true` | Банки эскроу по объекту. |

### Метрика/аналитика

| API | Роут | Описание |
|-----|------|----------|
| **metrica.trendagent.ru** | `POST /add/` | События просмотра объекта. |

---

## 2.4 Схема загрузки данных детальной страницы

1. **Инициализация:** `blocks/search/id/?guid={slug}` → `block_id`.
2. **Основа:** `blocks/{blockId}/unified` — шапка, галерея, описание, характеристики.
3. **Квартиры (таблица):** `apartments/block/{blockId}/search/`, `blocks/{blockId}/apartments/min-price/`, `apartment-api/.../directories/blocks/{blockId}`.
4. **Планировки:** `media/block/{blockId}/plans/` + тот же `apartments/block/{blockId}/search/`.
5. **Остальное параллельно:** advantages, nearby_places, finishings, media/finishing, video, mortgage, discounts, rewards, files, contacts, geo/buildings, parkings, commerce, progress/years, progress/{year}, 3d-tour, bank, directories (rooms, finishing).

---

# ЧАСТЬ III. ССЫЛКА НА РОУТЫ СТРАНИЦЫ ОБЪЕКТА (ИСХОДНОЕ ОПИСАНИЕ)

Ниже сохранено детальное описание в формате «роуты + структура» для страницы объекта (Белая дача), согласованное с разделами выше.

---

## 1. РОУТЫ И СТРУКТУРА СТРАНИЦЫ ОБЪЕКТА

См. **Часть II** выше: роуты `/object/{slug}/` и `/object/{slug}/checkerboard`, query `apartments-room=30`, `apartments-room=40`.

---

## 2. ВКЛАДКИ СЕКЦИИ «КВАРТИРЫ» (object-apartments__controls)

На странице есть три режима отображения квартир:

| Вкладка | Тип | Описание | Компонент |
|---------|-----|----------|-----------|
| **Таблица** | `value="table"` | Табличное отображение квартир по корпусам/очередям | `HousesTable` |
| **Планировки** | `value="plan"` | Планировки по корпусам/планам | `HousesPlans` |
| **Квартиры на шахматке** | Ссылка | Открывается в новой вкладке | `/object/belaya-dacha/checkerboard` |

---

## 3. API РОУТЫ ПО СЕКЦИЯМ

### 3.1 Инициализация и поиск блока

| API | Роут | Описание |
|-----|------|----------|
| **api.trendagent.ru** | `GET /v4_29/blocks/search/id/?guid=belaya-dacha` | Поиск блока по slug/guid. Возвращает `block_id` для последующих запросов |
| **api.trendagent.ru** | `GET /v4_29/unit_measurements` | Единицы измерения (м², ₽ и т.д.) |

---

### 3.2 Основные данные объекта (шапка, галерея, описание)

| API | Роут | Описание |
|-----|------|----------|
| **api.trendagent.ru** | `GET /v4_29/blocks/{blockId}/unified/?ch=false&formating=true` | **Главный источник данных** — объединённые данные объекта: название, адрес, застройщик, галерея, описание, характеристики (класс, срок сдачи, тип дома, фасад, отделка, паркинг, лифт, оплата, договор, виды, эскроу) |
| **api.trendagent.ru** | `GET /v4_29/blocks/{blockId}/advantages` | Преимущества объекта |
| **api.trendagent.ru** | `GET /v4_29/blocks/{blockId}/nearby_places` | Ближайшие места (инфраструктура) |

---

### 3.3 Секция «Квартиры» — Вкладка «Таблица»

| API | Роут | Описание |
|-----|------|----------|
| **api.trendagent.ru** | `GET /v4_29/apartments/block/{blockId}/search/?room=30&room=40` | Поиск квартир с фильтрами (room — тип квартиры). Данные для таблицы по корпусам/очередям |
| **api.trendagent.ru** | `GET /v4_29/blocks/{blockId}/apartments/min-price/?onrequest=true&reservation=true` | Минимальная цена квартир (для отображения «от X ₽») |
| **apartment-api.trendagent.ru** | `GET /v1/directories/blocks/{blockId}?types=rooms&types=balcony_types&types=banks&types=buildings&...` | Справочники для квартир: rooms, balcony_types, banks, buildings, building_type, cardinals, contract, buildings_deadline, finishing, premise_types, view_places, window_views и др. |
| **api.trendagent.ru** | `GET /v4_29/directories/rooms` | Справочник типов квартир (студия, 1к, 2к и т.д.) |
| **api.trendagent.ru** | `GET /v4_29/directories/finishing` | Справочник типов отделки |
| **api.trendagent.ru** | `GET /v4_29/directories/apartment/status` | Статусы квартир (свободна, продана, забронирована) |

---

### 3.4 Секция «Квартиры» — Вкладка «Планировки»

| API | Роут | Описание |
|-----|------|----------|
| **api.trendagent.ru** | `GET /v4_29/media/block/{blockId}/plans/?cache=false&formating=true` | Планировки этажей по корпусам/секциям. Используется для вкладки «Планировки» |
| **api.trendagent.ru** | `GET /v4_29/apartments/block/{blockId}/search/?room=30&room=40` | Данные квартир (общие с таблицей) — связь планировок с квартирами |

---

### 3.5 Секция «Квартиры на шахматке» (отдельная страница)

**Роут:** `https://spb.trendagent.ru/object/belaya-dacha/checkerboard?apartments-room[0]=30&apartments-room[1]=40`

| API | Роут | Описание |
|-----|------|----------|
| **api.trendagent.ru** | `GET /v4_29/checkerboards/{blockId}/apartments/buildings/?room=30&room=40` | Список корпусов для шахматки с количеством квартир |
| **api.trendagent.ru** | `GET /v4_29/checkerboards/{blockId}/apartments/?building_id={buildingId}` | Квартиры по этажам/секциям для выбранного корпуса (шахматка) |
| **apartment-api.trendagent.ru** | `GET /v1/directories/blocks/{blockId}/checkerboards?types=rooms&types=balcony_types&...` | Справочники для шахматки |
| **api.trendagent.ru** | `GET /v4_29/blocks/{blockId}/?ch=false&formating=true` | Основные данные блока (без unified) |
| **api.trendagent.ru** | `GET /v4_29/media/block/{blockId}/finishing/withtypes` | Отделки с типами |
| **api.trendagent.ru** | `GET /v4_29/directories/apartment/status` | Статусы квартир |

---

### 3.6 Секция «Вознаграждения»

| API | Роут | Описание |
|-----|------|----------|
| **rewards-api.trendagent.ru** | `GET /builder-reward-settings?block={blockId}` | Настройки вознаграждений застройщика |
| **rewards-api.trendagent.ru** | `GET /blocks/{blockId}/apartments?builder={builderId}` | Вознаграждения по квартирам |
| **rewards-api.trendagent.ru** | `GET /directories` | Справочники вознаграждений |

---

### 3.7 Секция «Акции и скидки»

| API | Роут | Описание |
|-----|------|----------|
| **discounts.trendagent.ru** | `GET /blocks/{blockId}/discounts?builder={builderId}` | Акции и скидки в ЖК |
| **discounts.trendagent.ru** | `GET /blocks/{blockId}/apartments/discounts` | Скидки по квартирам |

---

### 3.8 Секция «Ипотека»

| API | Роут | Описание |
|-----|------|----------|
| **mortgage-api.trendagent.ru** | `GET /blocks/{blockId}/?premiseType=apartment` | Программы ипотеки по объекту |
| **mortgage-api.trendagent.ru** | `GET /types` | Типы ипотечных программ |

---

### 3.9 Секция «Рассрочка»

| API | Роут | Описание |
|-----|------|----------|
| **tiny-installments-api.trendagent.ru** | `GET /v1/blocks/{blockId}` | Программы рассрочки по объекту |

---

### 3.10 Секция «Отделка»

| API | Роут | Описание |
|-----|------|----------|
| **api.trendagent.ru** | `GET /v4_29/finishings/block/{blockId}` | Типы отделки в объекте |
| **api.trendagent.ru** | `GET /v4_29/finishings/{finishingId}/block/{blockId}` | Детали конкретной отделки |
| **api.trendagent.ru** | `GET /v4_29/media/block/{blockId}/finishing/withtypes` | Медиа отделок (фото, описание) |

---

### 3.11 Секция «Видео»

| API | Роут | Описание |
|-----|------|----------|
| **video.trendagent.ru** | `GET /videos/block/{blockId}` | Видео объекта (шоу-румы, вебинары) |
| **video.trendagent.ru** | `GET /categories` | Категории видео |

---

### 3.12 Секция «Аэропанорама»

| API / Ресурс | Роут | Описание |
|--------------|------|----------|
| **3d-tour-api.trendagent.ru** | `GET /v1/blocks/{blockId}` | Метаданные 3D тура и аэропанорамы |
| **pano2.trendagent.ru** | `/belaya-dacha`, `/belaya-dacha/{year}-{month}/{index}/` | Хостинг панорам (HTML, iframe) |
| **pano-api.trendagent.ru** | `/belaya-dacha/{year}-{month}/{index}/panorama.xml`, `panoinfo.json` | XML и JSON панорам |
| **selcdn.trendagent.ru** | `/pano/belaya-dacha/.../preview.jpg` | Превью панорам |

---

### 3.13 Секция «Карта» (На карте)

| API | Роут | Описание |
|-----|------|----------|
| **api.trendagent.ru** | `GET /v4_29/blocks/{blockId}/geo/buildings` | Геоданные корпусов (координаты, полигоны) |
| **parkings-api.trendagent.ru** | `GET /parkings/block/{blockId}/geo` | Геоданные паркингов |
| **commerce-api.trendagent.ru** | `GET /search/map/buildings?blocks={blockId}` | Здания коммерции на карте |
| **overpass.kumi.systems** | Overpass API query | POI: кафе, клиники, школы, магазины, транспорт в радиусе 1 км |
| **api-maps.yandex.ru** | Яндекс.Карты API | Отрисовка карты, тайлы |

---

### 3.14 Секция «Файлы»

| API | Роут | Описание |
|-----|------|----------|
| **files.trendagent.ru** | `GET /fs/breadcrumbs/block/{blockId}` | Хлебные крошки и структура папок |
| **files.trendagent.ru** | `GET /fs/list/block/{blockId}` | Список файлов (документы, презентации, шаблоны) |

---

### 3.15 Секция «Контакты»

| API | Роут | Описание |
|-----|------|----------|
| **contacts-api.trendagent.ru** | `GET /contacts/blocks/{blockId}` | Контакты объекта (менеджеры, застройщик) |
| **api.trendagent.ru** | `GET /v4_29/contacts/group/?group_code=agency_manager` | Контакты менеджера агентства |

---

### 3.16 Ход строительства (прогресс)

| API | Роут | Описание |
|-----|------|----------|
| **api.trendagent.ru** | `GET /v4_29/media/block/{blockId}/progress/years` | Годы и этапы строительства (для карты и фильтров) |

---

### 3.17 Банки и эскроу

| API | Роут | Описание |
|-----|------|----------|
| **api.trendagent.ru** | `GET /v4_29/blocks/{blockId}/bank/?cache=false&formating=true` | Банки эскроу по объекту |

---

## 4. АВТОРИЗАЦИЯ И SSO

| API | Роут | Описание |
|-----|------|----------|
| **sso-api.trend.tech** | `GET /v1/status` | Проверка статуса сессии (auth_token) |
| **user-api.trendagent.ru** | `GET /v1/profile/documents` | Документы профиля |
| **user-api.trendagent.ru** | `GET /v1/settings` | Настройки пользователя |

Параметры запросов: `auth_token`, `city`, `lang=ru`

---

## 5. ВСПОМОГАТЕЛЬНЫЕ СЕРВИСЫ

| Сервис | Назначение |
|--------|------------|
| **online.trendagent.ru** | WebSocket (socket.io) — онлайн-чат, уведомления |
| **chat.trendagent.ru** | Непрочитанные сообщения чата |
| **webinars-api.trendagent.ru** | Вебинары на текущую дату |
| **presentations.trendagent.ru** | Избранные презентации |
| **comparisons.trendagent.ru** | Избранные сравнения |
| **rating-api.trendagent.ru** | Рейтинг агента |
| **api.trendagent.ru/v4_29/notices** | Уведомления |
| **api.trendagent.ru/v4_29/tariffs** | Тарифы |

---

## 6. ИСТОЧНИКИ ИЗОБРАЖЕНИЙ И МЕДИА

| Домен | Описание |
|-------|----------|
| **selcdn.trendagent.ru** | CDN изображений (галерея, планы, превью) |
| **modules.trendagent.ru/icons** | SVG иконки UI |
| **player.vimeo.com** | Видеоплеер (Vimeo) |

---

## 7. СХЕМА ПОТОКА ДАННЫХ

```
1. Загрузка страницы
   └── blocks/search/id/?guid=belaya-dacha → block_id
   └── blocks/{blockId}/unified → основные данные

2. Секция «Квартиры» (Таблица)
   └── apartments/block/{blockId}/search/?room=30&room=40
   └── apartment-api/.../directories/blocks/{blockId}
   └── blocks/{blockId}/apartments/min-price

3. Секция «Квартиры» (Планировки)
   └── media/block/{blockId}/plans
   └── apartments/block/{blockId}/search (те же данные)

4. Квартиры на шахматке (отдельная страница)
   └── checkerboards/{blockId}/apartments/buildings/?room=30&room=40
   └── checkerboards/{blockId}/apartments/?building_id=...
   └── apartment-api/.../directories/blocks/{blockId}/checkerboards

5. Остальные секции загружаются параллельно:
   └── advantages, nearby_places, finishings, media/finishing
   └── video, mortgage, discounts, installments, rewards
   └── files, contacts, geo/buildings, progress/years
   └── 3d-tour, pano
```

---

## 8. ВАЖНЫЕ ПАРАМЕТРЫ

- **block_id:** `64db7ab977be523b31f3f533`
- **builder_id:** `64db781368f69eab9ab7466e`
- **city:** `58c665588b6aa52311afa01b` (СПб)
- **room коды:** `30` — 3-комн., `40` — 4-комн. (из справочника rooms)

---

# ЧАСТЬ IV. СТРАНИЦА КВАРТИРЫ (flat) И ШАХМАТКА (checkerboard)

## 4.1 Страница квартиры (flat)

**URL:** `https://spb.trendagent.ru/object/{slug}/flat/{apartmentId}?apartments-onrequest=true&lang=ru`

**Пример:** https://spb.trendagent.ru/object/villa-marina/flat/65c9e2d423bccf0d3ebfd5c6?apartments-onrequest=true&lang=ru

| Элемент | Значение |
|--------|----------|
| **Роут фронта** | `/object/{slug}/flat/{apartmentId}` |
| **slug** | Идентификатор объекта (villa-marina). |
| **apartmentId** | ID квартиры (MongoDB ObjectId, напр. `65c9e2d423bccf0d3ebfd5c6`). |

**Query:** `apartments-onrequest=true`, `lang=ru`.

### API для страницы flat (проверено по коду)

| Домен | Метод и путь | Параметры query | Описание |
|-------|--------------|-----------------|----------|
| **api.trendagent.ru** | `GET /v4_29/blocks/search/id/` | `guid={slug}`, `city`, `lang`, `auth_token` | Получение block_id по slug. |
| **api.trendagent.ru** | `GET /v4_29/apartments/block/{blockId}/apartment/{apartmentId}/` | `city=58c665588b6aa52311afa01b`, `lang=ru`, `auth_token` | Детальная карточка квартиры (основной запрос). При 404 делается fallback на следующий. |
| **api.trendagent.ru** | `GET /v4_29/apartments/{apartmentId}/` | те же | Fallback, если запрос с blockId вернул 404. |
| **api.trendagent.ru** | `GET /v4_29/blocks/{blockId}/unified/` | `ch=false`, `formating=true`, `city`, `lang`, `auth_token` | Данные объекта (название, застройщик) для шапки/хлебных крошек. |
| **apartment-api.trendagent.ru** | `GET /v1/directories/blocks/{blockId}?types=...` | по справочникам | rooms, balcony_types, finishings и т.д. |

### Локальный прокси (проект AL)

| Метод | Локальный роут | Тело (JSON) | Описание |
|-------|-----------------|-------------|----------|
| POST | `/api/trendagent/apartments/{id}/flat/{apartmentId}` | `phone`, `password` | Детальная информация о квартире + данные блока. Контроллер: `ApartmentsController::flatDetail`; внутри: `getApartmentDetail(apartmentId, blockId)` + `getBlockUnified(blockId)`. |

**Структура страницы:** шапка с объектом и квартирой; характеристики квартиры (площадь, этаж, корпус, отделка, цена, статус); план; кнопки «Зафиксировать», «Сравнение» и т.д.

---

## 4.2 Страница «Квартиры на шахматке» (checkerboard)

**URL:** `https://spb.trendagent.ru/object/{slug}/checkerboard?apartments-onrequest=true`

**Пример:** https://spb.trendagent.ru/object/villa-marina/checkerboard?apartments-onrequest=true

**Роут фронта:** `/object/{slug}/checkerboard`. Query: `apartments-onrequest=true`, возможно `apartments-room=30&apartments-room=40`.

### API для шахматки (проверено по коду)

| Домен | Метод и путь | Параметры query | Описание |
|-------|--------------|-----------------|----------|
| **api.trendagent.ru** | `GET /v4_29/blocks/search/id/` | `guid={slug}`, `city`, `lang`, `auth_token` | Получение block_id по slug. |
| **api.trendagent.ru** | `GET /v4_29/checkerboards/{blockId}/apartments/buildings/` | `city`, `lang`, `auth_token`, опционально `room=30&room=40` (многократно) | Список корпусов для выбора. |
| **api.trendagent.ru** | `GET /v4_29/checkerboards/{blockId}/apartments/` | `city`, `lang`, `auth_token`, **`building_id`** (обязательный для квартир) | Квартиры по этажам для выбранного корпуса — сетка «шахматка». |
| **apartment-api.trendagent.ru** | `GET /v1/directories/blocks/{blockId}/checkerboards?types=...` | — | Справочники (комнаты, отделка, статусы). |
| **api.trendagent.ru** | `GET /v4_29/blocks/{blockId}/unified/` | при необходимости | Название объекта. |

### Локальный прокси (проект AL)

| Метод | Локальный роут | Тело (JSON) | Описание |
|-------|-----------------|-------------|----------|
| POST | `/api/trendagent/apartments/{id}/checkerboard/buildings` | `phone`, `password`, опционально `room` (массив) | Корпуса для шахматки. Контроллер: `ApartmentsController::checkerboardBuildings`. |
| POST | `/api/trendagent/apartments/{id}/checkerboard/apartments` | `phone`, `password`, **`building_id`** (обязательный) | Квартиры по корпусу. Контроллер: `ApartmentsController::checkerboardApartments`. |

**Структура отображения:** выбор корпуса (dropdown или кнопки) → сетка по этажам и номерам квартир; ячейка = квартира (номер, площадь, цена, статус); клик по ячейке ведёт на `/object/{slug}/flat/{apartmentId}`.

---

# ЧАСТЬ V. ПОСЁЛКИ (villages)

**Базовый URL:** `https://spb.trendagent.ru/villages/`

## 5.1 Роуты и вкладки (villages-tabs)

Навигация: `<nav class="villages-tabs">` — три элемента: **Посёлки** (href `/villages/list`), **Участки** (href `/villages/plots`), **На карте** (href `/villages/map/`).

| Вкладка | Роут (href) | Описание | API для данных |
|---------|-------------|----------|----------------|
| **Посёлки** | `/villages/list` | Список посёлков (карточки) | **house-api.trendagent.ru** `GET /v1/search/villages` |
| **Участки** | `/villages/plots` | Список участков | **house-api.trendagent.ru** `GET /v1/search/plots` |
| **На карте** | `/villages/map/` | Посёлки/участки на карте | Те же данные + геоданные |

**Общие параметры запросов (house-api):** `auth_token`, `city=58c665588b6aa52311afa01b`, `lang=ru`, `count`, `offset`, `sort_type`, `sort_order`. Для house-api используется `sort_type` (не `sort`).

---

## 5.2 API по вкладкам villages (проверено по коду)

### Вкладка «Посёлки» (`/villages/list`)

| Домен | Метод и путь | Параметры query (по умолчанию в коде) | Описание |
|-------|--------------|----------------------------------------|----------|
| **house-api.trendagent.ru** | `GET /v1/search/villages` | `auth_token`, `city=58c665588b6aa52311afa01b`, `lang=ru`, `sort_type=price`, `sort_order=asc`, `count=20`, `offset=0` | Список посёлков. |

**Ответ API:** `list[]` — массив элементов; `total_count`, `result_count`, `plots_count`, `villages_count`.

**Структура элемента посёлка (после обработки в коде):** `id`, `_id`, `guid`, `name`, `village_name`, `address`, `plots_count`, `view_plots_count`, `builder`, `distance` (center, railway, highway), `deadline`, `min_prices[]` (label, value, unit), `reward`, `reward_hint`, `sales_start`, `images[]` (thumbnail/full — selcdn.trendagent.ru), `is_new_village`, `property_types`, а также паспорт: `village_class`, `unified_style`, `land_purpose`, `water_supply`, `sewerage`, `gas_supply`, `electricity`, `power_kw`, `management_company`, `registration`, `fiber_internet`, `road`, `payment`, `contract`, `escrow`, `description`, `about`.

### Вкладка «Участки» (`/villages/plots`)

| Домен | Метод и путь | Параметры query | Описание |
|-------|--------------|-----------------|----------|
| **house-api.trendagent.ru** | `GET /v1/search/plots` | `auth_token`, `city`, `lang=ru`, `sort_type=price`, `sort_order=asc`, `count=20`, `offset=0` (в коде `sort` преобразуется в `sort_type`) | Список участков. |

**Ответ API:** `list[]`, `total_count`, `result_count`, `plots_count`.

**Структура элемента участка (после обработки в коде):** `id`, `_id`, `guid`, `name`, `village_name`, `address`, `plots_count`, `view_plots_count`, `builder`, `distance`, `deadline`, `min_prices[]`, `reward`, `reward_hint`, `sales_start`, `images[]`, `is_new_village`, `property_types`.

### Вкладка «На карте» (`/villages/map/`)

Роут фронта: `/villages/map/`. Данные для меток — те же **villages** и/или **plots** (те же API); геоданные могут быть в полях элементов или отдельный endpoint (уточнить по house-api). Карта: **api-maps.yandex.ru** (Яндекс.Карты).

### Локальный прокси (проект AL)

| Метод | Локальный роут | Тело/параметры | Внутренний API |
|-------|----------------|----------------|----------------|
| POST | `/api/trendagent/objects/list` | `phone`, `password`, `object_type`, `count`, `offset`, `sort`, `sort_order` | При `object_type=plots` → `getPlotsSearch` (house-api /v1/search/plots). Для списка посёлков отдельный `object_type` в этом роуте не задан — данные посёлков даёт `getVillagesSearch` (house-api /v1/search/villages). |
| POST | `/api/trendagent/plots` | `phone`, `password`, `count`, `offset`, `sort`, `city`, фильтры | PlotsController::index — внутри вызывает **getVillagesSearch** (возвращает поселки с участками). Для списка именно участков использовать `objects/list` с `object_type=plots`. |

---

# ЧАСТЬ VI. ПОСЕЛОК (village) И УЧАСТОК (plot)

## 6.1 Страница поселка (village)

**URL:** `https://spb.trendagent.ru/village/{slug}`

**Пример:** https://spb.trendagent.ru/village/lebyazhe

**Роут фронта:** `/village/{slug}` (slug — например lebyazhe).

### API для страницы поселка (проверено по коду)

Прямого endpoint вида `GET /v1/villages/{slug}` или `GET /v1/villages/{id}` в коде **нет**. Используется:

| Домен | Метод и путь | Как используется |
|-------|--------------|------------------|
| **api.trendagent.ru** | `GET /v4_29/blocks/search/id/` | Параметр `guid={slug}` — попытка получить block_id по slug (для поселков может не сработать, т.к. поселки в house-api). |
| **house-api.trendagent.ru** | `GET /v1/search/villages` | Список посёлков с пагинацией; затем в коде поиск элемента с `_id === villageId` или `id === villageId`. |
| **getVillageById(villageId)** | — | Реализация: вызывает `getVillagesSearch($params)` и находит в `data[]` элемент с совпадающим `_id` или `id`. Отдельного запроса по одному поселку к house-api нет. |
| **getBlockFullData(villageId, options)** | api.trendagent.ru blocks/unified, buildings, plans, progress, advantages, nearby_places, min_price, files и т.д. | Доп. данные блока (если village привязан к block в api.trendagent.ru). |

Участки поселка на сайте могут подгружаться, например, через `GET /v1/search/plots?village_id={id}` или `GET /v1/villages/{villageId}/plots` — в коде проекта отдельный вызов для списка участков одного поселка не найден; при необходимости уточнить по сетевым запросам.

### Локальный прокси (проект AL)

| Метод | Локальный роут | Описание |
|-------|----------------|----------|
| POST | `/api/trendagent/plots/{id}` | PlotsController::show. Параметр `id` — **village** id или GUID (slug). Логика: по GUID получают block_id через getBlockById(guid); данные поселка — getVillageById(villageId); при наличии — getBlockFullData(villageId). Возвращаются данные **поселка** (unified + опции), а не отдельного участка. |

**Структура страницы:** шапка поселка (название, адрес, застройщик, галерея); блок «Участки» (таблица или карточки); карта; описание; файлы; контакты.

---

## 6.2 Страница участка (plot)

**URL:** `https://spb.trendagent.ru/village/{slug}/plot/{plotId}?open=village&sort=construction_sequence_number&sort_order=asc`

**Пример:** https://spb.trendagent.ru/village/lebyazhe/plot/692578d2a5e15b2a6c65fdbd/?open=village&sort=construction_sequence_number&sort_order=asc

**Роут фронта:** `/village/{slug}/plot/{plotId}`.  
**Query:** `open=village` (открыть контекст поселка), `sort=construction_sequence_number`, `sort_order=asc`.

### API для страницы участка

В коде проекта **отдельного запроса «детальная карточка участка по plotId» нет**. Ожидаемые варианты на стороне TrendAgent (уточнить по сетевым запросам):

| Домен | Возможный роут | Описание |
|-------|----------------|----------|
| **house-api.trendagent.ru** | `GET /v1/plots/{plotId}?auth_token=...&lang=ru` | Детальная информация по участку (предположительно). |
| **house-api.trendagent.ru** | `GET /v1/villages/{villageId}/plots/{plotId}` | Альтернативный вариант (при наличии). |
| Данные поселка | см. п. 6.1 | Для шапки и хлебных крошек (Посёлки → {поселок} → Участок). |

**Локальный прокси:** отдельного роута вида `/plots/{plotId}` для детальной карточки **участка** в проекте нет; `POST /api/trendagent/plots/{id}` отдаёт данные поселка (village), где `id` — id поселка.

**Структура страницы:** хлебные крошки (Посёлки → {название поселка} → Участок); характеристики участка (площадь, номер, цена, статус, план); кнопки действий.

---

# ЧАСТЬ VII. ПОДРЯДЧИКИ / ПРОЕКТЫ ДОМОВ (houseprojects)

**Базовый URL:** `https://spb.trendagent.ru/houseprojects`  
**Домен API:** **house-api.trendagent.ru**

## 7.1 Список проектов (houseprojects)

**URL:** `https://spb.trendagent.ru/houseprojects`

**Роут фронта:** `/houseprojects`.

### API (проверено по коду)

| Домен | Метод и путь | Параметры query (по умолчанию) | Описание |
|-------|--------------|--------------------------------|----------|
| **house-api.trendagent.ru** | `GET /v1/projects/search` | `auth_token`, `city=58c665588b6aa52311afa01b`, `lang=ru`, `sort_type=price`, `sort_order=asc`, `count=20`, `offset=0` | Список проектов домов (подрядчики). |

**Ответ:** `list[]` или `results[]`, `total_count`. Элемент: `_id`, `id`, `guid`, `name` (строка или объект с label/value), `price`/`prices[]`, `images[]`, `area_total`/`total_area`/`area`, `area_living`, `area_kitchen`, `area_terrace`, и др.; в коде обрабатываются изображения (selcdn.trendagent.ru), цены, название.

**Локальный прокси:** список подрядчиков получается через `POST /api/trendagent/objects/list` с `object_type=contractors` (TrendSsoController вызывает `getContractorsSearch`).

---

## 7.2 Страница проекта (houseproject) — вкладки

**URL:** `https://spb.trendagent.ru/houseproject/{slug}?prev_event_page=search_projects_listing_page`

**Пример:** https://spb.trendagent.ru/houseproject/dk177-kopiya?prev_event_page=search_projects_listing_page

**Роут фронта:** `/houseproject/{slug}`. Query: `prev_event_page=search_projects_listing_page`.

**Вкладки (переключатель на странице):**
- **Все параметры** — `button[name="tabs"]` с `value="all"`.
- **Только отличия** — `button[name="tabs"]` с `value="diff"`.

### API (проверено по коду)

| Домен | Метод и путь | Параметры | Описание |
|-------|--------------|-----------|----------|
| **house-api.trendagent.ru** | `GET /v1/projects/{projectId}` | `auth_token`, `lang=ru` | Детальная информация по проекту. **projectId** — ID (24 символа hex) или GUID (slug). Если передан GUID, в коде сначала вызывается `getContractorsSearch` (count=1000), в ответе ищется элемент с `guid === projectIdOrGuid`, берётся его `_id` и выполняется запрос `GET /v1/projects/{_id}`. |
| При 404 | Fallback | Данные из списка `getContractorsSearch` — ищется проект по guid/_id/id и возвращаются эти данные как «детальная» информация. |

Вкладка **«Только отличия»** использует те же данные с `GET /v1/projects/{projectId}`; фильтрация полей (показ только отличий от базового/сравниваемого) выполняется на фронте.

**Локальный прокси:** детальная информация по проекту в текущем проекте получается через общий механизм блоков/объектов (getBlockDetails, getBlockData и т.д.) с типом contractors; прямой роут вида `/trendagent/contractors/{id}` в `routes/trendagent.php` не объявлен — используется `objects/list` для списка и при необходимости вызов сервиса `getContractorProjectDetail(projectIdOrGuid)`.

**Структура страницы:** шапка (название проекта, изображения); переключатель «Все параметры» / «Только отличия»; блок параметров (таблица/список: площадь, этажи, материал, цена и т.д.); описание; контакты подрядчика.

---

## 7.3 Сводка по house-api.trendagent.ru

| Страница | Роут фронта | API (house-api) |
|----------|-------------|-----------------|
| Список проектов | `/houseprojects` | `GET /v1/projects/search` |
| Детальная страница проекта | `/houseproject/{slug}` | `GET /v1/projects/{projectId}` (projectId = id или guid, при guid — предварительный поиск в search) |
| Вкладка «Все параметры» | — | Данные из `GET /v1/projects/{projectId}` |
| Вкладка «Только отличия» | — | Те же данные; отбор отличий на клиенте |

---

# ЧАСТЬ VIII. СВОДНАЯ ТАБЛИЦА РОУТОВ И ДОМЕНОВ

| Раздел | Фронт-роут | Домен API | Основные endpoints |
|--------|------------|-----------|--------------------|
| Объект (ЖК) | `/object/{slug}/` | api.trendagent.ru | blocks/search/id, blocks/{id}/unified, apartments/block/{id}/search, media/block/{id}/plans |
| Квартира (flat) | `/object/{slug}/flat/{apartmentId}` | api.trendagent.ru | blocks/search/id, apartments/block/{blockId}/apartment/{apartmentId}/, apartments/{apartmentId}/, blocks/{id}/unified |
| Шахматка | `/object/{slug}/checkerboard` | api.trendagent.ru | checkerboards/{blockId}/apartments/buildings/, checkerboards/{blockId}/apartments/?building_id= |
| Посёлки список | `/villages/list` | house-api.trendagent.ru | GET /v1/search/villages |
| Участки список | `/villages/plots` | house-api.trendagent.ru | GET /v1/search/plots |
| Посёлки на карте | `/villages/map/` | house-api.trendagent.ru | те же villages/plots + геоданные |
| Поселок | `/village/{slug}` | house-api + api | getVillagesSearch + find by id; getBlockById(guid); getBlockFullData |
| Участок | `/village/{slug}/plot/{plotId}` | house-api.trendagent.ru | /v1/plots/{plotId} или /v1/villages/…/plots/… (уточнить) |
| Подрядчики список | `/houseprojects` | house-api.trendagent.ru | GET /v1/projects/search |
| Проект дома | `/houseproject/{slug}` | house-api.trendagent.ru | GET /v1/projects/{projectId} |

---

## 8.1 Полный перечень внешних API (все роуты, ничего не пропущено)

### api.trendagent.ru (v4_29)

| Метод | Путь | Параметры (общие: auth_token, city, lang) | Назначение |
|-------|------|-------------------------------------------|------------|
| GET | `/blocks/search/id/` | `guid` | block_id по slug |
| GET | `/blocks/{blockId}/unified/` | ch, formating | Данные объекта/поселка |
| GET | `/blocks/{blockId}/advantages` | — | Преимущества |
| GET | `/blocks/{blockId}/nearby_places` | — | Ближайшие места |
| GET | `/blocks/{blockId}/apartments/min-price/` | onrequest, reservation | Мин. цена квартир |
| GET | `/blocks/{blockId}/geo/buildings/` | — | Геоданные корпусов |
| GET | `/apartments/block/{blockId}/search/` | room, sort, sort_order, count | Квартиры объекта |
| GET | `/apartments/block/{blockId}/apartment/{apartmentId}/` | — | Детальная карточка квартиры |
| GET | `/apartments/{apartmentId}/` | — | Детальная квартира (fallback) |
| GET | `/checkerboards/{blockId}/apartments/buildings/` | room (многократно) | Корпуса для шахматки |
| GET | `/checkerboards/{blockId}/apartments/` | **building_id** | Квартиры по корпусу (шахматка) |
| GET | `/media/block/{blockId}/plans/` | cache, formating | Планировки |
| GET | `/media/block/{blockId}/progress/years` | — | Годы строительства |
| GET | `/media/block/{blockId}/progress/{year}` | — | Этапы по году |
| GET | `/finishings/block/{blockId}/` | — | Отделки |
| GET | `/unit_measurements` | — | Единицы измерения |
| GET | `/directories/rooms` | — | Типы комнат |
| GET | `/directories/finishing` | — | Типы отделки |
| GET | `/blocks/search/` | show_type, room, sort, sort_order, count | Список блоков (комплексы) |
| GET | `/apartments/search/` | room, sort, sort_order, count | Поиск квартир |
| GET | `/prelaunches/exists` | — | Анонсы |
| GET | `/notices`, `/tariffs` | — | Уведомления, тарифы |

### house-api.trendagent.ru (v1)

| Метод | Путь | Параметры (общие: auth_token, city, lang) | Назначение |
|-------|------|-------------------------------------------|------------|
| GET | `/search/villages` | sort_type, sort_order, count, offset | Список посёлков |
| GET | `/search/plots` | sort_type, sort_order, count, offset | Список участков |
| GET | `/projects/search` | sort_type, sort_order, count, offset | Список проектов домов (подрядчики) |
| GET | `/projects/{projectId}` | lang | Детальная информация по проекту (id или guid) |

### apartment-api.trendagent.ru

| Метод | Путь | Параметры | Назначение |
|-------|------|-----------|------------|
| GET | `/v1/directories` | types, city | Справочники (rooms, balcony_types, finishings и т.д.) |
| GET | `/v1/directories/blocks/{blockId}` | types | Справочники по объекту |
| GET | `/v1/directories/blocks/{blockId}/checkerboards` | types | Справочники для шахматки |

### Локальные роуты проекта (routes/trendagent.php, префикс /api/trendagent, middleware trendagent.auth)

| Метод | Роут | Контроллер/действие | Описание |
|-------|------|---------------------|----------|
| POST | `/authenticate` | TrendSsoController::authenticate | SSO авторизация |
| GET | `/cities` | TrendSsoController::getCities | Города |
| POST | `/objects/list` | TrendSsoController::getObjectsList | Универсальный список (object_type: apartments, parking, houses, plots, commercial, contractors) |
| POST | `/block/details` | TrendSsoController::getBlockDetails | Детали блока |
| POST | `/block/{dataType}` | TrendSsoController::getBlockData | Данные блока по типу |
| POST | `/apartments` | ApartmentsController::index | Список квартир |
| POST | `/apartments/{id}` | ApartmentsController::show | Детали объекта (квартиры) |
| POST | `/apartments/{id}/checkerboard/buildings` | ApartmentsController::checkerboardBuildings | Корпуса для шахматки |
| POST | `/apartments/{id}/checkerboard/apartments` | ApartmentsController::checkerboardApartments | Квартиры шахматки по building_id |
| POST | `/apartments/{id}/flat/{apartmentId}` | ApartmentsController::flatDetail | Детальная карточка квартиры |
| POST | `/parkings` | ParkingsController::index | Список паркингов |
| POST | `/parkings/{id}` | ParkingsController::show | Детали паркинга |
| POST | `/parkings/{id}/places` | ParkingsController::places | Места парковки |
| POST | `/houses` | HousesController::index | Список домов |
| POST | `/houses/{id}` | HousesController::show | Детали дома |
| POST | `/houses/{id}/checkerboard/buildings` | HousesController::checkerboardBuildings | Корпуса шахматки (дома) |
| POST | `/houses/{id}/checkerboard/apartments` | HousesController::checkerboardApartments | Квартиры шахматки (дома) |
| POST | `/plots` | PlotsController::index | Список (внутри — getVillagesSearch) |
| POST | `/plots/{id}` | PlotsController::show | Детали поселка/участка (id = village id или guid) |
| POST | `/commercial` | CommercialController::index | Список коммерции |
| POST | `/commercial/{id}` | CommercialController::show | Детали коммерции |

---

## 9. ПРИМЕЧАНИЯ

- **Villa Marina:** block_id `65c8b45523bccfa820bfaf73`, builder_id `648078b4ebb354e3a41bd69a`. Все роуты Части II применимы с подстановкой этого `blockId`.
- **Белая дача:** block_id `64db7ab977be523b31f3f533`.
- Коды комнат: `30` — 3-комн., `40` — 4-комн. (справочник `rooms`).
- Во всех запросах передаются: `auth_token`, `city=58c665588b6aa52311afa01b`, `lang=ru` (где применимо).
- **house-api.trendagent.ru** используется для разделов: villages (посёлки), plots (участки), houseprojects (подрядчики/проекты домов). Остальные объекты (ЖК, квартиры, шахматка) — **api.trendagent.ru** и **apartment-api.trendagent.ru**.

*Документ составлен по анализу сетевых запросов и кода проекта (TRENDAGENT_PAGE_STRUCTURE, TrendSsoApiAuth, routes). Обновлено: страницы object/flat, checkerboard, villages (list/plots/map), village, plot, houseprojects, houseproject (07.02.2026).*

