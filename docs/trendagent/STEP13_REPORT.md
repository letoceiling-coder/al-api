# ШАГ 13 — Sync Runs + Health

**Дата:** 2026-02-15

## SyncRun

Импорт создаёт запись SyncRun на каждый запуск (region + type):
- started_at, finished_at
- status: running | success | failed
- created_count, updated_count, skipped_count, error_count
- duration_ms
- error_summary (при failed)

## Health endpoint

**GET /api/trendagent/v1/health** — без авторизации

Ответ:
```json
{
  "ok": true,
  "last_sync_runs": [...],
  "counts": {
    "apartments": { "active": N, "total": M },
    "complexes": { "active": N, "total": M },
    "parkings": { "active": N, "total": M }
  },
  "version": "abc1234"
}
```
