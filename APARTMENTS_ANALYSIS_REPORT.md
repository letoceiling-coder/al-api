# 📊 ОТЧЕТ ПО АНАЛИЗУ: КВАРТИРЫ

**Дата:** 2026-02-08  
**Статус:** ✅ Анализ завершен

---

## 📈 СТАТИСТИКА

- **Всего квартир:** 55,548
- **Комплексов (ЖК):** 344
- **Забронировано:** 2,508
- **Свободно:** ~53,040

---

## 🔗 API ЭНДПОИНТЫ

### 1. Список квартир
```
GET https://api.trendagent.ru/v4_29/apartments/search/
```

**Параметры:**
- `sort=price`
- `sort_order=asc`
- `count=50` (максимум за запрос)
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
    "list": [
      {
        "_id": "65c9df118a29dd4fe1d80167",
        "number": "4",
        "block_id": "65c8b45523bccfa820bfaf73",
        "block_name": "Villa Marina",
        "block_guid": "villa-marina",
        "plan": {
          "path": "ib/hb/",
          "file_name": "680de0ce0b5309e7329a2d74e634c549.png"
        },
        ...
      }
    ]
  }
}
```

**Пагинация:**
- 1-я страница: `offset=0`, получаем 50 квартир
- 2-я страница: `offset=50`, получаем следующие 50
- И так далее до `offset=55500` (для всех 55,548 квартир)

**Расчет:** 55,548 / 50 = **1,111 страниц** для полного парсинга

---

### 2. Планировки квартир
```
GET https://api.trendagent.ru/v4_29/apartments/search/
```

**Параметры:**
- Те же, что и для списка
- `count=30` (меньше для планировок)

**Особенность:** В ответе уже включено поле `plan` с планировкой каждой квартиры!

**Структура планировки:**
```json
{
  "plan": {
    "path": "ib/hb/",
    "file_name": "680de0ce0b5309e7329a2d74e634c549.png"
  }
}
```

**URL изображения планировки:**
```
https://selcdn.trendagent.ru/images/{path}m_{file_name}
https://selcdn.trendagent.ru/images/{path}{file_name}
```

**Пример:**
```
https://selcdn.trendagent.ru/images/ib/hb/m_680de0ce0b5309e7329a2d74e634c549.png (миниатюра)
https://selcdn.trendagent.ru/images/ib/hb/680de0ce0b5309e7329a2d74e634c549.png (полный размер)
```

---

### 3. Список комплексов (ЖК)
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

**Структура ответа:**
```json
{
  "errors": null,
  "data": [
    {
      "_id": "65c8b45523bccfa820bfaf73",
      "name": "Villa Marina",
      "guid": "villa-marina",
      ...
    }
  ]
}
```

**Пагинация:** 344 / 20 = **18 страниц**

---

### 4. Карта комплексов
```
GET https://api.trendagent.ru/v4_29/blocks/search/
```

**Параметры:**
- `show_type=map`
- `auth_token=<TOKEN>`
- `city=58c665588b6aa52311afa01b`
- `lang=ru`

**Возвращает:** Точки на карте с координатами комплексов

---

### 5. Информация по клику на карте
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

---

## 📦 СТРУКТУРА ДАННЫХ КВАРТИРЫ

### Основные поля:

| Поле | Тип | Описание |
|------|-----|----------|
| `_id` | string | ID квартиры |
| `number` | string | Номер квартиры |
| `block_id` | string | ID комплекса |
| `block_name` | string | Название комплекса |
| `block_guid` | string | GUID комплекса |
| `plan` | object | Планировка (path, file_name) |
| `area_given` | number | Приведенная площадь |
| `area_kitchen` | number | Площадь кухни |
| `price` | number | Цена |
| `floor` | number | Этаж |
| `floors` | number | Всего этажей |
| `room` | object | Тип комнат (name, name_short) |
| `status` | object | Статус (name, crm_id) |
| `finishing` | object | Отделка |
| `finishing_main` | array | Основная отделка |
| `finishing_additional` | array | Дополнительная отделка |
| `builder` | object | Застройщик |
| `district` | object | Район |
| `location` | object | Локация |
| `subway` | object | Метро |
| `deadline` | string | Срок сдачи |
| `deadline_over_check` | boolean | Просрочен ли срок |
| `view` | number | Вид |
| `view_places` | array | Места с видом |
| `reward` | object | Вознаграждение |
| `exclusive` | boolean | Эксклюзив |
| `is_suite` | boolean | Апартаменты |
| `building_name` | string | Название корпуса |
| `north` | number | Ориентация на север |

---

## 🖼️ ИЗОБРАЖЕНИЯ

### Планировки:
- **Миниатюра:** `https://selcdn.trendagent.ru/images/{path}m_{file_name}`
- **Полный размер:** `https://selcdn.trendagent.ru/images/{path}{file_name}`

