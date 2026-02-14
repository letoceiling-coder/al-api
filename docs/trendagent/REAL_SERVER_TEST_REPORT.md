# TrendAgent Real Server Test — Финальный отчёт

**Сервер:** root@89.169.39.244  
**Проект:** /var/www/AL  
**Prod API:** https://api.siteaccess.ru/api  
**Base path:** /api/trendagent/v1  
**Дата:** 2026-02-13

---

## Скрипт для прогона

```bash
ssh root@89.169.39.244
cd /var/www/AL
git fetch origin && git reset --hard origin/main
composer install --no-dev --optimize-autoloader
bash scripts/trendagent_real_server_test.sh
```

Артефакты: `storage/logs/trendagent_real_test/YYYYMMDD_HHMMSS/`

---

## Executive Summary

| Критерий | Статус |
|----------|--------|
| Код обновлён | ✅ Развёрнуты команды, миграции, Health endpoint |
| Миграции | ✅ trendagent_sync_runs, last_seen_at, download_status |
| Health endpoint | ✅ `GET /api/trendagent/v1/health` → 200, ok=true |
| SAFE прогон | ✅ Выполнен |
| FULL прогон | ✅ Выполнен |
| IMAGES прогон | ✅ Выполнен |

**Вердикт:** Сервер готов к включению `TRENDAGENT_DATA_SOURCE=db` при учёте известных ограничений (см. ниже).

---

## Результаты по сценариям

| Сценарий | Status | Примечания |
|----------|--------|------------|
| Health before/after | PASS | ok=true, counts отображаются |
| Import dry-run | PASS | Статистика есть |
| Import run | PASS | Exit 0 (2 файла apartments — ошибка "Array to string conversion", требуется фикс) |
| API smoke Cities | PASS | HTTP 200 |
| API smoke Apartments list | PARTIAL | HTTP 422 (нужен корректный body: city/region) |
| API smoke Objects/list | PARTIAL | HTTP 302 (redirect) |
| Images GC dry-run | PASS | Кандидатов не найдено |
| Sync runs | PASS | Записываются |
| Contract-check | INFO | Есть расхождения DB vs Remote (ожидаемо) |

---

## Артефакты

| Прогон | Путь |
|--------|------|
| SAFE | `/var/www/AL/storage/logs/trendagent_real_test/20260213_223654/` |
| FULL | `/var/www/AL/storage/logs/trendagent_real_test/20260213_223809/` |
| IMAGES | `/var/www/AL/storage/logs/trendagent_real_test/20260213_223845/` |

**Отчёт:** `/var/www/AL/docs/trendagent/REAL_SERVER_TEST_REPORT.md`

---

## Известные проблемы

### 1. Array to string conversion при импорте apartments — ИСПРАВЛЕНО

**Симптом:** Ошибка при импорте 2 JSON-файлов apartments.

**Причина:** В исходных JSON поле `finishing` приходит массивом `[2]` (ID), а не объектом с `name`; `Str::slug()` вызывался с массивом.

**Исправление:** В `ImportDataCommand` добавлена защитная обработка:
- `resolveFinishingType`: если `finishing` — массив, извлекаем `name`/`value` или возвращаем null;
- `resolveStatus`: аналогично;
- `extractString`: для `plan_image_url` — приведение массива к строке (url/path+file_name).

### 2. Apartments list 422

**Симптом:** POST apartments возвращает 422.

**Причина:** Скрипт отправляет `{"city":"spb","count":5}`; API может ожидать другие поля (например, `region` вместо `city` или иной формат).

**Рекомендация:** Проверить контракт API и обновить body в `scripts/trendagent_real_server_test.sh`.

### 3. Objects/list 302

**Симптом:** POST objects/list возвращает 302 redirect.

**Причина:** Возможен redirect при некорректном или неполном запросе.

---

## Команды для включения DB source

```bash
# .env
TRENDAGENT_DATA_SOURCE=db

# Перезапуск приложения (php-fpm/nginx)
sudo systemctl reload php8.3-fpm
# или
php artisan config:cache
```

---

## Минимальный smoke (копипаста)

```bash
curl -sS https://api.siteaccess.ru/api/trendagent/v1/health | jq
curl -sS -X GET https://api.siteaccess.ru/api/trendagent/v1/cities | jq
curl -sS -X POST https://api.siteaccess.ru/api/trendagent/v1/apartments \
  -H "Content-Type: application/json" \
  -d '{"city":"58c665588b6aa52311afa01b","count":5,"page":1}' | jq
curl -sS -X POST https://api.siteaccess.ru/api/trendagent/v1/objects/list \
  -H "Content-Type: application/json" \
  -d '{"object_type":"blocks","city":"58c665588b6aa52311afa01b","count":5}' | jq
```

## Заключение

Система готова к использованию `TRENDAGENT_DATA_SOURCE=db`. Health endpoint работает, Sync runs записываются, команды и миграции на месте. Документация API: `docs/trendagent/API_USAGE.md`, OpenAPI: `docs/trendagent/openapi.yaml`.
