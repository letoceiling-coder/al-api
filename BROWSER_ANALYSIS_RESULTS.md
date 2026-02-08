# Результаты ручного анализа эндпоинтов TrendAgent (браузер)

**Дата:** 2026-02-08  
**Источник:** выполнение BROWSER_MANUAL_ANALYSIS_STEPS.md в браузере (авторизация + переход по страницам, сбор Network).

---

## 1. Детальная информация о квартире

**Проблема:** старый эндпоинт возвращал 500.

**Рабочий эндпоинт:**

- **URL:** `https://api.trendagent.ru/v4_29/apartments/{apartmentId}/unified/`
- **Method:** GET
- **Query Parameters:**
  - `auth_token` — JWT (обязателен при авторизации)
  - `city=58c665588b6aa52311afa01b`
  - `lang=ru`

**Пример:**  
`GET https://api.trendagent.ru/v4_29/apartments/63c5614728d3bcf2420860b1/unified/?auth_token=...&city=58c665588b6aa52311afa01b&lang=ru`

**Дополнительно для страницы квартиры:**
- Планировки по блоку: `GET https://api.trendagent.ru/v4_29/apartments/floor_plan/directory/{blockId}?auth_token=...&city=...&lang=ru`
- Планировка этажа: `GET https://api.trendagent.ru/v4_29/apartments/floor_plan?building_id=...&floor_number=...&section_id=...&auth_token=...&city=...&lang=ru`
- Цены: `.../prices/apartment/{apartmentId}/graph`, `.../totals`
- Отделки: `.../finishings/apartment/{apartmentId}/`

**Статус:** ✅ Работает (использовать `/unified/`, не старый эндпоинт без unified).

---

## 2. Планировки квартир (checkerboard)

**Проблема:** эндпоинт checkerboard возвращал 404.

**Рабочая цепочка:**

1. **Получить block_id по slug (guid) объекта:**  
   `GET https://api.trendagent.ru/v4_29/blocks/search/id/?guid={slug}&auth_token=...&city=...&lang=ru`  
   Пример: `guid=dom-na-naberezhnoy-st` → в ответе id блока (например `63c50acc9a85d53360f63a76`).

2. **Список корпусов (buildings) шахматки:**  
   `GET https://api.trendagent.ru/v4_29/checkerboards/{blockId}/apartments/buildings/?auth_token=...&city=...&lang=ru`

3. **Квартиры по корпусу:**  
   `GET https://api.trendagent.ru/v4_29/checkerboards/{blockId}/apartments/?building_id={buildingId}&auth_token=...&city=...&lang=ru`

**Справочники для шахматки (apartment-api):**  
`GET https://apartment-api.trendagent.ru/v1/directories/blocks/{blockId}/checkerboards?types=rooms&types=balcony_types&...&city=...&lang=ru`

**Статус:** ✅ Работает (использовать `checkerboards/{blockId}/apartments/buildings/` и `checkerboards/{blockId}/apartments/?building_id=...`).

---

## 3. Детальная информация о доме (квартира/коттедж в ЖК)

Страница «дома» (например коттеджа в ЖК) использует тот же тип объекта, что и квартира.

**Эндпоинт:** тот же, что и для квартиры —  
`GET https://api.trendagent.ru/v4_29/apartments/{id}/unified/?auth_token=...&city=...&lang=ru`

**Статус:** ✅ Работает (тот же `apartments/.../unified/`).

---

## 4. Детальная информация о поселке

**Проблема:** эндпоинт по поселку возвращал 404.

**Рабочая цепочка:**

1. **Получить id поселка по slug (guid):**  
   `GET https://house-api.trendagent.ru/v1/villages/id?guid={slug}&city=...&lang=ru`  
   Пример: `guid=lebyazhe` → в ответе id поселка (например `691f1e08a5e15b2a6c6218b2`).

2. **Детальная информация (unified):**  
   `GET https://house-api.trendagent.ru/v1/villages/{villageId}/unified?city=...&lang=ru`

