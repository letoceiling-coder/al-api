# ШАГ 11 — Актуальность данных

**Дата:** 2026-02-15

## Поля

- **last_seen_at** (timestamp nullable) — время последнего появления в источнике
- **is_active** (bool default true) — актуальна ли запись

Таблицы: apartments, complexes, parkings, houses, plots, plot_settlements, commercial, contractor_projects.

## Импорт

При каждом upsert:
- `last_seen_at = now()`
- `is_active = true`

## Опции

- `--deactivate-missing=1` — деактивировать записи, не встречавшиеся N дней
- `--missing-days=7` — порог в днях

При включении deactivate-missing:
- Записи с `last_seen_at < now() - N дней` помечаются `is_active = false`
- Данные не удаляются

## API

- По умолчанию: только `is_active = true`
- `include_inactive=1` — выдавать все записи
