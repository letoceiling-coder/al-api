# ШАГ 4. ОТЧЁТ ПО ИМПОРТУ JSON → БД

**Дата:** 2026-02-14  
**Цель:** Идемпотентный импорт всех сущностей, SyncRuns, нормализация данных.

---

## 1. Что добавлено в ImportDataCommand

### 1.1. Нормализаторы

- **normalizePlanImageUrl(array $data): ?string** — извлекает URL плана из `plan_image`, `plan_image_url` (array/object/string). Предотвращает "Array to string conversion".

### 1.2. Импортируемые сущности

| Тип | Источник | Ключ upsert | Вложенные |
|-----|----------|-------------|-----------|
| complexes | details/complexes/*.json | external_id | buildings, nearby_places, floor_plans, images |
| apartments | details/apartments/*.json | external_id | images |
| parkings | details/parkings/*.json | external_id | images |
| parking_places | raw/parkings, details | (parking_id, external_id) | — |
| houses | details/houses/*.json | external_id | images |
| plots | details/plots/*.json | external_id | — |
| commercial | details/commercial/*.json | external_id | images |
| contractors | details/contractors/*.json | external_id | contractor_projects, images |

### 1.3. Идемпотентность

- **updateOrCreate** по `external_id` для основных сущностей.
- **firstOrCreate** / **updateOrCreate** для вложенных (buildings, nearby_places, parking_places, floor_plans).
- **syncImagesForEntity**: поиск по (object_type, object_id, url) — обновление при наличии, создание при отсутствии.

### 1.4. SyncRuns

- Создаётся запись при старте импорта (region, type, started_at, status=running).
- По завершению: finished_at, created_count, updated_count, skipped_count, error_count, duration_ms, status (success/failed).
- При ошибке: error_summary (до 1000 символов).

---

## 2. Миграции (Step 2 + Step 4)

- `add_region_id_to_trendagent_apartments`
- `add_metadata_to_trendagent_images`
- `create_trendagent_sync_runs_table`
- `add_last_seen_and_is_active_to_trendagent_tables` — last_seen_at, is_active, region_id (parkings, commercial)

---

## 3. Опции команды

| Опция | По умолчанию | Описание |
|-------|--------------|----------|
| --region | spb | Код региона |
| --type | all | all, apartments, parkings, houses, plots, commercial, complexes, contractors |
| --dry-run | false | Без сохранения в БД |
| --batch | 500 | Размер пачки |
| --fail-fast | 0 | Остановиться при первой ошибке пачки |
| --download-images | 0 | Скачивать изображения |
| --images-disk | public | Диск для изображений |
| --images-dir | trendagent | Подпапка |
| --timeout | 30 | Таймаут HTTP (сек) |
| --max-image-size-mb | 25 | Макс. размер изображения |
| --retries | 2 | Повторы скачивания |
| --deactivate-missing | 0 | Деактивировать записи без last_seen |
| --missing-days | 7 | Дней без last_seen для деактивации |

---

## 4. Порядок импорта

При `--type=all`:
1. complexes
2. apartments
3. parkings
4. parking_places
5. houses
6. plots
7. commercial
8. contractors

Комплексы импортируются первыми (для связей block_id → complex_id).
