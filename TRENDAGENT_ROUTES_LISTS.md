# Роуты получения списков: Квартиры, Паркинги, Дома, Участки, Коммерция, Проекты домов

Полные пути с доменом и параметрами.

---

## 1. Внешние API TrendAgent (прямые запросы к trendagent.ru)

Во всех запросах обязателен **auth_token** (получается через SSO). **city** — ID города (MongoDB ObjectId), например СПб: `58c665588b6aa52311afa01b`.

### Квартиры (список)

- **Метод:** GET  
- **URL:**  
  `https://api.trendagent.ru/v4_29/apartments/search/`  
- **Параметры (query):**  
  `city`, `auth_token`, `sort`, `sort_order`, `count`, `offset`, `lang`, при необходимости `room` (массив: `room=30&room=40` и т.д.)

**Пример полного URL (список квартир, СПб, 20 шт):**  
`https://api.trendagent.ru/v4_29/apartments/search/?city=58c665588b6aa52311afa01b&auth_token=YOUR_TOKEN&sort=price&sort_order=asc&count=20&offset=0&lang=ru`

---

### Паркинги / машиноместа (список)

- **Метод:** GET  
- **URL:**  
  `https://parkings-api.trendagent.ru/search/places/`  
- **Параметры (query):**  
  `city`, `auth_token`, `sort`, `sort_order`, `count`, `offset`, `number`, `lang`

**Пример полного URL:**  
`https://parkings-api.trendagent.ru/search/places/?city=58c665588b6aa52311afa01b&auth_token=YOUR_TOKEN&sort=price&sort_order=asc&count=50&offset=0&number=&lang=ru`

---

### Дома (список)

- **Метод:** GET  
- **URL:**  
  `https://api.trendagent.ru/v4_29/blocks/search/`  
- **Параметры (query):**  
  `city`, `auth_token`, `show_type=list`, `sort`, `sort_order`, `count`, `offset`, `lang`, **`room=30&room=40`** (30 — коттеджи, 40 — таунхаусы)

**Пример полного URL:**  
`https://api.trendagent.ru/v4_29/blocks/search/?city=58c665588b6aa52311afa01b&auth_token=YOUR_TOKEN&show_type=list&sort=price&sort_order=asc&count=20&offset=0&lang=ru&room=30&room=40`

---

### Участки (список — через поиск поселков/участков)

- **Метод:** GET  
- **URL (поселки/villages):**  
  `https://house-api.trendagent.ru/v1/search/villages`  
- **Параметры (query):**  
  `city`, `auth_token`, `sort_type`, `sort_order`, `count`, `offset`, `lang`

**Пример полного URL (поселки):**  
`https://house-api.trendagent.ru/v1/search/villages?city=58c665588b6aa52311afa01b&auth_token=YOUR_TOKEN&sort_type=price&sort_order=asc&count=20&offset=0&lang=ru`

- **URL (участки/plots):**  
  `https://house-api.trendagent.ru/v1/search/plots`  
- **Параметры:** те же (`city`, `auth_token`, `sort_type`, `sort_order`, `count`, `offset`, `lang`)

**Пример полного URL (участки):**  
`https://house-api.trendagent.ru/v1/search/plots?city=58c665588b6aa52311afa01b&auth_token=YOUR_TOKEN&sort_type=price&sort_order=asc&count=20&offset=0&lang=ru`

---

### Коммерция / помещения (список)

- **Метод:** GET  
- **URL:**  
  `https://commerce-api.trendagent.ru/search/premises`  
- **Параметры (query):**  
  `city`, `auth_token`, `sort`, `sort_order`, `count`, `offset`, `number`, `lang`

**Пример полного URL:**  
`https://commerce-api.trendagent.ru/search/premises?city=58c665588b6aa52311afa01b&auth_token=YOUR_TOKEN&sort=price&sort_order=asc&count=50&offset=0&number=&lang=ru`

---

### Проекты домов / подрядчики (список)

- **Метод:** GET  
- **URL:**  
  `https://house-api.trendagent.ru/v1/projects/search`  
- **Параметры (query):**  
  `city`, `auth_token`, `sort_type`, `sort_order`, `count`, `offset`, `lang`

**Пример полного URL:**  
`https://house-api.trendagent.ru/v1/projects/search?city=58c665588b6aa52311afa01b&auth_token=YOUR_TOKEN&sort_type=price&sort_order=asc&count=20&offset=0&lang=ru`

---

## 2. Роуты проекта AL (прокси к TrendAgent)

Базовый путь: **`https://ВАШ_ДОМЕН/api/trendagent/v1`**  
Все маршруты ниже требуют авторизации (middleware `trendagent.auth`). Тело запроса — обычно JSON с параметрами (city, count, offset и т.д.).

| Тип | Метод | Полный путь | Параметры (body/query) |
|-----|--------|-------------|------------------------|
| **Квартиры** | POST | `https://ВАШ_ДОМЕН/api/trendagent/v1/apartments` | `city`, `count`, `offset`, `sort`, `sort_order`, при необходимости `room` |
| **Паркинги** | POST | `https://ВАШ_ДОМЕН/api/trendagent/v1/parkings` | `city`, `count`, `offset` |
| **Дома** | POST | `https://ВАШ_ДОМЕН/api/trendagent/v1/houses` | `city`, `count`, `offset` |
| **Участки** | POST | `https://ВАШ_ДОМЕН/api/trendagent/v1/plots` | `city`, `count`, `offset` |
| **Коммерция** | POST | `https://ВАШ_ДОМЕН/api/trendagent/v1/commercial` | `city`, `count`, `offset` |
| **Проекты домов** | POST | `https://ВАШ_ДОМЕН/api/trendagent/v1/houseprojects` | `city`, `count`, `offset` |

Дополнительно в проекте:

- **Авторизация:**  
  `POST https://ВАШ_ДОМЕН/api/trendagent/v1/authenticate`  
  (тело: phone, password и т.д. по вашему контракту)
- **Города:**  
  `GET https://ВАШ_ДОМЕН/api/trendagent/v1/cities`
- **Универсальный список объектов:**  
  `POST https://ВАШ_ДОМЕН/api/trendagent/v1/objects/list`  
  (параметр `object_type` и др. по контракту)

---

## 3. Идентификаторы городов (city)

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

Остальные города — в `CityService::getAllCities()` (или из ответа `GET /api/trendagent/v1/cities`).