### Поэтажный план:
- Требует дополнительного анализа страницы квартиры

---

## 🌐 СТРАНИЦЫ ДЛЯ АНАЛИЗА В БРАУЗЕРЕ

### 1. Страница комплекса
```
https://promo.trendagent.ru/sloboda
```

**Задачи:**
- [ ] Открыть в браузере
- [ ] Авторизоваться (+79045393434 / nwBvh4q)
- [ ] Изучить HTML структуру
- [ ] Найти все AJAX запросы (Network tab)
- [ ] Запомнить все роуты API
- [ ] Скриншоты всех блоков
- [ ] Запомнить структуру данных

### 2. Страница квартиры
```
https://spb.trendagent.ru/object/dom-na-naberezhnoy-st/flat/63c5614728d3bcf2420860b1?sort=price&sort_order=asc&open=table&page=1&position=400
```

**Задачи:**
- [ ] Открыть в браузере
- [ ] Изучить HTML структуру
- [ ] Найти все роуты загрузки данных
- [ ] Запомнить блок с планировками
- [ ] Запомнить блок с фото
- [ ] Запомнить поэтажный план
- [ ] Проверить галерею изображений

### 3. Страница фильтров
```
https://spb.trendagent.ru/objects/table/
```

**Задачи:**
- [ ] Открыть страницу
- [ ] Кликнуть "Все фильтры"
- [ ] Запомнить список всех фильтров
- [ ] Для каждого фильтра запомнить:
  - [ ] Название
  - [ ] Тип (select, checkbox, range, etc.)
  - [ ] Возможные значения
  - [ ] API параметр

---

## ✅ ВЫВОДЫ

1. ✅ **API эндпоинты работают корректно**
2. ✅ **Структура данных понятна**
3. ✅ **Планировки включены в основной ответ**
4. ⚠️ **Требуется анализ страниц в браузере** для:
   - Поэтажных планов
   - Дополнительных фото
   - Фильтров
   - Детальной информации

---

## 🔧 РЕКОМЕНДАЦИИ ДЛЯ РЕАЛИЗАЦИИ

1. **Парсинг списка квартир:**
   - Использовать пагинацию с `offset`
   - Парсить по 50 квартир за запрос
   - Всего: 1,111 страниц

2. **Сохранение планировок:**
   - Планировки уже в ответе (`plan` поле)
   - Скачивать изображения по URL

3. **Связь с комплексами:**
   - Использовать `block_id`, `block_name`, `block_guid`
   - Парсить комплексы отдельно для полной информации

4. **Детальная информация:**
   - Требуется анализ страницы квартиры
   - Найти эндпоинт для детальной информации

---

## 📝 СЛЕДУЮЩИЕ ШАГИ

1. [ ] Анализ страницы комплекса в браузере
2. [ ] Анализ страницы квартиры в браузере
3. [ ] Анализ страницы фильтров в браузере
4. [ ] Реализация полного парсинга квартир
5. [ ] Реализация сохранения планировок и фото

---

**Статус:** ✅ API анализ завершен, требуется браузерный анализ
