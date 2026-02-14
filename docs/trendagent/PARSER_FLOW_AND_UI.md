# TrendAgent: Парсер и UI — что собирается и что отображается

## Почему /objects/table и /objects/plans пустые

| Страница | Источник данных | API | Когда заполняется |
|----------|-----------------|-----|-------------------|
| `/trendagent` (список) | Комплексы (blocks) | `objects/list` object_type=blocks | После парсинга **complexes** |
| `/objects/table` | Квартиры | `apartments` | После парсинга **apartments** |
| `/objects/plans` | Квартиры | `apartments` | После парсинга **apartments** |

**Причина:** Парсер обрабатывает регионы по порядку: **msk → spb → krd → rnd → ...**  
При обрыве SSH он останавливается. Успели спарсить только **complexes для msk** (5 штук).  
**Apartments** (квартиры) ещё не дошли — парсер не добрался до `parseApartments()`.  
Фильтр по умолчанию: **city=58c665588b6aa52311afa01b (СПб)**. Даже квартиры msk не покажутся при выборе СПб.

---

## Порядок парсинга (для каждого региона)

```
Для каждого региона (msk, spb, krd, rnd, crimea, kzn, ufa, ekb, nsk, dubai):
  1. complexes   — комплексы (блоки) → список на /trendagent
  2. contractors
  3. villages
  4. apartments  — квартиры → /objects/table, /objects/plans
  5. parkings
  6. houses
  7. plots
  8. commercial
  9. house_projects
```

---

## Что записывается в БД

| Тип | Таблица | Кто пишет | UI |
|-----|---------|-----------|-----|
| complexes | trendagent_complexes | saveComplexToDb | Список комплексов |
| apartments | trendagent_apartments | saveApartmentToDb | Table, Plans |
| parkings | trendagent_parkings | saveParkingToDb | Паркинги |
| houses | trendagent_houses | saveHouseToDb | Дома |
| plots | trendagent_plots | savePlotToDb | Участки |
| commercial | trendagent_commercial | saveCommercialToDb | Коммерция |
| contractor_projects | trendagent_contractor_projects | saveContractorProjectToDb | Проекты |

---

## Почему парсер медленный

1. **Много регионов** (10: msk, spb, krd, rnd, crimea, kzn, ufa, ekb, nsk, dubai).
2. **Много типов** — 9 типов на регион.
3. **Много запросов** — для каждого объекта отдельный вызов API:
   - complexes: 1 запрос списка + N запросов деталей (по числу комплексов)
   - apartments: пагинация по 100 + N запросов деталей (по числу квартир)
4. **Сеть** — задержки к trendagent.ru.

Оценка: полный парсинг всех регионов — **часы** (3–8+).

---

## Как ускорить получение данных для СПб

Запустить парсер только для СПб (в разы быстрее):

```bash
cd /var/www/AL
screen -S trendagent-spb
bash scripts/trendagent_parse_spb.sh
# Ctrl+A, D — отключиться
```

Или напрямую:
```bash
php artisan trendagent:parse --region=spb --type=all --details=true --save-raw=true --save-db=true
```

---

## Рекомендация: запуск в screen

Чтобы парсер не обрывался при закрытии SSH:

```bash
ssh root@89.169.39.244
screen -S trendagent-parse
cd /var/www/AL
bash scripts/trendagent_parse_full.sh
# Нажать Ctrl+A, затем D — отключиться (парсер продолжит работать)
# Вернуться: screen -r trendagent-parse
```

---

## Краткая схема

```
Парсер (trendagent:parse)
    │
    ├─ msk: complexes → contractors → villages → apartments → ...
    ├─ spb: complexes → contractors → villages → apartments → ...
    ├─ krd: ...
    └─ ...
         │
         ▼
    БД (trendagent_*)
         │
         ├─ trendagent_complexes ──► /trendagent (список)
         ├─ trendagent_apartments ──► /objects/table, /objects/plans
         └─ ...
```

**Для заполнения Table и Plans** нужно дождаться, пока парсер дойдёт до **apartments** для региона **spb** (или изменить city в UI на msk, когда apartments для msk будут спарсены).
