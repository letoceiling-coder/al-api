# ШАГ 8 — Performance (N+1, Debug Query)

**Дата:** 2026-02-14

## Выполнено

### N+1 устранён

- **listComplexes** — использован `apartments_count` из `withCount('apartments')`, убран вызов `$c->apartments()->count()` в цикле.
- **show (complex detail)** — `$complex->apartments->count()` вместо `$complex->apartments()->count()` (apartments уже подгружены через `with(['apartments'])`).

### Eager loading

- Apartments list: `with('complex')` — уже было.
- Complex show: `with(['buildings', 'apartments', 'parkings', 'nearbyPlaces', 'floorPlans'])` — уже было.
- Apartment flatDetail: `with(['complex', 'building'])` — уже было.

### images limit(2) на list

- **ApartmentListResource** — `formatImages()` ограничивает до 2 изображений: `array_slice($images, 0, $limit)`.
- **ComplexResource** — `resolveImages(2)` ограничивает до 2 элементов.

### TRENDAGENT_DEBUG_QUERY

- **config/trendagent.php** — добавлен `debug_query`.
- **TrendAgentDebugQueryMiddleware** — при `TRENDAGENT_DEBUG_QUERY=1` добавляет заголовки:
  - `X-TrendAgent-Queries` — число SQL-запросов
  - `X-TrendAgent-QueryTime` — время в мс

**Использование:**
```env
TRENDAGENT_DEBUG_QUERY=1
```
