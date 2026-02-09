# Отчет об исправлении сохранения данных в БД

## Проблема

После парсинга данные не сохранялись в БД для большинства типов объектов:
- ✅ Квартиры: 343100 записей (из 343109)
- ❌ Комплексы: 0 записей (из 1120)
- ❌ Паркинги: 0 записей (из 33092)
- ❌ Дома: 0 записей (из 5253)
- ❌ Участки: 0 записей (из 115)
- ❌ Коммерция: 0 записей (из 8148)
- ❌ Подрядчики: 0 записей (из 1137)

## Причины

1. **Сохранение только при `--details`**: Для паркингов, домов, участков, коммерции и подрядчиков сохранение в БД происходило ТОЛЬКО внутри блока `if ($this->option('details'))`. Для комплексов и квартир была дополнительная проверка `elseif ($this->shouldSaveToDb)`, которая сохраняла данные даже без деталей.

2. **Неправильная обработка JSON полей**: Методы `saveParkingToDb`, `saveHouseToDb`, `savePlotToDb`, `saveCommercialToDb`, `saveContractorToDb` использовали `fill()`, который не всегда правильно обрабатывает JSON поля через casts. Метод `saveComplexToDb` использовал `setAttribute()` для JSON полей, что правильно.

## Исправления

### 1. Добавлено сохранение без `--details` для всех типов

Добавлены блоки `elseif ($this->shouldSaveToDb)` для всех типов объектов:

```php
// Для паркингов
} elseif ($this->shouldSaveToDb) {
    // Если детали не парсим, все равно сохраняем в БД из данных списка
    $this->saveParkingToDb(['data' => $item], $placeId);
}

// Для домов
} elseif ($this->shouldSaveToDb) {
    $this->saveHouseToDb(['data' => $item], $houseId);
}

// Для участков
} elseif ($this->shouldSaveToDb) {
    $this->savePlotToDb(['data' => $item], $plotId);
}

// Для коммерции
} elseif ($this->shouldSaveToDb) {
    $this->saveCommercialToDb(['data' => $item], $commercialId);
}

// Для подрядчиков
} elseif ($this->shouldSaveToDb) {
    $this->saveContractorToDb(['data' => $item], $contractorId);
}
```

### 2. Исправлена обработка JSON полей

Все методы сохранения теперь используют `setAttribute()` для JSON полей, как в `saveComplexToDb`:

**Было:**
```php
$parking = Parking::firstOrNew(['external_id' => $externalId]);
$parking->fill($dbData);
$parking->save();
```

**Стало:**
```php
$parking = Parking::firstOrNew(['external_id' => $externalId]);

// Устанавливаем обычные поля
foreach ($dbData as $key => $value) {
    if (!in_array($key, ['images', 'raw_data'])) {
        $parking->setAttribute($key, $value);
    }
}

// Явно устанавливаем JSON поля (Laravel автоматически конвертирует через casts)
$parking->setAttribute('images', $images);
$parking->setAttribute('raw_data', $parkingData);

$parking->save();
```

## Измененные файлы

1. `app/Console/Commands/TrendAgentParse.php`:
   - Добавлены блоки `elseif ($this->shouldSaveToDb)` для паркингов, домов, участков, коммерции и подрядчиков
   - Исправлены методы `saveParkingToDb`, `saveHouseToDb`, `savePlotToDb`, `saveCommercialToDb`, `saveContractorToDb` для правильной обработки JSON полей

2. `app/Console/Commands/TrendAgent/ImportDataCommand.php`:
   - Исправлена инициализация `$this->region`

## Следующие шаги

### Вариант 1: Перезапустить парсинг (рекомендуется)

Если данные еще не были удалены, можно перезапустить парсинг с исправленным кодом:

```bash
php artisan trendagent:parse --type=all --details --save-raw --save-db
```

Это сохранит все данные в БД с исправленными методами.

### Вариант 2: Импортировать из сохраненных JSON файлов

Если JSON файлы были сохранены при парсинге, можно импортировать их:

```bash
# Для всех регионов
for region in msk spb krd rnd crimea kzn ufa ekb nsk dubai; do
    php artisan trendagent:import-data --region=$region --type=all
done

# Или для конкретного региона
php artisan trendagent:import-data --region=spb --type=all
```

### Вариант 3: Импортировать только недостающие типы

```bash
# Импортировать только комплексы
php artisan trendagent:import-data --region=spb --type=complexes

# Импортировать только паркинги
php artisan trendagent:import-data --region=spb --type=parkings

# И так далее для каждого типа
```

## Проверка результатов

После импорта проверьте количество записей:

```bash
php artisan trendagent:check-data
```

Ожидаемые результаты:
- Комплексы: ~1120
- Квартиры: ~343109
- Паркинги: ~33092
- Дома: ~5253
- Участки: ~115
- Коммерция: ~8148
- Подрядчики: ~1137

## Примечания

- Методы сохранения теперь работают одинаково для всех типов объектов
- JSON поля обрабатываются через `setAttribute()`, что гарантирует правильную конвертацию через casts
- Данные сохраняются в БД даже если `--details` не указан (из данных списка)
- При наличии деталей сохраняются детальные данные, иначе - данные из списка
