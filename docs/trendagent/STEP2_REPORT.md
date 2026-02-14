# ШАГ 2. ОТЧЁТ ПО МИГРАЦИЯМ

**Дата:** 2026-02-14  
**Цель:** Добавить недостающие колонки и таблицы для контракта и импорта.

---

## 1. Добавленные миграции

| Миграция | Описание |
|----------|----------|
| `2026_02_14_120000_add_region_id_to_trendagent_apartments` | region_id + индексы для apartments |
| `2026_02_14_120001_add_metadata_to_trendagent_images` | mime, size, hash, download_status, downloaded_at в trendagent_images |
| `2026_02_14_120002_create_trendagent_sync_runs_table` | Таблица отчётов импорта |

---

## 2. Поля и индексы

### 2.1. trendagent_apartments.region_id

**Зачем:** Контракт (db_api_contract.md) требует фильтрацию по city/region. CityService: city.id ↔ region.code.

**Поля:** `region_id` (FK → trendagent_regions, nullable, onDelete set null).

**Индексы:**
- `idx_apt_region_price` — (region_id, price_base) — фильтры price_from/price_to
- `idx_apt_region_rooms` — (region_id, rooms) — фильтр room[]
- `idx_apt_region_area` — (region_id, area_total) — фильтры area_from/area_to

**Ссылка на контракт:** раздел 4 «Фильтры», city — обязательный параметр.

---

### 2.2. trendagent_images — метаданные

**Зачем:** ImportDataCommand сохраняет mime, size, hash, download_status, downloaded_at. Контракт image_url: при local_path — Storage::url(local_path), иначе — оригинальный URL.

| Поле | Тип | Описание |
|------|-----|----------|
| mime | varchar(100) nullable | MIME-тип |
| size | bigint unsigned nullable | Размер в байтах |
| hash | varchar(64) nullable | SHA1 для дедупликации |
| download_status | varchar(20) default 'pending' | none\|pending\|ready\|failed |
| downloaded_at | timestamp nullable | Время скачивания |

**Индексы:** `idx_trendagent_images_hash`, `idx_trendagent_images_dl_status`.

---

### 2.3. trendagent_sync_runs

**Зачем:** ImportDataCommand создаёт/обновляет SyncRun для отчётов. Health/last_sync может показывать последние запуски.

| Поле | Тип | Описание |
|------|-----|----------|
| region | string(50) | spb, msk, … |
| type | string(50) | apartments, complexes, parkings, … |
| started_at, finished_at | timestamp | |
| status | string(20) | running, success, failed |
| created_count, updated_count, skipped_count, error_count | int | |
| duration_ms | int | |
| flags | json | download_images, batch, … |
| error_summary | text | |

**Индексы:** region, type, started_at, status, (region, type, started_at).

---

## 3. SQLite-совместимость

- Новые миграции не используют `GENERATED ALWAYS AS`.
- Имена индексов уникальные: `idx_apt_*`, `idx_trendagent_images_*`, `idx_sync_runs_*`.
- `dropColumn` вызывается по одному в цикле (add_metadata down).

---

## 4. Команды проверки

```bash
php artisan migrate
php artisan migrate:status
# Откат (при необходимости):
# php artisan migrate:rollback --step=3
```
