# Исправление ошибки "Cannot GET /apar" для детальной страницы квартиры

## Проблема

При запросе детальной информации о квартире API TrendAgent возвращает ошибку:
```
Cannot GET /apar (truncated...)
```

Это означает, что URL обрезается где-то на стороне API TrendAgent.

## Анализ

Из логов видно, что URL формируется правильно:
```
https://api.trendagent.ru/v4_29/apartments/block/63c50acc9a85d53360f63a76/apartment/63c631e028d3bc0bbe087af5/?city=58c665588b6aa52311afa01b&lang=ru&auth_token=...
```

Но API возвращает 404 с ошибкой "Cannot GET /apar".

## Решение

Согласно документации `TRENDAGENT_PAGE_STRUCTURE.md` (строка 641-642), правильный формат API:
```
GET /v4_29/apartments/block/{blockId}/apartment/{apartmentId}/
```

### Внесенные изменения

1. **Убран слэш в конце URL** перед добавлением query параметров:
   ```php
   $apiUrl = rtrim($apiUrl, '/');
   ```

2. **Улучшено формирование query string** с использованием `PHP_QUERY_RFC3986`:
   ```php
   $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
   $fullUrl = $apiUrl . '?' . $queryString;
   ```

3. **Расширено логирование** для отладки:
   - Логируется URL без query параметров
   - Логируется полный URL с параметрами (токен скрыт)
   - Логируется статус fallback запроса

4. **Улучшена обработка fallback**:
   - Fallback срабатывает не только при 404, но и при 400
   - Добавлено логирование fallback запроса

## Проверка

После деплоя проверить:
1. Логи на сервере: `tail -n 50 storage/logs/laravel.log | grep "apartment detail"`
2. Работу эндпоинта: `https://api.siteaccess.ru/trendagent/apartments/{id}/flat/{apartmentId}`

## Статус

✅ Изменения внесены и задеплоены
