# Обращение к API TrendAgent — полные пути, параметры и назначение

Документ описывает **внешние** эндпоинты TrendAgent (домены `*.trendagent.ru`), к которым обращается проект AL. Во всех запросах передаётся **auth_token** (получается через SSO по телефону и паролю). Метод запросов — **GET**, параметры — в query string.

Общие параметры для большинства эндпоинтов: **auth_token**, **city** (ID города, например СПб: `58c665588b6aa52311afa01b`), **lang** (обычно `ru`).

---

## 1. api.trendagent.ru (квартиры, ЖК, дома)

Базовый домен: **https://api.trendagent.ru**

### 1.1 Поиск блоков (ЖК / квартиры / дома)

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Список ЖК (квартиры), список домов с фильтром | `GET https://api.trendagent.ru/v4_29/blocks/search/` | `auth_token`, `city`, `lang`, `show_type=list`, `sort`, `sort_order`, `count`, `offset`, при необходимости `room` (массив: 30=коттеджи, 40=таунхаусы для домов) |

**Пояснение:** Один и тот же эндпоинт для квартир (без room) и для домов (room=30&room=40). Возвращает блоки с полями результатов и изображениями (URL достраиваются до selcdn.trendagent.ru).

---

### 1.2 Поиск блока по ID / GUID

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Найти блок по ID или GUID (в т.ч. поселок) | `GET https://api.trendagent.ru/v4_29/blocks/search/id/` | `auth_token`, `city`, `lang`, `guid` (ID блока 24 hex или slug) |

**Пояснение:** Используется для получения блока по известному идентификатору (в т.ч. для поселков).

---

### 1.3 Детали блока (unified)

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Полная сводная информация по ЖК/блоку | `GET https://api.trendagent.ru/v4_29/blocks/{blockId}/unified/` | `auth_token`, `city`, `lang`, `ch`, `formating` |

**Пояснение:** Единый объект с основными данными блока, минимальными ценами, количеством участков и т.д.

---

### 1.4 Здания блока на карте

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Список зданий/корпусов блока для карты | `GET https://api.trendagent.ru/v4_29/blocks/{blockId}/geo/buildings/` | `auth_token`, `city`, `lang` |

---

### 1.5 Квартиры блока

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Поиск квартир в рамках одного блока | `GET https://api.trendagent.ru/v4_29/apartments/block/{blockId}/search/` | `auth_token`, `city`, `lang`, при необходимости фильтры (onrequest, room, price и т.д.) |

