## ER-диаграмма БД TrendAgent (Conceptual / Logical)

Документ описывает связи между основными сущностями:

- Комплексы (`trendagent_complexes`)
- Подрядчики (`trendagent_contractors`)
- Посёлки (`trendagent_plot_settlements`)
- Квартиры (`trendagent_apartments`)
- Паркинги (`trendagent_parkings`, `trendagent_parking_places`)
- Дома (`trendagent_houses`)
- Участки (`trendagent_plots`)
- Коммерция (`trendagent_commercial`)
- Проекты подрядчиков (`trendagent_contractor_projects`)

Основа — структура из `DB_SCHEMA_UPDATED_PLAN.md`.

---

## 1. Общая схема (высокий уровень)

```text
trendagent_regions
    ├── trendagent_complexes
    │   ├── trendagent_buildings
    │   │   ├── trendagent_sections
    │   │   │   ├── trendagent_floors
    │   │   │   └── trendagent_apartments
    │   │   └── trendagent_floor_plans
    │   ├── trendagent_apartments
    │   ├── trendagent_parkings
    │   │   └── trendagent_parking_places
    │   ├── trendagent_commercial
    │   └── trendagent_nearby_places
    ├── trendagent_houses
    ├── trendagent_plot_settlements
    │   └── trendagent_plots
    └── (подрядчики глобальные, не привязаны напрямую к регионам)

trendagent_contractors
    └── trendagent_contractor_projects

Справочники:
    ├── trendagent_finishing_types → trendagent_apartments
    ├── trendagent_statuses → trendagent_apartments, trendagent_parking_places
    ├── trendagent_parking_types → trendagent_parkings
    ├── trendagent_commercial_types → trendagent_commercial
    ├── trendagent_business_types → trendagent_commercial
    ├── trendagent_balcony_types → trendagent_apartments
    └── trendagent_view_types → trendagent_apartments
```

---

## 2. Комплексы и иерархия здания

### 2.1. Регионы → Комплексы

```text
trendagent_regions (1) ──< (N) trendagent_complexes
```

- `trendagent_regions.id` → PK региона.
- `trendagent_complexes.region_id` → FK на регион.

**Смысл:** каждый комплекс принадлежит одному региону; регион может иметь много комплексов.

---

### 2.2. Комплексы → Корпуса → Секции → Этажи

```text
trendagent_complexes (1) ──< (N) trendagent_buildings
trendagent_buildings (1) ──< (N) trendagent_sections
trendagent_sections  (1) ──< (N) trendagent_floors
```

- `trendagent_buildings.complex_id` → FK на комплекс.
- `trendagent_sections.building_id` → FK на корпус.
- `trendagent_floors.section_id` → FK на секцию.

**Смысл:** иерархия внутри ЖК:

комплекс → корпуса → секции → этажи.

---

### 2.3. Поэтажные планы

```text
trendagent_complexes (1) ──< (N) trendagent_floor_plans
trendagent_buildings (1) ──< (N) trendagent_floor_plans (опционально)
trendagent_sections  (1) ──< (N) trendagent_floor_plans (опционально)
trendagent_floors    (1) ──< (N) trendagent_floor_plans (опционально)
```

- `trendagent_floor_plans.complex_id` (обязателен).
- `trendagent_floor_plans.building_id / section_id / floor_id` (опциональны, уточняют привязку).

**Смысл:** поэтажные планы могут быть на уровне всего комплекса или конкретного корпуса/секции/этажа.

---

## 3. Квартиры

### 3.1. Комплекс / Корпус / Секция / Этаж → Квартира

```text
trendagent_complexes (1) ──< (N) trendagent_apartments
trendagent_buildings (1) ──< (N) trendagent_apartments
trendagent_sections  (1) ──< (N) trendagent_apartments
trendagent_floors    (1) ──< (N) trendagent_apartments
```

- `trendagent_apartments.complex_id` → FK на комплекс (может быть NULL).
- `trendagent_apartments.building_id` → FK на корпус.
- `trendagent_apartments.section_id` → FK на секцию.
- `trendagent_apartments.floor_id` → FK на этаж.

**Смысл:** квартира привязана к комплексу и, при наличии детальной структуры, к корпусу/секции/этажу.

---

### 3.2. Справочники для квартир

```text
trendagent_finishing_types (1) ──< (N) trendagent_apartments
trendagent_statuses        (1) ──< (N) trendagent_apartments
trendagent_balcony_types   (1) ──< (N) trendagent_apartments
trendagent_view_types      (1) ──< (N) trendagent_apartments
```

- `trendagent_apartments.finishing_type_id` → тип отделки.
- `trendagent_apartments.status_id` → статус квартиры (available, booked, etc.).
- `trendagent_apartments.balcony_type_id` → тип балкона.
- `trendagent_apartments.view_type_id` → вид из окна.

**Смысл:** квартира использует несколько справочников, позволяя строить аналитику и фильтры по отделке/статусам/видам.

---

## 4. Паркинги и машиноместа

### 4.1. Комплекс → Паркинги

```text
trendagent_complexes (1) ──< (N) trendagent_parkings
```

- `trendagent_parkings.complex_id` → FK на комплекс.

**Смысл:** в одном комплексе может быть несколько паркингов (подземный, многоуровневый и т.п.).

---

### 4.2. Паркинги → Машиноместа

```text
trendagent_parkings      (1) ──< (N) trendagent_parking_places
trendagent_statuses      (1) ──< (N) trendagent_parking_places
trendagent_parking_types (1) ──< (N) trendagent_parkings
```

- `trendagent_parking_places.parking_id` → FK на паркинг.
- `trendagent_parking_places.status_id` → статус места.
- `trendagent_parkings.parking_type_id` → тип паркинга.

