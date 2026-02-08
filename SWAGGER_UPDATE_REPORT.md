# ✅ ОТЧЕТ: Обновление Swagger документации

**Дата:** 2026-02-08  
**URL:** https://api.siteaccess.ru/swagger/trendagent  
**Статус:** ✅ Завершено

---

## 📊 ВЫПОЛНЕННЫЕ ОБНОВЛЕНИЯ

### 1️⃣ Обновлена версия API

**Изменения:**
- ✅ Версия обновлена с `1.0.0` на `2.0.0`
- ✅ Добавлено описание обновлений в description

---

### 2️⃣ Добавлены новые эндпоинты

#### Подрядчики (Contractors / House Projects)

1. **POST `/houseprojects`** - Список подрядчиков (проектов домов)
   - Использует: `GET https://house-api.trendagent.ru/v1/projects/search`
   - Параметры: `phone`, `password`, `city`, `count`, `offset`, `sort_type`, `sort_order`
   - Возвращает: список проектов с информацией о подрядчиках

2. **POST `/houseprojects/{id}`** - Детальная информация о проекте
   - Использует: unified эндпоинт `/v1/projects/{projectId}/unified`
   - Поддерживает получение ID по GUID через `/v1/projects/id?guid={slug}`
   - Возвращает: полную информацию о проекте в формате unified

#### Коммерция (Commercial)

3. **POST `/commercial`** - Список коммерческих помещений
   - Использует: `GET https://commerce-api.trendagent.ru/search/premises`
   - Параметры: `phone`, `password`, `city`, `count`, `offset`, `sort`, `price_from`, `price_to`, `area_from`, `area_to`, `purpose`
   - Возвращает: список помещений с total, premises_count, blocks_count

4. **POST `/commercial/{id}`** - Детальная информация о помещении
   - Использует: unified эндпоинт `/commerce/{premiseId}/unified/`
   - Возвращает: полную информацию о помещении в формате unified

#### Участки (Plots)

5. **POST `/plots/{id}`** - Детальная информация о поселке
   - Использует: unified эндпоинт `/v1/villages/{villageId}/unified`
   - Поддерживает получение ID по GUID через `/v1/villages/id?guid={slug}`
   - Возвращает: полную информацию о поселке в формате unified

#### Дома (Houses)

6. **POST `/houses/{id}`** - Детальная информация о доме
   - Использует: unified эндпоинт `/v4_29/apartments/{id}/unified/` (тот же, что для квартир)
   - Параметры: `options.apartments` - для домов возвращает дома с фильтром `room=[30,40]`
   - Возвращает: полную информацию о доме в формате unified

---

### 3️⃣ Обновлены описания существующих эндпоинтов

#### Квартиры (Apartments)

1. **POST `/apartments/{id}/flat/{apartmentId}`**
   - ✅ Обновлено описание: использует unified эндпоинт `/v4_29/apartments/{apartmentId}/unified/`

2. **POST `/apartments/{id}/checkerboard/buildings`**
   - ✅ Обновлено описание: использует `/v4_29/checkerboards/{blockId}/apartments/buildings/`

3. **POST `/apartments/{id}/checkerboard/apartments`**
   - ✅ Обновлено описание: использует `/v4_29/checkerboards/{blockId}/apartments/?building_id={buildingId}`

---

### 4️⃣ Добавлены новые теги

- ✅ **Contractors** - Операции с подрядчиками и проектами домов
- ✅ Обновлен тег **House Projects** - добавлено описание "(подрядчики)"

---

## 📋 НОВЫЕ ЭНДПОИНТЫ В SWAGGER

| Эндпоинт | Метод | Описание | Unified |
|----------|-------|----------|---------|
| `/houseprojects` | POST | Список подрядчиков | ❌ |
| `/houseprojects/{id}` | POST | Детали проекта | ✅ |
| `/commercial` | POST | Список помещений | ❌ |
| `/commercial/{id}` | POST | Детали помещения | ✅ |
| `/plots/{id}` | POST | Детали поселка | ✅ |
| `/houses/{id}` | POST | Детали дома | ✅ |

---

## 🔄 ОБНОВЛЕННЫЕ ЭНДПОИНТЫ

| Эндпоинт | Изменения |
|----------|-----------|
| `/apartments/{id}/flat/{apartmentId}` | Обновлено описание: использует unified |
| `/apartments/{id}/checkerboard/buildings` | Обновлено описание: правильный эндпоинт |
| `/apartments/{id}/checkerboard/apartments` | Обновлено описание: правильный эндпоинт |

---

## 📝 ИСПОЛЬЗУЕМЫЕ UNIFIED ЭНДПОИНТЫ

1. **Квартиры/Дома:** `/v4_29/apartments/{id}/unified/`
2. **Коммерция:** `/commerce/{premiseId}/unified/`
3. **Участки:** `/v1/villages/{villageId}/unified`
4. **Подрядчики:** `/v1/projects/{projectId}/unified`

---

## ✅ РЕЗУЛЬТАТЫ

- ✅ Версия API обновлена до 2.0.0
- ✅ Добавлены эндпоинты для подрядчиков
- ✅ Добавлены эндпоинты для коммерции
- ✅ Обновлены описания существующих эндпоинтов
- ✅ Добавлена информация о unified эндпоинтах
- ✅ JSON валиден

---

## 🎯 ДОСТУП К ДОКУМЕНТАЦИИ

- **Swagger UI:** https://api.siteaccess.ru/swagger/trendagent
- **Swagger JSON:** https://api.siteaccess.ru/trendagent/v1/swagger.json

---

**Отчет создан:** 2026-02-08  
**Swagger документация обновлена!** 🚀
