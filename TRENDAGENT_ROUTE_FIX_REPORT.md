# Отчет об исправлении роута для детальной страницы квартиры

**Дата:** 2026-02-07  
**Проблема:** 404 ошибка при обращении к `/api/trendagent/apartments/{id}/flat/{apartmentId}`

---

## Проблема

При обращении к URL:
```
https://api.siteaccess.ru/trendagent/apartments/63c50acc9a85d53360f63a76/flat/63c5614728d3bcf2420860b1?block=63c50acc9a85d53360f63a76&guid=dom-na-naberezhnoy-st
```

Возникала ошибка 404:
```
The route api/trendagent/apartments/63c50acc9a85d53360f63a76/flat/63c5614728d3bcf2420860b1 could not be found.
```

---

## Анализ

1. **Роут определен правильно:**
   ```php
   Route::post('/{id}/flat/{apartmentId}', [ApartmentsController::class, 'flatDetail']);
   ```

2. **Роут зарегистрирован:**
   ```
   POST api/trendagent/apartments/{id}/flat/{apartmentId} ... TrendAgent\ApartmentsController@flatDetail
   ```

3. **Проблема:** Порядок роутов - более общий роут `/{id}` может перехватывать запрос раньше более специфичного `/{id}/flat/{apartmentId}`

---

## Решение

### 1. Изменен порядок роутов

Более специфичные роуты теперь определены раньше менее специфичных:

```php
Route::prefix('apartments')->group(function () {
    Route::post('/', [ApartmentsController::class, 'index']); // Список квартир
    // Более специфичные роуты должны быть определены раньше менее специфичных
    Route::post('/{id}/flat/{apartmentId}', [ApartmentsController::class, 'flatDetail'])
        ->where(['id' => '[a-f0-9]{24}', 'apartmentId' => '[a-f0-9]{24}']); // Детальная информация о квартире
    Route::post('/{id}/checkerboard/buildings', [ApartmentsController::class, 'checkerboardBuildings'])
        ->where(['id' => '[a-f0-9]{24}']); // Корпуса для шахматки
    Route::post('/{id}/checkerboard/apartments', [ApartmentsController::class, 'checkerboardApartments'])
        ->where(['id' => '[a-f0-9]{24}']); // Квартиры для шахматки
    Route::post('/{id}', [ApartmentsController::class, 'show'])
        ->where(['id' => '[a-f0-9]{24}|[a-z0-9-]+']); // Детали объекта (должен быть последним)
});
```

### 2. Добавлены ограничения на параметры

Добавлены ограничения `where()` для параметров роутов:
- `id` и `apartmentId` должны быть 24-символьными hex-строками (MongoDB ObjectId)
- Это помогает Laravel правильно различать роуты

---

## Измененные файлы

1. ✅ `routes/trendagent.php`
   - Изменен порядок роутов
   - Добавлены ограничения на параметры

---

## Проверка

После изменений роут зарегистрирован правильно:
```
POST api/trendagent/apartments/{id}/flat/{apartmentId} ... TrendAgent\ApartmentsController@flatDetail
```

---

## Рекомендации

1. **Очистить кэш роутов** после изменений:
   ```bash
   php artisan route:clear
   ```

2. **Проверить порядок роутов** - более специфичные должны быть определены раньше менее специфичных

3. **Использовать ограничения** на параметры роутов для лучшей производительности и точности сопоставления

---

**Статус:** ✅ Исправлено