**Смысл:** 
- `trendagent_parkings` — «большой» объект (сам паркинг/паркинг-зона).
- `trendagent_parking_places` — конкретные машиноместа внутри паркинга.

---

## 5. Дома (коттеджи, таунхаусы)

```text
trendagent_regions (1) ──< (N) trendagent_houses
```

- `trendagent_houses.region_id` → FK на регион.

**Смысл:** дом привязан к региону, но не к ЖК (отдельный объект застройки).

Дополнительно возможно использование:

- `trendagent_statuses` для статусов (через `raw_data` или будущее поле).

---

## 6. Посёлки и участки

### 6.1. Регионы → Посёлки

```text
trendagent_regions (1) ──< (N) trendagent_plot_settlements
```

- `trendagent_plot_settlements.region_id` → FK на регион.

**Смысл:** один регион может содержать несколько посёлков (коттеджные посёлки).

---

### 6.2. Посёлки → Участки

```text
trendagent_plot_settlements (1) ──< (N) trendagent_plots
trendagent_regions          (1) ──< (N) trendagent_plots
```

- `trendagent_plots.settlement_id` → FK на посёлок.
- `trendagent_plots.region_id` → FK на регион.

**Смысл:** участок всегда принадлежит одному посёлку и одному региону.  
Дополнительно в `utilities` (JSON) хранятся коммуникации, а в `raw_data` — полный ответ API.

---

## 7. Коммерческая недвижимость

```text
trendagent_complexes        (1) ──< (N) trendagent_commercial
trendagent_commercial_types (1) ──< (N) trendagent_commercial
trendagent_business_types   (1) ──< (N) trendagent_commercial
```

- `trendagent_commercial.complex_id` → комплекс (если помещение внутри ЖК).
- `trendagent_commercial.commercial_type_id` → тип коммерции.
- `trendagent_commercial.business_type_id` → тип бизнеса.

**Смысл:** коммерческое помещение может быть как частью ЖК, так и отдельным объектом, при этом типы/назначения вынесены в справочники.

---

## 8. Подрядчики и проекты домов

### 8.1. Подрядчики

```text
trendagent_contractors (1) ──< (N) trendagent_contractor_projects
```

- `trendagent_contractor_projects.contractor_id` → FK на подрядчика.

**Смысл:** у каждого подрядчика может быть несколько типовых проектов домов.  
Проекты не привязаны к конкретному региону — это «каталог» типовых решений.

---

## 9. Инфраструктура вокруг комплексов

### 9.1. Ближайшие места

```text
trendagent_complexes (1) ──< (N) trendagent_nearby_places
```

- `trendagent_nearby_places.complex_id` → комплекс.

**Смысл:** для каждого комплекса можно хранить набор ближайших объектов (метро, школы, магазины) с типом (`type`) и расстоянием.

---

## 10. Изображения и планы (опциональное нормализованное хранилище)

### 10.1. Изображения

```text
trendagent_images
    ├── (object_type = 'complex',   object_id = trendagent_complexes.id)
    ├── (object_type = 'apartment', object_id = trendagent_apartments.id)
    ├── (object_type = 'house',     object_id = trendagent_houses.id)
    ├── (object_type = 'plot',      object_id = trendagent_plots.id)
    └── (object_type = 'commercial',object_id = trendagent_commercial.id)
```

**Смысл:** для аналитики и индексации можно вынести изображения в отдельную таблицу, но в текущей реализации достаточно JSON полей `images` в основных таблицах.

---

## 11. Справочники (общая схема)

```text
trendagent_finishing_types  (1) ──< (N) trendagent_apartments
trendagent_statuses         (1) ──< (N) trendagent_apartments
trendagent_statuses         (1) ──< (N) trendagent_parking_places
trendagent_parking_types    (1) ──< (N) trendagent_parkings
trendagent_commercial_types (1) ──< (N) trendagent_commercial
trendagent_business_types   (1) ──< (N) trendagent_commercial
trendagent_balcony_types    (1) ──< (N) trendagent_apartments
trendagent_view_types       (1) ──< (N) trendagent_apartments
```

**Смысл:** все типовые значения (отделка, статусы, виды, типы коммерции и т.п.) вынесены в отдельные таблицы, что:

- упрощает расширение без миграций по основным таблицам;
- позволяет строить витрины и фильтры по кодам/группам;
- облегчает локализацию/переименование.

---

## 12. Итоговая диаграмма (сосредоточено на основных типах объектов)

```text
                trendagent_regions
                       │ 1
                       ├─────────────────────────────────────┐
                       │                                     │
              (N) trendagent_complexes               (N) trendagent_houses
                       │                                     │
          ┌────────────┴─────────────┐                       │
          │                          │                       │
   (N) trendagent_buildings   (N) trendagent_apartments      │
          │                          ▲  ▲  ▲                 │
          │                          │  │  │                 │
(N) trendagent_sections      finishing/status/balcony/view   │
          │                          │  │  │                 │
  (N) trendagent_floors              │  │  │                 │
          │                          │  │  │                 │
          └───────────────┬──────────┘  │  │                 │
                          │             │  │                 │
                 (N) trendagent_floor_plans                  │
                                                             │
trendagent_plot_settlements ──< (N) trendagent_plots  <──────┘

trendagent_complexes
    ├── (N) trendagent_parkings ──< (N) trendagent_parking_places
    ├── (N) trendagent_commercial
    └── (N) trendagent_nearby_places

trendagent_contractors ──< (N) trendagent_contractor_projects
```

Этот документ можно использовать как основу для:

- визуальной ER-диаграммы (draw.io, dbdiagram.io, Diagrams.net);
- согласования схемы с аналитиками и архитекторами;
- генерации миграций/ORM моделей в других проектах.

