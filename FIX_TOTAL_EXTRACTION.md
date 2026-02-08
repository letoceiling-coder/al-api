# Исправление извлечения total из API

## Проблема

Метод `getBlocksSearch()` возвращал `'total' => count($processedResults)`, что неправильно - это количество обработанных результатов на странице, а не общее количество из API.

## Исправление

Изменил возвращаемое значение `total` на использование `blocksCount` из ответа API:

```php
'total' => $data['data']['blocksCount'] ?? count($processedResults), // Используем blocksCount из API
```

Также добавлена обработка параметра `room` в метод `executeApartmentsSearchRequest()` для правильного формирования URL с `room=30&room=40` для домов.

## Ожидаемый результат

После исправления парсер должен показывать правильные значения:
- Комплексы: 352 (из blocksCount)
- Квартиры: 55,551 (из total API)
- Паркинги: 3,644 (из total API)
- Дома: 1,023 (из total API с фильтром room)
- Участки: 2,370 (из plots_count)
- Коммерция: 1,775 (из total API)
- Подрядчики: 149 (из total API)
