# 📊 СВОДКА ВСЕХ API ЭНДПОИНТОВ TrendAgent

**Дата:** 2026-02-08  
**Статус:** ✅ API анализ завершен, требуется браузерный анализ

---

## 🎯 ДВА СПОСОБА ПОЛУЧЕНИЯ ДАННЫХ

### Способ 1: Прямые API запросы ✅
- `api.trendagent.ru/v4_29/...`
- `parkings-api.trendagent.ru/...`
- `commerce-api.trendagent.ru/...`
- `house-api.trendagent.ru/...`

### Способ 2: Через прокси/обертку ⚠️
- `api.siteaccess.ru/trendagent/...` (возвращает HTML, не JSON)
- **Требует браузерного анализа для поиска правильного эндпоинта**

---

## 1️⃣ КВАРТИРЫ (55,548 шт, 352 ЖК)

### Способ 1: Прямые API

#### 1.1. Список квартир
```
GET https://api.trendagent.ru/v4_29/apartments/search/
```
**Параметры:**
- `sort=price`
- `sort_order=asc`
- `count=50`
- `offset=0, 50, 100, ...` (пагинация)
- `auth_token=<TOKEN>`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`

**Структура ответа:**
```json
{
  "errors": null,
  "data": {
    "apartmentsCount": 55548,
    "blocksCount": 344,
    "bookedApartmentsCount": 2508,
    "list": [...]
  }
}
```

**Пагинация:** 55,548 / 50 = **1,111 страниц**

#### 1.2. Планировки квартир
```
GET https://api.trendagent.ru/v4_29/apartments/search/
```
**Параметры:** Те же, что и для списка
**Особенность:** В ответе уже включено поле `plan` с планировкой

#### 1.3. Список комплексов (ЖК)
```
GET https://api.trendagent.ru/v4_29/blocks/search/
```
**Параметры:**
- `show_type=list`
- `sort=price`
- `sort_order=asc`
- `count=20`
- `offset=0, 20, 40, ...`
- `auth_token=<TOKEN>`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`

**Пагинация:** 344 / 20 = **18 страниц**

#### 1.4. Карта комплексов
```
GET https://api.trendagent.ru/v4_29/blocks/search/
```
**Параметры:**
- `show_type=map`
- `auth_token=<TOKEN>`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`

#### 1.5. Информация по клику на карте
```
GET https://api.trendagent.ru/v4_29/blocks/{BLOCK_ID}/map/
```
**Параметры:**
- `formating=true`
- `auth_token=<TOKEN>`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`

### Способ 2: Через прокси ⚠️
**Требует браузерного анализа!**

**Попытка:**
- `https://api.siteaccess.ru/trendagent/apartments` - возвращает HTML (фронтенд)
- **Нужно найти правильный JSON эндпоинт в браузере!**

---

## 2️⃣ ПАРКИНГИ (3,644 машиноместа, 50 ЖК)

### Способ 1: Прямые API

#### 2.1. Список машиномест
```
GET https://parkings-api.trendagent.ru/search/places/
```
**Параметры:**
- `count=50`
- `number=` (пустой)
- `offset=0, 50, 100, ...`
- `sort=price`
- `sort_order=asc`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`
- `auth_token=<TOKEN>` ⚠️ **ОБЯЗАТЕЛЬНО**

**Структура ответа:**
```json
{
  "placesCount": 3644,
  "blocksCount": 50,
  "bookedPlacesCount": 572,
  "results": [...]
}
```

**Пагинация:** 3,644 / 50 = **73 страницы**

#### 2.2. Список ЖК с паркингами
```
GET https://parkings-api.trendagent.ru/search/blocks
```
**Параметры:**
- `count=20`
- `offset=0, 20, 40`
- `sort=price`
- `sort_order=asc`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`
- `auth_token=<TOKEN>` ⚠️ **ОБЯЗАТЕЛЬНО**

**Пагинация:** 50 / 20 = **3 страницы**

#### 2.3. Карта паркингов
```
GET https://parkings-api.trendagent.ru/search/pins
```
**Параметры:**
- `city=58c665588b6aa52311afa01b`
- `lang=ru`
- `auth_token=<TOKEN>` ⚠️ **ОБЯЗАТЕЛЬНО**

