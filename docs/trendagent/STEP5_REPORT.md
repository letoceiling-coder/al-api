# ШАГ 5. ОТЧЁТ ПО ИЗОБРАЖЕНИЯМ И ЗАПУСКУ

**Дата:** 2026-02-14  
**Цель:** Хранение изображений, опции, примеры запуска.

---

## 1. TrendAgentImageStoreService

### 1.1. Конструктор

```php
new TrendAgentImageStoreService(
    ?string $disk = null,      // default: public
    ?string $baseDir = null,   // default: trendagent
    ?int $timeoutSeconds = null,  // default: 30
    ?int $maxSizeMb = null,    // default: 25
    ?int $retries = null       // default: 2
);
```

### 1.2. Методы

- **storeFromUrl(url, entityType, entityId, orderIndex?)** — скачивает изображение, проверяет размер, вычисляет hash, сохраняет в `{baseDir}/{entityType}/{entityId}/{hash}.{ext}`. Дедупликация по существующему файлу.
- **buildPath(entityType, entityId, hash, ext)** — формирует путь.
- **normalizeUrl(urlOrPath)** — path + file_name → полный URL.
- **buildUrlFromPathAndFile(path, fileName)** — URL из TrendAgent-формата.

### 1.3. Возврат

`['local_path' => string|null, 'mime' => string|null, 'size' => int|null, 'hash' => string|null]`

---

## 2. Интеграция в импорт

- При `--download-images=1`: для каждого изображения вызывается `storeFromUrl`.
- В `TrendAgentImage` сохраняются: url, local_path, mime, size, hash, download_status (ready|failed|pending), downloaded_at.
- Accessor `image_url`: при local_path — `Storage::url(local_path)`, иначе — оригинальный url.

---

## 3. Примеры запуска

### Dry run (без сохранения)
```bash
php artisan trendagent:import-data --region=spb --type=apartments --dry-run
```

### Импорт без изображений
```bash
php artisan trendagent:import-data --region=spb --type=all --download-images=0
```

### Импорт с изображениями
```bash
php artisan trendagent:import-data --region=spb --type=apartments --download-images=1 --timeout=20 --retries=2 --max-image-size-mb=25
```

### Один тип, батч и fail-fast
```bash
php artisan trendagent:import-data --region=spb --type=complexes --batch=100 --fail-fast=1
```

### Деактивация отсутствующих
```bash
php artisan trendagent:import-data --region=spb --type=all --deactivate-missing=1 --missing-days=7
```

---

## 4. Типовые проблемы

| Проблема | Решение |
|----------|---------|
| Директория с данными не найдена | Запустить парсинг: `php artisan trendagent:parse --region=spb --type=all --details --save-raw` |
| Ошибка "Array to string conversion" | Использовать `normalizePlanImageUrl`, `resolveFinishingType`, `resolveStatus` — они обрабатывают array/object |
| Дубликаты при повторном импорте | Использовать updateOrCreate по external_id; SyncImagesForEntity — по (object_type, object_id, url) |
| Изображения не скачиваются | Проверить SSL, таймаут, `--download-images=1` |
| SyncRun не создаётся | Убедиться, что таблица trendagent_sync_runs существует, миграции выполнены |
