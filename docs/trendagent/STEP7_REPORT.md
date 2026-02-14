# ШАГ 7 — Contract Validator

**Дата:** 2026-02-14

## Реализовано

### Команда trendagent:contract-check

Проверяет соответствие ответов DB API контракту (db_api_contract.md).

**Использование:**
```bash
# HTTP-запросы к app.url
php artisan trendagent:contract-check

# Внутренний вызов контроллеров (для CI, без HTTP-сервера)
php artisan trendagent:contract-check --internal

# Указать base URL
php artisan trendagent:contract-check --base-url=https://api.example.com

# JSON вывод
php artisan trendagent:contract-check --json
```

### Проверяемые endpoints

| Endpoint | Метод | Описание |
|----------|-------|----------|
| cities | GET /api/trendagent/v1/cities | success, data[].id, data[].name |
| objects-list | POST /api/trendagent/v1/objects/list | success, data.objects, total_count, pagination.has_more |
| apartments-list | POST /api/trendagent/v1/apartments | success, data.objects, total_count, pagination |
| apartment-detail | POST /api/trendagent/v1/apartments/{id}/flat/{apartmentId} | success, data.id, data._id, data.number, data.floor |

### Сравнение структур

- **missing keys** — отсутствующие обязательные поля
- **type mismatch** — несоответствие типа (string, integer, boolean, array)
- **extra keys** — не проверяются (допускаются)

### Exit code

- 0 — контракт соблюдён
- 1 — обнаружены несоответствия
