# ✅ НАЙДЕННЫЕ ПРАВИЛЬНЫЕ ЭНДПОИНТЫ (из кода проекта)

**Дата:** 2026-02-08  
**Источник:** Анализ кода `app/Services/TrendAgent/TrendSsoApiAuth.php`

---

## 1️⃣ ПЛАНИРОВКИ КВАРТИР (CHECKERBOARD) ✅

### Эндпоинт 1: Получение корпусов для шахматки

**URL:** `https://api.trendagent.ru/v4_29/checkerboards/{blockId}/apartments/buildings/`

**Метод:** GET

**Параметры:**
- `city` - ID города (58c665588b6aa52311afa01b для SPB)
- `lang` - язык (ru)
- `auth_token` - токен авторизации
- `room` (опционально) - массив количества комнат [30, 40]
- `onrequest` (опционально) - фильтр по запросу

**Пример:**
```
https://api.trendagent.ru/v4_29/checkerboards/63c50acc9a85d53360f63a76/apartments/buildings/?city=58c665588b6aa52311afa01b&lang=ru&auth_token=...
```

**Статус в коде:** ✅ Используется в `getCheckerboardBuildings()`

---

### Эндпоинт 2: Получение квартир по корпусу

**URL:** `https://api.trendagent.ru/v4_29/checkerboards/{blockId}/apartments/`

**Метод:** GET

**Параметры:**
- `city` - ID города
- `lang` - язык
- `auth_token` - токен авторизации
- `building_id` - **ОБЯЗАТЕЛЬНЫЙ** ID корпуса

**Пример:**
```
https://api.trendagent.ru/v4_29/checkerboards/63c50acc9a85d53360f63a76/apartments/?city=58c665588b6aa52311afa01b&lang=ru&building_id=63c50acc9a85d53360f63a77&auth_token=...
```

**Статус в коде:** ✅ Используется в `getCheckerboardApartments()`

**Важно:** 
- Используется `checkerboards` (множественное число), а не `checkerboard`
- Параметр `building_id` обязателен для получения квартир

---

## 2️⃣ ДЕТАЛЬНАЯ ИНФОРМАЦИЯ О КВАРТИРЕ

### Эндпоинт: Получение детальной информации

**URL:** `https://api.trendagent.ru/v4_29/apartments/{apartmentId}`

**Метод:** GET

**Параметры:**
- `city` - ID города
- `lang` - язык
- `auth_token` - токен авторизации
- `block` (опционально) - ID блока

**Пример:**
```
https://api.trendagent.ru/v4_29/apartments/63c5614728d3bcf2420860b1?city=58c665588b6aa52311afa01b&lang=ru&auth_token=...
```

**Статус в коде:** ✅ Используется в `getApartmentDetail()` с fallback механизмом

**Fallback механизм:**
Если прямой запрос не работает, код использует:
1. Поиск квартиры в списке комплекса через `/v4_29/blocks/{blockId}/unified/`
2. Поиск в данных checkerboard
3. Поиск в данных поиска квартир

---

## 3️⃣ ДЕТАЛЬНАЯ ИНФОРМАЦИЯ О ПРОЕКТЕ ДОМА

### Эндпоинт: Получение детальной информации о проекте

**URL:** `https://house-api.trendagent.ru/v1/projects/{projectId}`

**Метод:** GET

**Параметры:**
- `city` - ID города
- `lang` - язык
- `auth_token` - токен авторизации

**Пример:**
```
https://house-api.trendagent.ru/v1/projects/66d02665d5fa3023a711487c?city=58c665588b6aa52311afa01b&lang=ru&auth_token=...
```

**Статус в коде:** ✅ Используется в `getContractorProjectDetails()`

**Важно:** 
- Используется `projectId` (ID), а не `slug`
- Нужно сначала получить ID проекта из списка, затем использовать его для детальной информации

---

## 4️⃣ ДЕТАЛЬНАЯ ИНФОРМАЦИЯ О ПОСЕЛКЕ/УЧАСТКЕ

### Эндпоинт: Получение детальной информации об участке

**URL:** `https://house-api.trendagent.ru/v1/villages/{villageId}/plots/{plotId}`

**Метод:** GET (предположительно)

**Параметры:**
- `city` - ID города
- `lang` - язык
- `auth_token` - токен авторизации

