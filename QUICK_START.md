# 🚀 Быстрый старт: Парсинг TrendAgent

## ✅ Что исправлено

### 🏢 Коммерция - ГОТОВО
- **Было:** 167 комплексов
- **Стало:** 1,774 помещения
- **Статус:** ✅ Полностью рабочий

### 🏠 Дома - ЧАСТИЧНО
- **Было:** 344 дома (лимит offset)
- **Стало:** 359 домов (+15)
- **Статус:** ⚠️ Требует доработки для полного парсинга

### 🏞️ Участки - ТРЕБУЕТ РЕАЛИЗАЦИИ
- **Сейчас:** 344 участка (лимит offset)
- **Нужно:** ~2,370 участков
- **Статус:** ⚠️ Требует реализации раздельного парсинга

---

## 📋 Команды парсинга

### Коммерция (полностью работает):
```bash
# Все помещения СПб
php artisan trendagent:parse --region=spb --type=commercial --limit=2000 --save-raw

# Результат: 1,774 помещения ✅
```

### Дома (частично работает):
```bash
# Текущий вариант (359 домов)
php artisan trendagent:parse --region=spb --type=houses --limit=500 --save-raw

# Для полного парсинга нужна доработка ⚠️
```

### Участки (требует доработки):
```bash
# Текущий вариант (344 участка)
php artisan trendagent:parse --region=spb --type=plots --limit=500 --save-raw

# Для полного парсинга нужна реализация ⚠️
```

### Квартиры и паркинги (работают):
```bash
# Квартиры
php artisan trendagent:parse --region=spb --type=apartments --limit=50000 --save-raw

# Паркинги
php artisan trendagent:parse --region=spb --type=parkings --limit=5000 --save-raw
```

---

## 📊 Текущие результаты

| Тип | Команда | Результат | Статус |
|-----|---------|-----------|--------|
| Квартиры | `--type=apartments --limit=50000` | ~40,000+ | ✅ |
| Паркинги | `--type=parkings --limit=5000` | ~3,644 | ✅ |
| **Коммерция** | **`--type=commercial --limit=2000`** | **1,774** | ✅ |
| Дома | `--type=houses --limit=500` | 359 | ⚠️ |
| Участки | `--type=plots --limit=500` | 344 | ⚠️ |

---

## 🔍 Структура сохраненных данных

### Коммерция:
```
storage/app/private/trendagent/parsing/spb/raw/commercial/
├── premises_list_offset_0.json      # 100 помещений
├── premises_list_offset_100.json    # 100 помещений
├── ...
└── premises_list_offset_1700.json   # 74 помещения
```

### Пример данных помещения:
```json
{
  "_id": "66cf3c79c5983b2c858cab4d",
  "block_name": "Smart Восстановления",
  "area_total": 5.1,
  "price": 1606500,
  "price_m2": 315000,
  "purpose": {"label": "Свободное назначение"},
  "status": {"label": "Свободно"}
}
```

---

## ⚙️ Измененные файлы

### Основные:
1. `app/Services/TrendAgent/TrendAgentApiClient.php`
   - ✅ Исправлен `getCommercial()`
   - ✅ Добавлен `getCommercePremises()`

2. `app/Services/TrendAgent/TrendSsoApiAuth.php`
   - ✅ Добавлен `getCommercePremises()`

3. `app/Console/Commands/TrendAgent/ParseCommand.php`
   - ✅ Обновлен `parseCommercial()`

---

## 🎯 Следующие шаги

### Для полного парсинга домов:
1. Обновить `parseHouses()` для раздельного парсинга:
   - room=30 (Коттеджи)
   - room=40 (Таунхаусы)

### Для полного парсинга участков:
1. Добавить поддержку фильтра `room` в API
2. Обновить `parsePlots()` для раздельного парсинга:
   - room=50 (ИЖС)
   - room=52 (СНТ)

---

## 📞 Техническая информация

- **Дата обновления:** 2026-02-08
- **Версия:** 1.1.0
- **Статус коммерции:** ✅ Production Ready
- **Статус домов/участков:** ⚠️ Requires Implementation

---

## ❓ FAQ

**Q: Почему коммерция теперь парсит 1,774 объекта вместо 167?**  
A: Раньше парсились **комплексы**, теперь парсятся **помещения** (как требуется).

**Q: Как получить больше 344 домов/участков?**  
A: Нужна реализация раздельного парсинга по типам (room filter).

**Q: Где хранятся данные?**  
A: `storage/app/private/trendagent/parsing/{region}/raw/{type}/`

**Q: Как запустить полный парсинг всех типов?**  
A: `php artisan trendagent:parse --region=spb --type=all --limit=50000 --save-raw`
