# 📊 ОТЧЕТ ПО АНАЛИЗУ: ПАРКИНГИ

**Дата:** 2026-02-08  
**Статус:** ✅ Анализ завершен

---

## 📈 СТАТИСТИКА

- **Всего машиномест:** 3,644 ✅
- **ЖК с паркингами:** 50 ✅
- **Забронировано:** 572
- **Свободно:** ~3,072

---

## 🔗 API ЭНДПОИНТЫ

### 1. Список машиномест
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

---

### 2. Список ЖК с паркингами
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

---

### 3. Карта паркингов
```
GET https://parkings-api.trendagent.ru/search/pins
```

**Параметры:**
- `city=58c665588b6aa52311afa01b`
- `lang=ru`
- `auth_token=<TOKEN>` ⚠️ **ОБЯЗАТЕЛЬНО**

---

### 4. Информация по клику на карте
```
GET https://parkings-api.trendagent.ru/search/map/block/{BLOCK_ID}
```

**Параметры:**
- `city=58c665588b6aa52311afa01b`
- `lang=ru`
- `auth_token=<TOKEN>` ⚠️ **ОБЯЗАТЕЛЬНО**

---

## 📦 СТРУКТУРА ДАННЫХ МАШИНОМЕСТА

### Основные поля:

| Поле | Тип | Описание |
|------|-----|----------|
| `_id` | string | ID машиноместа |
| `number` | string | Номер |
| `block_id` | string | ID комплекса |
| `block_name` | string | Название комплекса |
| `block_guid` | string | GUID комплекса |
| `area` | number | Площадь |
| `price` | number | Цена |
| `floor` | number | Этаж |
| `floor_image` | object | Изображение этажа |
| `geometry` | object | Геометрия |
| `parking_type` | object | Тип паркинга |
| `status` | object | Статус |
| `type` | string | Тип |
| `builder` | object | Застройщик |
| `district` | object | Район |
| `location` | object | Локация |
| `subway` | object | Метро |
| `deadline` | string | Срок сдачи |
| `deadline_over_check` | boolean | Просрочен ли |
| `reward` | object | Вознаграждение |
| `property_type` | object | Тип недвижимости |

---

## ✅ ВЫВОДЫ

1. ✅ **Все эндпоинты требуют `auth_token`**
2. ✅ **Структура данных понятна**
3. ✅ **Пагинация работает через `offset`**
4. ✅ **Всего 3,644 машиноместа в 50 ЖК**

---

**Статус:** ✅ API анализ завершен