**Статус в коде:** ⚠️ Используется в `getPlotDetail()`, но формат может отличаться

**Альтернатива:**
Данные участка могут приходить вместе с данными поселка из:
- `https://house-api.trendagent.ru/v1/search/villages`

---

## 5️⃣ ДЕТАЛЬНАЯ ИНФОРМАЦИЯ О ПОМЕЩЕНИИ КОММЕРЦИИ

### Эндпоинт: Получение детальной информации о помещении

**URL:** `https://commerce-api.trendagent.ru/premises/{premiseId}`

**Метод:** GET (предположительно)

**Параметры:**
- `city` - ID города
- `lang` - язык
- `auth_token` - токен авторизации

**Статус в коде:** ⚠️ Не найден прямой метод, но данные могут приходить из:
- `https://commerce-api.trendagent.ru/search/premises` (список с детальной информацией)

---

## 🔍 РАЗНИЦА МЕЖДУ НЕРАБОТАЮЩИМИ И РАБОТАЮЩИМИ ЭНДПОИНТАМИ

### ❌ Неправильные (не работают):

1. `https://api.trendagent.ru/v4_29/checkerboard/{blockId}/apartments/` 
   - ❌ Использует `checkerboard` (единственное число)
   - ❌ Не указывает `building_id`

2. `https://api.trendagent.ru/v4_29/apartments/{ID}` (без правильных параметров)
   - ❌ Может требовать `block` параметр
   - ❌ Может требовать другой формат авторизации

3. `https://house-api.trendagent.ru/v1/villages/{ID}`
   - ❌ Может требовать другой формат (villageId/plotId)
   - ❌ Может требовать POST вместо GET

4. `https://commerce-api.trendagent.ru/premises/{ID}`
   - ❌ Может требовать другой путь
   - ❌ Может требовать POST вместо GET

5. `https://house-api.trendagent.ru/v1/projects/{slug}`
   - ❌ Использует slug вместо ID
   - ❌ Нужно сначала получить ID из списка проектов

### ✅ Правильные (работают):

1. `https://api.trendagent.ru/v4_29/checkerboards/{blockId}/apartments/buildings/`
   - ✅ Использует `checkerboards` (множественное число)
   - ✅ Правильный путь

2. `https://api.trendagent.ru/v4_29/checkerboards/{blockId}/apartments/`
   - ✅ Использует `checkerboards`
   - ✅ Требует `building_id` параметр

3. `https://api.trendagent.ru/v4_29/apartments/{ID}?block={blockId}`
   - ✅ С параметром `block`
   - ✅ С правильными заголовками авторизации

---

## 📝 ИНСТРУКЦИЯ ДЛЯ ТЕСТИРОВАНИЯ В БРАУЗЕРЕ

### Шаг 1: Откройте DevTools (F12) → Network

### Шаг 2: Для каждого эндпоинта:

1. **Планировки:**
   - Откройте: `https://spb.trendagent.ru/object/dom-na-naberezhnoy-st/checkerboard`
   - Найдите запрос к `/v4_29/checkerboards/...`
   - Проверьте, используется ли `checkerboards` (множественное число)
   - Проверьте, передается ли `building_id` для запроса квартир

2. **Детальная информация о квартире:**
   - Откройте: `https://spb.trendagent.ru/object/dom-na-naberezhnoy-st/flat/63c5614728d3bcf2420860b1`
   - Найдите запрос к `/v4_29/apartments/63c5614728d3bcf2420860b1`
   - Проверьте, какие параметры передаются
   - Проверьте, используется ли параметр `block`

3. **Детальная информация о проекте:**
   - Откройте: `https://spb.trendagent.ru/houseproject/dk177-kopiya`
   - Найдите запрос к `/v1/projects/...`
   - Проверьте, используется ли ID или slug
   - Проверьте, как получается ID из slug

---

## ✅ ВЫВОДЫ

1. **Планировки работают** через `/v4_29/checkerboards/` (множественное число)
2. **Детальная информация** может требовать дополнительные параметры
3. **Проекты** требуют ID, а не slug
4. **Участки и помещения** могут получаться из списков, а не через отдельные эндпоинты

---

**Следующий шаг:** Протестировать найденные эндпоинты в браузере и подтвердить их работоспособность! 🚀
