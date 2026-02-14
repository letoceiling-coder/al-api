# ШАГ 3. ОТЧЁТ ПО МОДЕЛЯМ И SCOPES

**Дата:** 2026-02-14  
**Цель:** Связи, scopes по контракту, eager loading.

---

## 1. Диаграмма связей (текст)

```
Region
  ├── hasMany Complex
  ├── hasMany Apartment (через region_id)
  ├── hasMany House
  ├── hasMany PlotSettlement
  └── hasMany Plot

Complex
  ├── belongsTo Region
  ├── hasMany Building
  ├── hasMany Apartment
  ├── hasMany Parking
  ├── hasMany Commercial
  ├── hasMany FloorPlan
  ├── hasMany NearbyPlace
  └── morphMany TrendAgentImage (imageable)

Apartment
  ├── belongsTo Region
  ├── belongsTo Complex
  ├── belongsTo Building
  ├── belongsTo Section
  ├── belongsTo Floor
  └── (TrendAgentImage через object_type=apartment)

TrendAgentImage
  └── morphTo imageable (object_type, object_id)

Contractor
  └── hasMany ContractorProject

SyncRun — отдельная таблица, без связей
```

---

## 2. Список scopes

### Apartment

| Scope | Параметры | Описание |
|-------|-----------|----------|
| scopeRegion | int\|string $region | region_id или region.code |
| scopePriceBetween | ?int $from, ?int $to | price_from, price_to |
| scopeAreaBetween | ?float $from, ?float $to | area_from, area_to |
| scopeRoomsIn | array $rooms | room[] |
| scopeSort | string $sort, string $order | price\|area\|deadline\|name, asc\|desc |

### Complex

| Scope | Параметры | Описание |
|-------|-----------|----------|
| scopeRegion | int\|string $region | region_id или code |
| scopeSort | string $sort, string $order | price\|deadline\|name |

---

## 3. TrendAgentImage accessor

```php
getImageUrlAttribute(): string
// local_path заполнен → Storage::url(local_path)
// иначе → url
```

`$appends = ['image_url']` — добавляется в JSON/array.

---

## 4. Eager loading для API

При выборке списков/деталей:

```php
// Список apartments
Apartment::with(['region', 'complex', 'complex.region'])
    ->region($regionCode)
    ->priceBetween($priceFrom, $priceTo)
    ->areaBetween($areaFrom, $areaTo)
    ->roomsIn($rooms)
    ->sort($sort, $sortOrder)
    ->paginate($count);

// Детали complex
Complex::with([
    'region',
    'buildings',
    'apartments',
    'parkings',
    'commercial',
    'nearbyPlaces',
    'floorPlans',
    'images',
])
    ->findOrFail($id);
```

---

## 5. Замечание: importContractor $now

В `ImportDataCommand::importContractor()` используется `$now` без объявления. Фикс — в ШАГЕ 4 (импорт).
