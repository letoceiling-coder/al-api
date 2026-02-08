# 📋 ПОЛНОЕ ТЕХНИЧЕСКОЕ ЗАДАНИЕ: Парсинг TrendAgent

**Дата:** 2026-02-08  
**Логин:** +79045393434  
**Пароль:** nwBvh4q

---

## 🎯 ЦЕЛЬ

Спарсить **ВСЕ** данные по Санкт-Петербургу со всеми деталями, планировками, фотографиями и фильтрами.

---

## 📊 ОБЩАЯ СТАТИСТИКА

| Тип объекта | Количество | Статус |
|-------------|------------|--------|
| **Квартиры** | 55,551 шт | ⚠️ Требует полного парсинга |
| **ЖК (комплексы)** | 352 шт | ⚠️ Требует полного парсинга |
| **Паркинги (машиноместа)** | 3,644 шт | ⚠️ Требует полного парсинга |
| **Паркинги (ЖК)** | 50 шт | ⚠️ Требует полного парсинга |
| **Дома** | 1,023 шт | ⚠️ Требует полного парсинга |
| **Участки** | 2,370 шт | ⚠️ Требует полного парсинга |
| **Поселки** | 68 шт | ⚠️ Требует полного парсинга |
| **Коммерция (помещения)** | 1,775 шт | ✅ Частично готово |
| **Коммерция (ЖК)** | 168 шт | ⚠️ Требует полного парсинга |
| **Проекты домов** | 149 шт | ⚠️ Требует реализации |

---

## 1️⃣ КВАРТИРЫ

### 📊 Количество:
- **Квартир:** 55,551
- **ЖК:** 352

### 🔗 API Endpoints:

#### 1.1. Список квартир
```
GET https://api.trendagent.ru/v4_29/apartments/search/
```

**Параметры:**
- `sort=price`
- `sort_order=asc`
- `count=50`
- `offset=0` (для пагинации: 0, 50, 100, ...)
- `auth_token=<TOKEN>`
- `city=58c665588b6aa52311afa01b` (SPB)
- `lang=ru`

**Пример 1-я страница:**
```
https://api.trendagent.ru/v4_29/apartments/search/?sort=price&sort_order=asc&count=50&auth_token=...&city=58c665588b6aa52311afa01b&lang=ru
```

**Пример 2-я страница:**
```
https://api.trendagent.ru/v4_29/apartments/search/?sort=price&sort_order=asc&count=50&offset=50&auth_token=...&city=58c665588b6aa52311afa01b&lang=ru
```

#### 1.2. Планировки квартир
```
GET https://api.trendagent.ru/v4_29/apartments/search/
```

**Параметры:**
- `sort=price`
- `sort_order=asc`
- `count=30`
- `offset=0, 30, 60, ...`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`
- `auth_token=<TOKEN>`

**Особенность:** Включает планировки квартир

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

**Пример:**
```
https://api.trendagent.ru/v4_29/blocks/59fc27538bcb2468a6174402/map/?formating=true&auth_token=...&city=58c665588b6aa52311afa01b&lang=ru
```

### 🌐 Страницы для анализа:

#### 1.6. Страница комплекса
```
https://promo.trendagent.ru/sloboda
```

**Задача:**
- ✅ Открыть в браузере
- ✅ Запомнить структуру HTML
- ✅ Найти все роуты откуда загружается информация
- ✅ Запомнить все блоки страницы

#### 1.7. Страница квартиры
```
https://spb.trendagent.ru/object/dom-na-naberezhnoy-st/flat/63c5614728d3bcf2420860b1?sort=price&sort_order=asc&open=table&page=1&position=400
```

**Задача:**
- ✅ Открыть в браузере
- ✅ Запомнить структуру HTML
- ✅ Найти все роуты
- ✅ Запомнить все блоки
- ✅ Фото планировок
- ✅ Поэтажный план

#### 1.8. Страница фильтров
```
https://spb.trendagent.ru/objects/table/
```

**Задача:**
- ✅ Открыть в браузере
- ✅ Кликнуть "Все фильтры"
- ✅ Запомнить ВСЕ возможные фильтры
- ✅ Запомнить свойства каждого фильтра

---

## 2️⃣ ПАРКИНГИ

### 📊 Количество:
- **Машиномест:** 3,644
- **ЖК с паркингами:** 50

### 🔗 API Endpoints:

#### 2.1. Список машиномест
```
GET https://parkings-api.trendagent.ru/search/places/
```

**Параметры:**
- `count=50`
- `number=` (пустой для всех)
- `offset=0, 50, 100, ...`
- `sort=price`
- `sort_order=asc`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`

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

#### 2.3. Карта паркингов
```
GET https://parkings-api.trendagent.ru/search/pins
```

**Параметры:**
- `city=58c665588b6aa52311afa01b`
- `lang=ru`

#### 2.4. Информация по клику на карте
```
GET https://parkings-api.trendagent.ru/search/map/block/{BLOCK_ID}
```

**Пример:**
```
https://parkings-api.trendagent.ru/search/map/block/66421754529e7d426f2ffeaf?city=58c665588b6aa52311afa01b&lang=ru
```

### 🌐 Страницы для анализа:

#### 2.5. Страница фильтров паркингов
```
https://spb.trendagent.ru/parkings/table/
```

**Задача:**
- ✅ Открыть в браузере
- ✅ Кликнуть "Все фильтры"
- ✅ Запомнить ВСЕ фильтры
- ✅ Запомнить свойства каждого фильтра

---

## 3️⃣ ДОМА

### 📊 Количество:
- **Домов:** 1,023

### 🔗 API Endpoints:

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

#### 3.2. Список комплексов домов
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

### 🌐 Страницы для анализа:

#### 3.4. Страница фильтров домов
```
https://spb.trendagent.ru/houses/table/
```

**Задача:**
- ✅ Открыть в браузере
- ✅ Кликнуть "Все фильтры"
- ✅ Запомнить ВСЕ фильтры

#### 3.5. Страница дома
```
https://spb.trendagent.ru/object/belaya-dacha/flat/64dcedbb77be5275aff41ef9?apartments-room=30&apartments-room=40&sort=price&sort_order=asc&open=plans
```

**Задача:**
- ✅ Открыть в браузере
- ✅ Запомнить структуру HTML
- ✅ Все роуты
- ✅ Все блоки
- ✅ Фото планировок
- ✅ Поэтажный план

---

## 4️⃣ УЧАСТКИ

### 📊 Количество:
- **Поселков:** 68
- **Участков:** 2,370

### 🔗 API Endpoints:

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

#### 4.3. Карта поселков
```
GET https://house-api.trendagent.ru/v1/search/map/villages/pins
```

**Параметры:**
- `city=58c665588b6aa52311afa01b`
- `lang=ru`
- `auth_token=<TOKEN>`

#### 4.4. Информация по клику на карте
```
GET https://house-api.trendagent.ru/v1/search/map/villages/{VILLAGE_ID}
```

**Пример:**
```
https://house-api.trendagent.ru/v1/search/map/villages/68e3b2ef16cc0b93592b8638?city=58c665588b6aa52311afa01b&lang=ru
```

### 🌐 Страницы для анализа:

#### 4.5. Страница участка
```
https://spb.trendagent.ru/village/lebyazhe/plot/692578d2a5e15b2a6c65fdbd/?open=table&sort=price&sort_order=asc
```

**Задача:**
- ✅ Открыть в браузере
- ✅ Запомнить структуру HTML
- ✅ Все роуты
- ✅ Все блоки
- ✅ Фото

#### 4.6. Страница фильтров участков
```
https://spb.trendagent.ru/villages/plots
```

**Задача:**
- ✅ Открыть в браузере
- ✅ Кликнуть "Все фильтры"
- ✅ Запомнить ВСЕ фильтры

---

## 5️⃣ КОММЕРЦИЯ

### 📊 Количество:
- **Помещений:** 1,775
- **ЖК:** 168

### 🔗 API Endpoints:

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

### 🌐 Страницы для анализа:

#### 5.4. Страница фильтров коммерции
```
https://spb.trendagent.ru/commerce/table
```

**Задача:**
- ✅ Открыть в браузере
- ✅ Кликнуть "Все фильтры"
- ✅ Запомнить ВСЕ фильтры

#### 5.5. Страница помещения
```
https://spb.trendagent.ru/commerce-premise/66d02665d5fa3023a711487c/?open=table&sort=price&sort_order=asc
```

**Задача:**
- ✅ Открыть в браузере
- ✅ Запомнить структуру HTML
- ✅ Все роуты
- ✅ Все блоки
- ✅ Фото
- ✅ Поэтажный план

---

## 6️⃣ ПРОЕКТЫ ДОМОВ (ПОДРЯДЧИКИ)

### 📊 Количество:
- **Проектов:** 149

### 🔗 API Endpoints:

#### 6.1. Список проектов домов
```
GET https://house-api.trendagent.ru/v1/projects/search
```

**Параметры:**
- `count=20`
- `offset=0, 20, 40`
- `sort_order=asc`
- `sort_type=price`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`

### 🌐 Страницы для анализа:

#### 6.2. Страница фильтров проектов
```
https://spb.trendagent.ru/houseprojects
```

**Задача:**
- ✅ Открыть в браузере
- ✅ Кликнуть "Все фильтры"
- ✅ Запомнить ВСЕ фильтры

#### 6.3. Страница проекта дома
```
https://spb.trendagent.ru/houseproject/dk177-kopiya?prev_event_page=search_projects_listing_page
```

**Задача:**
- ✅ Открыть в браузере
- ✅ Запомнить структуру HTML
- ✅ Все роуты
- ✅ Все блоки
- ✅ Фото

---

## 🔧 ТЕХНИЧЕСКИЕ ДЕТАЛИ

### Пагинация
Для всех списков используется параметр `offset`:
- `offset=0` - 1-я страница
- `offset=50` - 2-я страница (если count=50)
- `offset=100` - 3-я страница
- и т.д.

### Авторизация
- **Логин:** +79045393434
- **Пароль:** nwBvh4q
- **Параметр:** `auth_token=<TOKEN>` (получается при авторизации)

### Город
- **SPB ID:** `58c665588b6aa52311afa01b`
- **Параметр:** `city=58c665588b6aa52311afa01b`

### Язык
- **Параметр:** `lang=ru`

---

## ✅ ТРЕБОВАНИЯ

1. ✅ **Открыть ВСЕ страницы в браузере**
2. ✅ **Авторизоваться** (+79045393434 / nwBvh4q)
3. ✅ **Запомнить структуру HTML** каждой страницы
4. ✅ **Найти ВСЕ роуты** откуда загружается информация
5. ✅ **Запомнить ВСЕ фильтры** на страницах с кнопкой "Все фильтры"
6. ✅ **Запомнить свойства каждого фильтра**
7. ✅ **Запомнить структуру планировок** и фото
8. ✅ **Запомнить поэтажные планы**

---

## 📋 ЧЕКЛИСТ

См. файл `FULL_TASK_CHECKLIST.md`

---

## 📝 ПРИМЕЧАНИЯ

- Все эндпоинты протестированы пользователем
- Токены в примерах временные (нужно получать при каждой авторизации)
- Некоторые эндпоинты требуют обязательной авторизации
- Пагинация одинаковая для всех типов объектов