#### 2.4. Информация по клику на карте
```
GET https://parkings-api.trendagent.ru/search/map/block/{BLOCK_ID}
```
**Параметры:**
- `city=58c665588b6aa52311afa01b`
- `lang=ru`
- `auth_token=<TOKEN>` ⚠️ **ОБЯЗАТЕЛЬНО**

### Способ 2: ⚠️ Требует браузерного анализа

---

## 3️⃣ ДОМА (1,023 шт, 60 комплексов)

### Способ 1: Прямые API

#### 3.1. Список домов
```
GET https://api.trendagent.ru/v4_29/apartments/search/
```
**Параметры:**
- `sort=price`
- `sort_order=asc`
- `count=50`
- `room=30&room=40` (30=Коттеджи, 40=Таунхаусы)
- `offset=0, 50, 100, ...`
- `auth_token=<TOKEN>`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`

**Структура ответа:**
```json
{
  "errors": null,
  "data": {
    "apartmentsCount": 1023,
    "blocksCount": 60,
    "list": [...]
  }
}
```

**Пагинация:** 1,023 / 50 = **21 страница**

#### 3.2. Комплексы домов
```
GET https://api.trendagent.ru/v4_29/blocks/search/
```
**Параметры:**
- `show_type=list`
- `sort=price`
- `sort_order=asc`
- `count=20`
- `room=30&room=40`
- `offset=0, 20, 40`
- `auth_token=<TOKEN>`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`

#### 3.3. Планировки домов
```
GET https://api.trendagent.ru/v4_29/apartments/search/
```
**Параметры:**
- `sort=price`
- `sort_order=asc`
- `count=30`
- `room=30&room=40`
- `offset=0, 30, 60`
- `auth_token=<TOKEN>`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`

**Особенность:** В ответе включено поле `plan` с планировкой

### Способ 2: ⚠️ Требует браузерного анализа

---

## 4️⃣ УЧАСТКИ (2,370 шт, 68 поселков)

### Способ 1: Прямые API

#### 4.1. Список участков
```
GET https://house-api.trendagent.ru/v1/search/villages
```
**Параметры:**
- `count=1` (или больше)
- `offset=0, 1, 2, ...`
- `sort_order=asc`
- `sort_type=price`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`
- `auth_token=<TOKEN>` ⚠️ **ОБЯЗАТЕЛЬНО**

#### 4.2. Список поселков
```
GET https://house-api.trendagent.ru/v1/search/villages
```
**Параметры:**
- `count=20`
- `offset=0, 20, 40`
- `sort_order=asc`
- `sort_type=price`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`
- `auth_token=<TOKEN>` ⚠️ **ОБЯЗАТЕЛЬНО**

**Структура ответа:**
```json
{
  "total_count": 68,
  "result_count": 20,
  "plots_count": 2370,
  "list": [...]
}
```

#### 4.3. Карта поселков
```
GET https://house-api.trendagent.ru/v1/search/map/villages/pins
```
**Параметры:**
- `city=58c665588b6aa52311afa01b`
- `lang=ru`
- `auth_token=<TOKEN>` ⚠️ **ОБЯЗАТЕЛЬНО**

#### 4.4. Информация по клику на карте
```
GET https://house-api.trendagent.ru/v1/search/map/villages/{VILLAGE_ID}
```
**Параметры:**
- `city=58c665588b6aa52311afa01b`
- `lang=ru`
- `auth_token=<TOKEN>` ⚠️ **ОБЯЗАТЕЛЬНО**

### Способ 2: ⚠️ Требует браузерного анализа

---

## 5️⃣ КОММЕРЦИЯ (1,775 помещений, 168 ЖК)

### Способ 1: Прямые API

#### 5.1. Список помещений
```
GET https://commerce-api.trendagent.ru/search/premises
```
**Параметры:**
- `count=50`
- `number=` (пустой)
- `offset=0, 50, 100, ...`
- `sort=price`
- `sort_order=asc`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`

**Структура ответа:**
```json
{
  "premises_count": 1775,
  "blocks_count": 168,
  "booked_premises_count": 150,
  "result": [...]
}
```

**Пагинация:** 1,775 / 50 = **36 страниц**