**Дополнительно:**
- Меню: `.../villages/{id}/menu?city=...&lang=ru`
- Контакты: `.../villages/{id}/contacts?city=...&lang=ru`
- Теги: `.../villages/{id}/tags?city=...&lang=ru`
- Вознаграждения: `.../reward-settings?village={id}&...`, `.../rewards/villages/{id}?...`

**Статус:** ✅ Работает (house-api, `villages/id?guid=...` и `villages/{id}/unified`).

---

## 5. Детальная информация о помещении коммерции

**Проблема:** эндпоинт возвращал 404 (возможно использовался путь типа `/premises/`).

**Рабочий эндпоинт:**

- **URL:** `https://commerce-api.trendagent.ru/commerce/{premiseId}/unified/`
- **Method:** GET
- **Query Parameters:**
  - `city=58c665588b6aa52311afa01b`
  - `lang=ru`
- В запросах браузера `auth_token` в URL для этого запроса не передавался (может передаваться в заголовках при необходимости).

**Пример:**  
`GET https://commerce-api.trendagent.ru/commerce/66d02665d5fa3023a711487c/unified/?city=58c665588b6aa52311afa01b&lang=ru`

**Дополнительно:**
- Планировки: `GET https://commerce-api.trendagent.ru/floor_plan/directory/{blockId}?city=...&lang=ru`
- Планировка этажа: `GET https://commerce-api.trendagent.ru/floor_plan/commercial?building_id=...&floor=...&city=...&lang=ru`

**Статус:** ✅ Работает (путь `/commerce/{id}/unified/`, не `/premises/`).

---

## 6. Детальная информация о проекте дома

**Проблема:** эндпоинт возвращал 404 (по slug или неверный путь).

**Рабочая цепочка:**

1. **Получить id проекта по slug (guid):**  
   `GET https://house-api.trendagent.ru/v1/projects/id?guid={slug}&city=...&lang=ru`  
   Пример: `guid=dk177-kopiya` → в ответе id проекта (например `683db476e92855d2ef1568ce`).

2. **Детальная информация (unified):**  
   `GET https://house-api.trendagent.ru/v1/projects/{projectId}/unified?city=...&lang=ru`

**Дополнительно:**
- Конфигурации: `.../projects/{id}/configurations?city=...&lang=ru`
- Отделки: `.../projects/{id}/finishing-items?city=...&lang=ru`
- Доп. услуги: `.../projects/{id}/additional-services?city=...&lang=ru`
- Видео: `.../projects/{id}/videos?city=...&lang=ru`
- Вознаграждения: `.../reward-settings?project={id}&...`, `.../rewards/projects/{id}?...`

**Статус:** ✅ Работает (house-api, `projects/id?guid=...` и `projects/{id}/unified`).

---

## Сводная таблица

| Сущность           | Домен/API              | Получение id (если по slug) | Деталь (unified)                          |
|-------------------|------------------------|-----------------------------|-------------------------------------------|
| Квартира          | api.trendagent.ru v4_29 | — (id в URL страницы)       | `GET /apartments/{id}/unified/`            |
| Шахматка          | api.trendagent.ru v4_29 | `GET /blocks/search/id/?guid={slug}` | `/checkerboards/{blockId}/apartments/buildings/`, `/checkerboards/{blockId}/apartments/?building_id=...` |
| Дом/коттедж в ЖК  | api.trendagent.ru v4_29 | —                           | `GET /apartments/{id}/unified/`            |
| Посёлок           | house-api.trendagent.ru | `GET /v1/villages/id?guid={slug}` | `GET /v1/villages/{id}/unified`            |
| Коммерция         | commerce-api.trendagent.ru | — (id в URL)            | `GET /commerce/{id}/unified/`              |
| Проект дома       | house-api.trendagent.ru | `GET /v1/projects/id?guid={slug}` | `GET /v1/projects/{id}/unified`            |

---

## Общие параметры

- **city:** `58c665588b6aa52311afa01b` (СПб в примерах).
- **lang:** `ru`.
- **auth_token:** JWT в query или заголовке Authorization для api.trendagent.ru (и при необходимости других API).

После применения этих эндпоинтов в коде парсера ошибки 404/500 по перечисленным сущностям должны устраняться.
