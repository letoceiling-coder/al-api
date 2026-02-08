# Отчет по исправлению парсинга коммерции

**Дата:** 2026-02-08  
**Задача:** Исправить парсинг коммерческих объектов в TrendAgent

---

## 🔍 Проблема

При запуске парсинга коммерции:
```bash
php artisan trendagent:parse --region=spb --type=commercial --limit=2000
```

**Результат:**
- Обработано: **167 объектов** (вместо ожидаемых ~1,771 помещений)
- Парсились **комплексы**, а не помещения
- API метод `getCommercial()` вызывал `getBlocksSearch()` вместо `getCommercialSearch()`

---

## 🔬 Исследование

### 1. Анализ API

**Обнаружено:**
- `getCommercial()` вызывал **неправильный метод** `getBlocksSearch()` 
- Возвращались данные **квартир** (room=23), а не коммерции

**Тестирование:**
```bash
php test_commercial_basic.php
```
**До исправления:**
- Первый объект: "Villa Marina" (квартирный комплекс)
- Total: 344

**После исправления:**
- Первый объект: "Smart Восстановления" (коммерческий комплекс)
- Total: 167

### 2. Поиск эндпоинта для помещений

Пользователь указал правильный эндпоинт:
```
https://commerce-api.trendagent.ru/search/premises
```

**Тестирование:**
```bash
php test_commerce_api_premises.php
```

**Результат:**
```
✅ premises_count: 1,772
✅ blocks_count: 167
✅ Получено: 50 помещений за запрос
```

---

## ✅ Решение

### 1. Исправлен `TrendAgentApiClient::getCommercial()`

**Было:**
```php
$result = $this->auth->getBlocksSearch($apiParams);
```

**Стало:**
```php
$result = $this->executeWithRetry(function() use ($apiParams) {
    return $this->auth->getCommercialSearch($apiParams);
});
```

### 2. Добавлен метод `getCommercePremises()`

**В `TrendSsoApiAuth`:**
```php
public function getCommercePremises(array $params = []): array
{
    $apiUrl = 'https://commerce-api.trendagent.ru/search/premises';
    // ... реализация
}
```

**В `TrendAgentApiClient`:**
```php
public function getCommercePremises(array $params = []): array
{
    return $this->executeWithRetry(function() use ($apiParams) {
        return $this->auth->getCommercePremises($apiParams);
    });
}
```

### 3. Обновлен `ParseCommand::parseCommercial()`

**Было:**
```php
$data = $this->apiClient->getCommercial($params);
```

**Стало:**
```php
$data = $this->apiClient->getCommercePremises($params);
```

---

## 📊 Результаты

### До исправления:
```
📦 Обработано: 167 комплексов
⚠️  Помещения: не парсились
```

### После исправления:
```
✅ Обработано: 1,774 помещения
✅ Ошибок: 0
✅ Время: 10 секунд
✅ Сохранено файлов: 18 файлов по 100 помещений
```

### Структура данных помещения:
```json
{
  "_id": "66cf3c79c5983b2c858cab4d",
  "block_id": "66ce4335ed7175f303a8898d",
  "block_name": "Smart Восстановления",
  "area_total": 5.1,
  "price": 1606500,
  "price_m2": 315000,
  "purpose": {"label": "Свободное назначение"},
  "status": {"label": "Свободно"},
  "number": "2 2.8",
  "building_name": "1",
  "floors": [3],
  "individual_plan_image": {...},
  "subway": {...},
  "district": {...}
}
```

---

## 🎯 Итог

### Исправлено:
1. ✅ `getCommercial()` - теперь возвращает правильные данные коммерции
2. ✅ Добавлен `getCommercePremises()` - парсит все 1,772 помещения
3. ✅ Обновлен `parseCommercial()` - парсит помещения вместо комплексов
4. ✅ Нет проблемы с лимитом offset (167 < 344)

### Тестирование:
```bash
php artisan trendagent:parse --region=spb --type=commercial --limit=2000 --save-raw
```

**Результат:**
```
✅ Обработано: 1,774 помещения
✅ Ошибок: 0
✅ Соответствует данным сайта: ~1,771 помещение
```

---

## 📁 Измененные файлы

1. `app/Services/TrendAgent/TrendAgentApiClient.php`
   - Исправлен `getCommercial()`
   - Добавлен `getCommercePremises()`
   - Добавлен `getAuthToken()`

2. `app/Services/TrendAgent/TrendSsoApiAuth.php`
   - Добавлен `getCommercePremises()`

3. `app/Console/Commands/TrendAgent/ParseCommand.php`
   - Обновлен `parseCommercial()` для парсинга помещений

---

## 🔄 Для полного парсинга коммерции:

```bash
# Парсинг всех помещений СПб
php artisan trendagent:parse --region=spb --type=commercial --limit=2000 --save-raw

# Результат: 1,774 помещения в 18 файлах
```
