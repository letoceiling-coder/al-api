# TrendAgent Production State

**Last update:** 2026-02-15

## Production Freeze Tag

| Параметр | Значение |
|----------|----------|
| **Production Tag** | `v1.0-trendagent-db-stable` |
| **Commit** | `625c47e` |
| **Дата** | 2026-02-15 |

### Создание и пуш тега (локально)

```bash
git rev-parse HEAD   # проверить текущий commit
git tag v1.0-trendagent-db-stable
git push origin v1.0-trendagent-db-stable
```

---

## Manual edits policy

- **Запрещено:** вручную править файлы UI (шаблоны, стили, public/trendagent) на сервере
- **Разрешено:** только деплой через git pull + build + optimize:clear
- Все изменения вносить локально, коммитить, затем деплоить через `php artisan deploy:trendagent`

## Синхронизация локальный ↔ сервер

- **Принцип:** Локальный и серверный код должны быть идентичны (одинаковый commit из `origin/main`).
- **Процедура:** см. [SYNC_LOCAL_SERVER.md](SYNC_LOCAL_SERVER.md) (если есть)

## Current state

- **Commit:** 625c47e
- **Tag:** v1.0-trendagent-db-stable
- **TRENDAGENT_DATA_SOURCE:** db
- **Server:** root@89.169.39.244
- **Project:** /var/www/AL
- **Domain:** https://api.siteaccess.ru

## Cron (рекомендуемое расписание)

| Задача | Расписание | Лог |
|--------|------------|-----|
| Parse (все регионы) | `0 0 * * *` (ежедневно в 0:00) | trendagent_parse.log |
| Import | `0 */6 * * *` (каждые 6 ч) | trendagent_cron.log |
| Images GC | `0 3 * * 0` (Вс 3:00) | trendagent_gc.log |
| Health check | `*/10 * * * *` | trendagent_health_alert.log (при fail) |

## Endpoints

- GET /api/trendagent/v1/health (публичный)
- GET /api/trendagent/v1/cities
- POST /api/trendagent/v1/apartments (body: city, count)
- POST /api/trendagent/v1/objects/list (body: object_type, city)

## Logs

- storage/logs/trendagent_parse.log — парсер (все регионы)
- storage/logs/trendagent_parse_timing.json — замеры длительности парсинга
- storage/logs/trendagent_cron.log
- storage/logs/trendagent_gc.log
- storage/logs/trendagent_health_alert.log
- storage/logs/laravel.log
