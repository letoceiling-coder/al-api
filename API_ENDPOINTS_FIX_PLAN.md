# 🔧 План исправления API эндпоинтов

## Проблема

Данные в таблице неправильные из-за использования неверных эндпоинтов:

| Тип | Показывается | Должно быть | Эндпоинт |
|-----|--------------|-------------|----------|
| Комплексы | 344 | 352 | `/v4_29/blocks/search/` ✅ |
| Квартиры | 55,507 | 55,551 | `/v4_29/apartments/search/` ❌ |
| Паркинги | 40 | 3,644 машиноместа в 50 ЖК | `parkings-api.trendagent.ru/search/places` ❌ |
| Дома | 344 | 1,023 | `/v4_29/apartments/search/?room=30&room=40` ❌ |
| Участки | 344 | 2,370 участков в 68 поселках | `house-api.trendagent.ru/v1/search/villages` ❌ |
| Коммерция | 168 | 1,775 помещений в 168 ЖК | `commerce-api.trendagent.ru/search/premises` ❌ |
| Подрядчики | 0 | 149 проектов | `house-api.trendagent.ru/v1/projects/search` ❌ |

## Решение

### 1. Квартиры - использовать `/v4_29/apartments/search/`

**Текущий метод:** `getApartments()` использует `getBlocksSearch()`

**Нужно:** Создать метод `getApartmentsSearch()` в `TrendSsoApiAuth.php` и использовать его

**Эндпоинт:** `https://api.trendagent.ru/v4_29/apartments/search/`

**Параметры:**
- `sort=price`
- `sort_order=asc`
- `count=50`
- `offset=0`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`
- `auth_token=...`

### 2. Паркинги - использовать `parkings-api.trendagent.ru`

**Текущий метод:** `getParkings()` использует `getBlocksSearch()`

**Нужно:** 
- Для ЖК: `parkings-api.trendagent.ru/search/blocks`
- Для машиномест: `parkings-api.trendagent.ru/search/places`

**Эндпоинты:**
- ЖК: `https://parkings-api.trendagent.ru/search/blocks?count=20&offset=0&sort=price&sort_order=asc&city=58c665588b6aa52311afa01b&lang=ru`
- Машиноместа: `https://parkings-api.trendagent.ru/search/places/?count=50&number=&offset=0&sort=price&sort_order=asc&city=58c665588b6aa52311afa01b&lang=ru`

### 3. Дома - использовать `/v4_29/apartments/search/` с фильтром room

**Текущий метод:** `getHouses()` использует `getBlocksSearch()` с `object_type=house`

**Нужно:** Использовать `/v4_29/apartments/search/` с `room=30&room=40`

**Эндпоинт:** `https://api.trendagent.ru/v4_29/apartments/search/?sort=price&sort_order=asc&count=50&room=30&room=40&city=58c665588b6aa52311afa01b&lang=ru&auth_token=...`

### 4. Участки - использовать `house-api.trendagent.ru/v1/search/villages`

**Текущий метод:** `getPlots()` использует `getBlocksSearch()` с `object_type=land_plot`

**Нужно:** Использовать `house-api.trendagent.ru/v1/search/villages`

**Эндпоинт:** `https://house-api.trendagent.ru/v1/search/villages?count=20&offset=0&sort_order=asc&sort_type=price&city=58c665588b6aa52311afa01b&lang=ru`

### 5. Коммерция - использовать `commerce-api.trendagent.ru/search/premises`

**Текущий метод:** `getCommercial()` использует `getCommercialSearch()`

**Нужно:** Использовать `commerce-api.trendagent.ru/search/premises` для помещений

**Эндпоинт:** `https://commerce-api.trendagent.ru/search/premises?count=50&number=&offset=0&sort=price&sort_order=asc&city=58c665588b6aa52311afa01b&lang=ru`

### 6. Подрядчики - использовать `house-api.trendagent.ru/v1/projects/search`

**Текущий метод:** `getContractors()` использует `getContractorsSearch()` ✅

**Нужно:** Проверить, что метод правильно извлекает total

**Эндпоинт:** `https://house-api.trendagent.ru/v1/projects/search?count=20&offset=0&sort_order=asc&sort_type=price&city=58c665588b6aa52311afa01b&lang=ru`

## План реализации

1. ✅ Создать метод `getApartmentsSearch()` в `TrendSsoApiAuth.php`
2. ✅ Обновить `getParkings()` для использования правильных эндпоинтов
3. ✅ Обновить `getHouses()` для использования `/v4_29/apartments/search/` с room
4. ✅ Обновить `getPlots()` для использования `house-api.trendagent.ru/v1/search/villages`
5. ✅ Обновить `getCommercial()` для использования `commerce-api.trendagent.ru/search/premises`
6. ✅ Проверить `getContractors()` и исправить извлечение total
7. ✅ Обновить `TrendAgentApiClient` для использования новых методов
8. ✅ Обновить парсер для правильного извлечения total из ответов