#### 5.2. Список комплексов коммерции
```
GET https://commerce-api.trendagent.ru/search/blocks
```
**Параметры:**
- `count=20`
- `offset=0, 20, 40`
- `sort=price`
- `sort_order=asc`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`

**Пагинация:** 168 / 20 = **9 страниц**

#### 5.3. Планировки коммерции
```
GET https://commerce-api.trendagent.ru/search/premisesPlan
```
**Параметры:**
- `count=15`
- `number=` (пустой)
- `offset=0, 15, 30`
- `sort=price`
- `sort_order=asc`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`
- `auth_token=<TOKEN>` ⚠️ **ОБЯЗАТЕЛЬНО**

### Способ 2: ⚠️ Требует браузерного анализа

---

## 6️⃣ ПРОЕКТЫ ДОМОВ (149 шт)

### Способ 1: Прямые API

#### 6.1. Список проектов домов
```
GET https://house-api.trendagent.ru/v1/projects/search
```
**Параметры:**
- `count=20`
- `offset=0, 20, 40, ...`
- `sort_order=asc`
- `sort_type=price`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`
- `auth_token=<TOKEN>` ⚠️ **ОБЯЗАТЕЛЬНО**

**Структура ответа:**
```json
{
  "total_count": 149,
  "result_count": 20,
  "list": [
    {
      "_id": "...",
      "name": "...",
      "contractor_name": "...",
      "contractor_id": "...",
      "price": 0,
      "square_total": 0,
      "square_living": 0,
      "square_kitchen": 0,
      "bedrooms_number": 0,
      "bathrooms_number": 0,
      "floor_number": 0,
      "finishing_type": "...",
      "technology_list": [...],
      "terrace": false,
      "images": [...],
      "reward": {...},
      "is_requested_price": false,
      "construction_period": "...",
      "guid": "..."
    }
  ]
}
```

**Пагинация:** 149 / 20 = **8 страниц**

### Способ 2: ⚠️ Требует браузерного анализа

---

## 🔑 АВТОРИЗАЦИЯ

### Требуют `auth_token`:
- ✅ Паркинги (все эндпоинты)
- ✅ Участки (все эндпоинты)
- ✅ Коммерция (планировки)
- ✅ Проекты домов (все эндпоинты)

### Не требуют `auth_token`:
- ✅ Квартиры (основные эндпоинты)
- ✅ Дома (основные эндпоинты)
- ✅ Коммерция (основные эндпоинты)

---

## 📋 ФИЛЬТРЫ (требуют браузерного анализа)

### Квартиры:
- ⚠️ Требует анализа страницы `https://spb.trendagent.ru/objects/table/`

### Паркинги:
- ⚠️ Требует анализа страницы `https://spb.trendagent.ru/parkings/table/`

### Дома:
- ⚠️ Требует анализа страницы `https://spb.trendagent.ru/houses/table/`

### Участки:
- ⚠️ Требует анализа страницы `https://spb.trendagent.ru/villages/plots`

### Коммерция:
- ⚠️ Требует анализа страницы `https://spb.trendagent.ru/commerce/table`

### Проекты домов:
- ⚠️ Требует анализа страницы `https://spb.trendagent.ru/houseprojects`

---

## 🖼️ ИЗОБРАЖЕНИЯ

### Планировки:
- **Квартиры:** `https://selcdn.trendagent.ru/images/{path}m_{file_name}` (миниатюра)
- **Квартиры:** `https://selcdn.trendagent.ru/images/{path}{file_name}` (полный размер)
- **Дома:** Аналогично квартирам
- **Коммерция:** ⚠️ Требует браузерного анализа

### Поэтажные планы:
- ⚠️ Требует браузерного анализа для каждого типа

---

## 📄 ДОКУМЕНТЫ

- ⚠️ Требует браузерного анализа для каждого типа

---

## ✅ СЛЕДУЮЩИЕ ШАГИ

1. [ ] Браузерный анализ всех 12 страниц
2. [ ] Найти второй способ получения данных для каждого типа
3. [ ] Записать все фильтры с их свойствами
4. [ ] Найти все роуты для детальной информации
5. [ ] Найти все изображения и документы
6. [ ] Реализовать полный парсинг всех типов

---

**Статус:** ✅ API анализ завершен, требуется браузерный анализ
