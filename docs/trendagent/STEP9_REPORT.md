# ШАГ 9 — SQLite Tests, Index Conflicts, Feature Tests

**Дата:** 2026-02-14

## Выполнено

### Конфликты индексов SQLite

В SQLite имена индексов должны быть уникальны во всей БД. Миграция `2026_02_08_235531_add_indexes_to_trendagent_tables.php` использовала `idx_created_at`, `idx_region_id`, `idx_complex_id` на нескольких таблицах, что вызывало ошибку «index already exists».

**Исправление:** уникальные имена на таблицу:
- `idx_ta_apt_created_at` (apartments)
- `idx_ta_cplx_created_at`, `idx_ta_cplx_region_id` (complexes)
- `idx_ta_park_created_at`, `idx_ta_park_complex_id` (parkings)
- `idx_ta_house_created_at`, `idx_ta_house_region_id` (houses)
- `idx_ta_plot_created_at`, `idx_ta_plot_region_id` (plots)
- `idx_ta_comm_created_at`, `idx_ta_comm_complex_id` (commercial)

### Feature-тесты

- **cities**, **authenticate** — всегда выполняются (не зависят от БД TrendAgent)
- **apartments list**, **apartment detail** — используют `RefreshDatabase` + `TrendAgentTestSeeder`
- При отсутствии таблиц TrendAgent — `markTestSkipped`

### TrendAgentTestSeeder

Добавлен сидер: Region (spb), Complex, Apartment для тестов.

### Новые тесты

| Тест | Описание |
|------|----------|
| test_apartments_list_image_url_present | В объектах списка есть plan_image_url и plan_image |
| test_apartments_list_filter_by_price | Фильтр price_from/price_to ограничивает результаты |
| test_apartments_list_filter_by_rooms | Фильтр room возвращает только квартиры с указанным количеством комнат |
