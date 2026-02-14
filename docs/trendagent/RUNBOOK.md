# TrendAgent RUNBOOK

## Production Hardening (шаги 10–13)

### Импорт (batch + transactions + retries)

```bash
# Dry-run (без записи в БД и без скачивания файлов)
php artisan trendagent:import-data --region=spb --type=all --dry-run

# Импорт без изображений
php artisan trendagent:import-data --region=spb --type=all --download-images=0 --batch=500

# Импорт с изображениями (timeout, retries, max-size)
php artisan trendagent:import-data --region=spb --type=apartments --download-images=1 \
  --timeout=20 --retries=2 --max-image-size-mb=25

# С деактивацией отсутствующих (is_active=false для last_seen_at < N дней)
php artisan trendagent:import-data --region=spb --type=all --deactivate-missing=1 --missing-days=7
```

**Опции:**
- `--batch=500` — размер пачки (транзакция на пачку)
- `--fail-fast=1` — остановиться при первой ошибке пачки
- `--timeout=15` — таймаут HTTP для скачивания (сек)
- `--retries=2` — повторные попытки скачивания
- `--max-image-size-mb=25` — макс. размер изображения (MB)
- `--download-images=0|1` — скачивать изображения
- `--images-disk=public` — диск для изображений
- `--images-dir=trendagent` — подпапка
- `--dry-run` — только статистика, без записи и скачивания
- `--deactivate-missing=0|1` — деактивировать записи, не встречавшиеся N дней
- `--missing-days=7` — дней без last_seen для деактивации

### Images GC

```bash
# Dry-run — показать, что будет удалено
php artisan trendagent:images:gc --days=30 --dry-run

# Выполнить GC (удалить неиспользовавшиеся N дней)
php artisan trendagent:images:gc --days=30
```

### Health endpoint

```bash
curl -s https://your-domain/api/trendagent/v1/health | jq
```

Ответ: `ok`, `last_sync_runs`, `counts` (active/total), `version`.

### Sync Runs

Каждый импорт создаёт запись в `trendagent_sync_runs` (region, type, status, counts).

### Актуальность данных (is_active, last_seen_at)

- При upsert: `last_seen_at=now()`, `is_active=true`
- С `--deactivate-missing=1`: записи с `last_seen_at < now - missing-days` помечаются `is_active=false`
- API по умолчанию фильтрует `is_active=true`; `include_inactive=1` — выдаёт всё

---

## Contract Check

```bash
php artisan trendagent:contract-check --internal
```

---

## Cron и мониторинг

### Рекомендуемое расписание на сервере

```bash
# Crontab (crontab -e)
# Import каждые 6 часов
0 */6 * * * cd /var/www/AL && php artisan trendagent:import-data --region=spb --type=all --download-images=0 --deactivate-missing=1 --missing-days=7 >> storage/logs/trendagent_cron.log 2>&1

# Images GC раз в неделю (воскресенье 3:00)
0 3 * * 0 cd /var/www/AL && php artisan trendagent:images:gc --days=30 >> storage/logs/trendagent_gc.log 2>&1

# Health check каждые 10 минут (опционально)
*/10 * * * * curl -sf -o /dev/null -w "%{http_code}" https://api.siteaccess.ru/api/trendagent/v1/health || echo "$(date): health fail" >> /var/www/AL/storage/logs/trendagent_health_alert.log
```

### Логи

- `storage/logs/trendagent_cron.log` — вывод import
- `storage/logs/trendagent_gc.log` — вывод images:gc
- `storage/logs/trendagent_health_alert.log` — сбои health check

---

## Реактивация при пустом UI (Найдено комплексов: 0)

Если UI показывает "Объекты не найдены" при наличии данных в БД — вероятно записи помечены `is_active=false` (deactivate-missing). Реактивировать:

```bash
# Сначала dry-run
php artisan trendagent:reactivate-inactive --dry-run

# Реактивировать complexes, apartments, parkings
php artisan trendagent:reactivate-inactive

# Только комплексы
php artisan trendagent:reactivate-inactive --tables=complexes
```

После реактивации обновить кеш: `php artisan config:cache`

---

## Emergency rollback procedure

При критической регрессии после деплоя:

```bash
cd /var/www/AL

# Откат на стабильный tag
git fetch origin --tags
git reset --hard v1.0-trendagent-db-stable

# Очистка кешей
php artisan optimize:clear
php artisan config:cache

# Перезапуск PHP-FPM (если нужно)
# sudo systemctl reload php8.3-fpm
```

После отката — зафиксировать инцидент и причину в PRODUCTION_FINAL_REPORT.md.