**Пояснение:** Результаты могут быть сгруппированы по типам (например «1#4 кв. 2024»); внутри групп — массивы квартир.

---

### 1.6 Шахматка — корпуса

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Список корпусов для шахматки квартир | `GET https://api.trendagent.ru/v4_29/checkerboards/{blockId}/apartments/buildings/` | `auth_token`, `city`, `lang`, при необходимости `room`, `onrequest` |

---

### 1.7 Шахматка — квартиры по корпусу

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Квартиры по этажам/секциям для выбранного корпуса | `GET https://api.trendagent.ru/v4_29/checkerboards/{blockId}/apartments/` | `auth_token`, `city`, `lang`, `building_id` |

---

### 1.8 Детали квартиры

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Детальная информация по квартире (с указанием блока) | `GET https://api.trendagent.ru/v4_29/apartments/block/{blockId}/apartment/{apartmentId}/` | `auth_token`, `city`, `lang` |
| Детальная информация по квартире (только ID квартиры) | `GET https://api.trendagent.ru/v4_29/apartments/{apartmentId}/` | `auth_token`, `city`, `lang` |

**Пояснение:** Сначала используется вариант с blockId; при ошибке — fallback на вариант только с apartmentId.

---

### 1.9 Поиск квартир (общий каталог)

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Общий поиск квартир по городу и фильтрам | `GET https://api.trendagent.ru/v4_29/apartments/search/` | `auth_token`, `city`, `lang`, `sort`, `sort_order`, `count`, `offset`, при необходимости `room` (массив) |

---

### 1.10 Поэтажный план — справочник

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Справочник корпусов/секций/этажей для поэтажного плана | `GET https://api.trendagent.ru/v4_29/apartments/floor_plan/directory/{blockId}` | `auth_token`, `city`, `lang` |

---

### 1.11 Поэтажный план — данные этажа

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Данные для отрисовки плана этажа | `GET https://api.trendagent.ru/v4_29/apartments/floor_plan` | `auth_token`, `city`, `lang`, `building_id`, `section_id`, `floor_number` |

---

### 1.12 Медиа и доп. данные блока

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Планировки блока | `GET https://api.trendagent.ru/v4_29/media/block/{blockId}/plans/` | `auth_token`, `city`, `lang`, `cache`, `formating` |
| Список лет хода строительства | `GET https://api.trendagent.ru/v4_29/media/block/{blockId}/progress/years/` | `auth_token`, `city`, `lang` |
| Ход строительства за год | `GET https://api.trendagent.ru/v4_29/media/block/{blockId}/progress/{year}/` | `auth_token`, `city`, `lang` |
| Отделки по блоку | `GET https://api.trendagent.ru/v4_29/finishings/block/{blockId}/` | `auth_token`, `city`, `lang` |
| Преимущества блока | `GET https://api.trendagent.ru/v4_29/blocks/{blockId}/advantages/` | `auth_token`, `city`, `lang` |
| Ближайшие места | `GET https://api.trendagent.ru/v4_29/blocks/{blockId}/nearby_places/` | `auth_token`, `city`, `lang` |
| Минимальные цены по квартирам | `GET https://api.trendagent.ru/v4_29/blocks/{blockId}/apartments/min-price/` | `auth_token`, `city`, `lang` |
| Банки/ипотека по блоку | `GET https://api.trendagent.ru/v4_29/blocks/{blockId}/bank/` | `auth_token`, `city`, `lang` |

---

## 2. parkings-api.trendagent.ru и parkings.trendagent.ru

### 2.1 Список блоков паркингов

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Список паркингов (блоков машиномест) | `GET https://parkings-api.trendagent.ru/search/blocks` | `auth_token`, `city`, `lang`, `sort`, `sort_order`, `count`, `offset` |

**Пояснение:** Аналог списка ЖК, но для паркингов; в ответе — блоки с изображениями и минимальными ценами.

---

### 2.2 Список машиномест (общий)

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Поиск машиномест по городу | `GET https://parkings-api.trendagent.ru/search/places/` | `auth_token`, `city`, `lang`, `sort`, `sort_order`, `count`, `offset`, `number` |

---

### 2.3 Паркинги по блоку (ЖК)

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Места парковки, привязанные к ЖК | `GET https://parkings.trendagent.ru/parkings/block/{blockId}` | `auth_token`, `city`, `lang` |

**Пояснение:** Используется для получения паркингов конкретного жилого комплекса.

---

## 3. house-api.trendagent.ru (участки, поселки, проекты домов)

Базовый домен: **https://house-api.trendagent.ru**

### 3.1 Участки (поиск)

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Список участков | `GET https://house-api.trendagent.ru/v1/search/plots` | `auth_token`, `city`, `lang`, `sort_type`, `sort_order`, `count`, `offset` |

---

### 3.2 Поселки (поиск)

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Список поселков (деревень с участками) | `GET https://house-api.trendagent.ru/v1/search/villages` | `auth_token`, `city`, `lang`, `sort_type`, `sort_order`, `count`, `offset` |

---

### 3.3 Детали участка

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Детальная информация по одному участку | `GET https://house-api.trendagent.ru/v1/plots/{plotId}` | `auth_token`, `lang`, при необходимости `city` |

**Пояснение:** plotId — ID участка. При 404 в коде используется fallback — поиск участка в списке из search/plots.

---

### 3.4 Проекты домов / подрядчики (поиск)

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Список проектов домов (подрядчиков) | `GET https://house-api.trendagent.ru/v1/projects/search` | `auth_token`, `city`, `lang`, `sort_type`, `sort_order`, `count`, `offset` |

---

### 3.5 Детали проекта дома

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Детальная информация по проекту дома | `GET https://house-api.trendagent.ru/v1/projects/{projectId}` | `auth_token`, `city`, `lang` |

**Пояснение:** projectId — ID проекта (подрядчика). При 404 возможен fallback на поиск в списке projects/search.

---

## 4. commerce.trendagent.ru и commerce-api.trendagent.ru

### 4.1 Список блоков коммерции

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Список коммерческих комплексов/блоков | `GET https://commerce.trendagent.ru/search/blocks` | `auth_token`, `city`, `lang`, `sort`, `sort_order`, `count`, `offset` |

**Пояснение:** Аналог списка ЖК для коммерческой недвижимости.

---

### 4.2 Список коммерческих помещений

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Поиск коммерческих помещений | `GET https://commerce-api.trendagent.ru/search/premises` | `auth_token`, `city`, `lang`, `sort`, `sort_order`, `count`, `offset`, `number` |

---

## 5. Вспомогательные домены TrendAgent

### 5.1 Изображения (CDN)

| Назначение | Полный путь | Параметры |
|------------|-------------|-----------|
| Миниатюра изображения | `GET https://selcdn.trendagent.ru/images/{path}/m_{file_name}` | path и file_name из ответов API |
| Полноразмерное изображение | `GET https://selcdn.trendagent.ru/images/{path}/{file_name}` | path и file_name из ответов API |

**Пояснение:** В ответах API приходят `path` и `file_name`; полные URL собираются в коде по этим шаблонам.

---

### 5.2 Дополнительные сервисы по blockId

| Назначение | Полный путь | Параметры (query) |
|------------|-------------|-------------------|
| Видео по блоку | `GET https://video.trendagent.ru/videos/block/{blockId}` | по контракту API |
| Файлы по блоку | `GET https://files.trendagent.ru/fs/list/block/{blockId}` | по контракту API |
| Настройки вознаграждений | `GET https://rewards-api.trendagent.ru/builder-reward-settings` | `auth_token`, `block`, при необходимости `builder` |
| Скидки по блоку | `GET https://discounts.trendagent.ru/blocks/{blockId}/discounts` | `auth_token`, при необходимости `builder` |
| Ипотека по блоку | `GET https://mortgage-api.trendagent.ru/blocks/{blockId}/` | по контракту API |
| Рассрочка | `GET https://tiny-installments-api.trendagent.ru/v1/blocks/{blockId}` | по контракту API |
| Контакты по блоку | `GET https://contacts-api.trendagent.ru/contacts/blocks/{blockId}` | по контракту API |
| 3D-тур по блоку | `GET https://3d-tour-api.trendagent.ru/v1/blocks/{blockId}` | по контракту API |

---

## 6. Идентификаторы городов (city)

| Город | ID (city) |
|-------|-----------|
| Санкт-Петербург | `58c665588b6aa52311afa01b` |
| Москва | `5a5cb42159042faa9a218d04` |
| Краснодарский край / Сочи | `604b5243f9760700074ac345` |
| Ростов-на-Дону | `61926fb5bb267a0008de132b` |
| Крым | `682700dd0e7daf77097d0779` |
| Казань | `642157fca50429d21e3aa14f` |
| Уфа | `674eff862307c824cf56ced3` |
| Екатеринбург | `650974f78d34c0f790a012a9` |
| Новосибирск | `618120c1a56997000866c4d8` |

Остальные города — через сервис городов в проекте или ответ `GET /api/trendagent/v1/cities`.

---

## 7. Краткая сводка по назначению

- **Списки:** blocks/search (ЖК/дома), parkings-api/search/blocks, parkings-api/search/places, house-api search/plots, search/villages, search/projects, commerce/search/blocks, commerce-api/search/premises, apartments/search.
- **Детали блока/объекта:** blocks/search/id, blocks/{id}/unified, parkings/block/{id}, house-api plots/{id}, projects/{id}, apartments/block/{blockId}/apartment/{apartmentId}, apartments/{apartmentId}.
- **По блоку:** geo/buildings, apartments/block/{id}/search, checkerboards buildings/apartments, floor_plan directory и floor_plan, media/plans, media/progress, finishings, advantages, nearby_places, min-price, bank.
- **Картинки:** selcdn.trendagent.ru/images/… по path и file_name из ответов.

Все перечисленные пути используются в `TrendSsoApiAuth` и связанных сервисах проекта AL при парсинге и отдаче данных.
