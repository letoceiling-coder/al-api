# ШАГ 12 — Images GC

**Дата:** 2026-02-15

## TrendAgentImage

- **download_status**: none|pending|ready|failed
- **downloaded_at** — время скачивания
- При успехе: status=ready, downloaded_at=now()
- При ошибке: status=failed

## Команда GC

```bash
php artisan trendagent:images:gc --days=30 [--dry-run]
```

- Удаляет локальные файлы (local_path), не использовавшиеся N дней
- Критерий: `downloaded_at < now() - days` или `downloaded_at IS NULL`
- После удаления: `local_path = null`, `download_status = none`
- `--dry-run` — только вывод списка файлов без удаления
