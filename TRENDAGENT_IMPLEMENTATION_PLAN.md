# TRENDAGENT: План реализации полного парсинга

**Дата**: 8 февраля 2026  
**Статус**: 🟡 Готов к реализации  
**Основание**: Результаты тестирования API

---

## 📊 РЕЗУЛЬТАТЫ ТЕСТИРОВАНИЯ API

### ✅ Что работает из коробки:

| Метод | Что возвращает | Структура | Готовность |
|-------|---------------|-----------|------------|
| `getBlockParkings($blockId)` | Машиноместа комплекса | Массив объектов | ✅ Готово |
| `getBlockPlans($blockId)` | Планировки комплекса | Массив объектов | ✅ Готово |
| `getHousesSearch()` | Комплексы с домами | `houses_count`, `view_houses_count` | ⚠️ Нужен доп. метод |
| `getPlotsSearch()` | Участки напрямую | Полный объект участка | ✅ Готово! |
| `getVillagesSearch()` | Поселки | Полная информация | ✅ Готово |
| `getCommercialSearch()` | Комплексы с коммерцией | `premises_count` | ⚠️ Нужен доп. метод |

### 🔍 Ключевое открытие:

**УЧАСТКИ** - Можно получать напрямую через `getPlotsSearch()`!  
❌ Не нужно: Поселки → Участки поселка  
✅ Нужно: Прямой запрос всех участков

---

## 🎯 ПЛАН РЕАЛИЗАЦИИ

### ЭТАП 1: Исследование недостающих endpoints (30-60 мин)

#### Задачи:
1. **Найти endpoint для домов комплекса**
   - Попробовать: `GET /houses/block/{blockId}`
   - Аналог: `GET /apartments/block/{blockId}` (работает для квартир)
   - DevTools: Перехватить запрос на https://spb.trendagent.ru/houses/table/

2. **Найти endpoint для коммерческих помещений**
   - Попробовать: `GET /commerce/block/{blockId}`
   - DevTools: Перехватить запрос на https://spb.trendagent.ru/commerce/table/

#### Инструменты:
- Chrome DevTools → Network → Filter: XHR
- Открыть страницу со списком домов/помещений
- Перехватить запрос при загрузке таблицы

---

### ЭТАП 2: Добавление методов в TrendSsoApiAuth (1-2 часа)

#### 2.1. Метод для домов комплекса

```php
/**
 * Получение домов конкретного комплекса
 * 
 * @param string $blockId ID комплекса
 * @param array $params Дополнительные параметры
 * @return array Данные о домах
 * @throws \Exception
 */
public function getBlockHouses(string $blockId, array $params = []): array
{
    if (!$this->isAuthenticated()) {
        throw new \Exception('Необходимо сначала выполнить авторизацию');
    }

    try {
        $authToken = $this->getAuthToken();
        if (empty($authToken)) {
            throw new \Exception('Токен авторизации не найден');
        }

        $defaultParams = [
            'city' => '58c665588b6aa52311afa01b',
            'lang' => 'ru',
        ];
        $queryParams = array_merge($defaultParams, $params);
        $queryParams['auth_token'] = $authToken;

        // Попробовать https://houses.trendagent.ru/houses/block/{blockId}
        $apiUrl = "https://houses.trendagent.ru/houses/block/{$blockId}";
        $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

        Log::info('getBlockHouses - запрос к API', [
            'url' => $apiUrl,
            'block_id' => $blockId,
        ]);

        $response = $this->client->get($fullUrl, [
            'headers' => $this->getAuthHeaders(),
            'timeout' => 30,
            'verify' => false,
        ]);

        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();

        if ($statusCode !== 200) {
            throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
        }

        Log::info('getBlockHouses - получен ответ', [
            'block_id' => $blockId,
            'houses_count' => is_array($data['data'] ?? null) ? count($data['data']) : 0,
        ]);

        return [
            'success' => true,
            'data' => $data['data'] ?? $data,
            'raw_response' => $data,
        ];
    } catch (\Exception $e) {
        Log::error('getBlockHouses - ошибка', [
            'block_id' => $blockId,
            'message' => $e->getMessage(),
        ]);
        throw new \Exception('Ошибка при получении домов: ' . $e->getMessage());
    }
}
```

#### 2.2. Метод для коммерческих помещений

```php
/**
 * Получение коммерческих помещений конкретного комплекса
 * 
 * @param string $blockId ID комплекса
 * @param array $params Дополнительные параметры
 * @return array Данные о помещениях
 * @throws \Exception
 */
public function getBlockCommercial(string $blockId, array $params = []): array
{
    if (!$this->isAuthenticated()) {
        throw new \Exception('Необходимо сначала выполнить авторизацию');
    }

    try {
        $authToken = $this->getAuthToken();
        if (empty($authToken)) {
            throw new \Exception('Токен авторизации не найден');
        }

        $defaultParams = [
            'city' => '58c665588b6aa52311afa01b',
            'lang' => 'ru',
        ];
        $queryParams = array_merge($defaultParams, $params);
        $queryParams['auth_token'] = $authToken;

        // Попробовать https://commerce.trendagent.ru/commerce/block/{blockId}
        $apiUrl = "https://commerce.trendagent.ru/commerce/block/{$blockId}";
        $fullUrl = $apiUrl . '?' . http_build_query($queryParams);

        Log::info('getBlockCommercial - запрос к API', [
            'url' => $apiUrl,
            'block_id' => $blockId,
        ]);

        $response = $this->client->get($fullUrl, [
            'headers' => $this->getAuthHeaders(),
            'timeout' => 30,
            'verify' => false,
        ]);

        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();

        if ($statusCode !== 200) {
            throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
        }

        Log::info('getBlockCommercial - получен ответ', [
            'block_id' => $blockId,
            'commercial_count' => is_array($data['data'] ?? null) ? count($data['data']) : 0,
        ]);

        return [
            'success' => true,
            'data' => $data['data'] ?? $data,
            'raw_response' => $data,
        ];
    } catch (\Exception $e) {
        Log::error('getBlockCommercial - ошибка', [
            'block_id' => $blockId,
            'message' => $e->getMessage(),
        ]);
        throw new \Exception('Ошибка при получении помещений: ' . $e->getMessage());
    }
}
```

---

### ЭТАП 3: Обновление ParseCommand (2-3 часа)

#### 3.1. Исправить текущие методы парсинга

**БЫЛО (НЕПРАВИЛЬНО)**:
```php
private function parseParkings(int $offset, $bar): array
{
    // Получает КОМПЛЕКСЫ с паркингами, а не машиноместа!
    $data = $this->apiClient->getParkings($params);
    return ['processed' => count($data['data']), 'errors' => 0];
}
```

**ДОЛЖНО БЫТЬ**:
```php
private function parseParkingsType(): void
{
    $this->info("📦 Парсинг паркингов (машиномест)");
    
    // 1. Получить комплексы с паркингами (room=50)
    $offset = 0;
    $pageSize = 40;
    $processed = 0;
    $allComplexes = [];
    
    // Собираем все комплексы с паркингами
    while ($processed < $this->limit) {
        $data = $this->apiClient->getObjectsList($this->region, [50], $pageSize, $offset);
        $complexes = $data['data']['data'] ?? [];
        
        if (empty($complexes)) {
            break;
        }
        
        $allComplexes = array_merge($allComplexes, $complexes);
        $processed += count($complexes);
        $offset += $pageSize;
        
        usleep(100000); // 0.1 сек
    }
    
    $this->info("   Найдено {$processed} комплексов с паркингами");
    
    // 2. Для каждого комплекса получить машиноместа
    $totalParkings = 0;
    $bar = $this->output->createProgressBar(count($allComplexes));
    $bar->start();
    
    foreach ($allComplexes as $complex) {
        try {
            $parkings = $this->apiClient->getBlockParkings($complex['_id']);
            $parkingsData = $parkings['data'] ?? [];
            $count = is_array($parkingsData) ? count($parkingsData) : 0;
            
            if ($count > 0) {
                // Сохранить машиноместа
                $this->saveParkingsData($complex['_id'], $parkings);
                $totalParkings += $count;
            }
            
            $bar->advance();
            usleep(100000);
        } catch (Exception $e) {
            $this->warn("\n⚠️  Ошибка при получении паркингов комплекса {$complex['_id']}: " . $e->getMessage());
        }
    }
    
    $bar->finish();
    $this->newLine();
    $this->info("✅ Спарсено {$totalParkings} машиномест");
}
```

#### 3.2. Аналогично для домов и коммерции

**Дома**:
```php
private function parseHousesType(): void
{
    // 1. Получить комплексы с домами (room=30,40)
    $allComplexes = $this->getComplexesByRoom([30, 40]);
    
    // 2. Для каждого комплекса
    $totalHouses = 0;
    foreach ($allComplexes as $complex) {
        // Получить дома
        $houses = $this->apiClient->getBlockHouses($complex['_id']);
        $housesData = $houses['data'] ?? [];
        $count = is_array($housesData) ? count($housesData) : 0;
        
        if ($count > 0) {
            // Получить планировки
            if ($this->parseDetails) {
                $plans = $this->apiClient->getBlockPlans($complex['_id']);
                $this->saveHousesData($complex['_id'], $houses, $plans);
            } else {
                $this->saveHousesData($complex['_id'], $houses);
            }
            
            $totalHouses += $count;
        }
    }
    
    $this->info("✅ Спарсено {$totalHouses} домов");
}
```

**Коммерция**:
```php
private function parseCommercialType(): void
{
    // 1. Получить комплексы с коммерцией (room=110,120,130)
    $allComplexes = $this->getComplexesByRoom([110, 120, 130]);
    
    // 2. Для каждого комплекса
    $totalCommercial = 0;
    foreach ($allComplexes as $complex) {
        // Получить помещения
        $commercial = $this->apiClient->getBlockCommercial($complex['_id']);
        $commercialData = $commercial['data'] ?? [];
        $count = is_array($commercialData) ? count($commercialData) : 0;
        
        if ($count > 0) {
            // Получить планировки
            if ($this->parseDetails) {
                $plans = $this->apiClient->getBlockPlans($complex['_id']);
                $this->saveCommercialData($complex['_id'], $commercial, $plans);
            } else {
                $this->saveCommercialData($complex['_id'], $commercial);
            }
            
            $totalCommercial += $count;
        }
    }
    
    $this->info("✅ Спарсено {$totalCommercial} помещений");
}
```

**Участки** (уже работают правильно!):
```php
private function parsePlotsType(): void
{
    // Участки получаются напрямую!
    $offset = 0;
    $processed = 0;
    
    while ($processed < $this->limit) {
        $data = $this->apiClient->getPlotsSearch([
            'city' => $this->region,
            'count' => 100,
            'offset' => $offset,
        ]);
        
        $plots = $data['data'] ?? [];
        if (empty($plots)) {
            break;
        }
        
        if ($this->saveRaw) {
            $this->saveRawData('plots', 'list', $offset, $data);
        }
        
        $processed += count($plots);
        $offset += 40; // API возвращает ~40 объектов
        usleep(100000);
    }
    
    $this->info("✅ Спарсено {$processed} участков");
}
```

#### 3.3. Вспомогательный метод

```php
/**
 * Получить комплексы по типу комнат
 */
private function getComplexesByRoom(array $roomTypes): array
{
    $offset = 0;
    $pageSize = 40;
    $allComplexes = [];
    
    while (true) {
        $data = $this->apiClient->getObjectsList($this->region, $roomTypes, $pageSize, $offset);
        $complexes = $data['data']['data'] ?? [];
        
        if (empty($complexes)) {
            break;
        }
        
        $allComplexes = array_merge($allComplexes, $complexes);
        $offset += $pageSize;
        
        usleep(100000);
    }
    
    return $allComplexes;
}
```

---

### ЭТАП 4: Методы сохранения данных (1 час)

#### 4.1. Структура директорий

```
storage/app/private/trendagent/parsing/spb/
├── parkings/
│   ├── complexes/                    # Комплексы с паркингами
│   │   └── list_room_50_offset_0.json
│   └── items/                        # Машиноместа по комплексам
│       ├── parkings_block_XXX.json   # Все машиноместа комплекса XXX
│       └── ...
│
├── houses/
│   ├── complexes/                    # Комплексы с домами
│   │   └── list_room_30_40_offset_0.json
│   ├── items/                        # Дома по комплексам
│   │   ├── houses_block_XXX.json
│   │   └── ...
│   └── plans/                        # Планировки по комплексам
│       ├── plans_block_XXX.json
│       └── ...
│
├── plots/
│   └── items/                        # Участки напрямую
│       └── list_offset_0.json
│
└── commercial/
    ├── complexes/                    # Комплексы с коммерцией
    │   └── list_room_110_120_130_offset_0.json
    ├── items/                        # Помещения по комплексам
    │   ├── commercial_block_XXX.json
    │   └── ...
    └── plans/                        # Планировки по комплексам
        ├── plans_block_XXX.json
        └── ...
```

#### 4.2. Методы сохранения

```php
private function saveParkingsData(string $blockId, array $parkingsData): void
{
    $fileName = "parkings_block_{$blockId}.json";
    $path = "trendagent/parsing/{$this->region}/parkings/items/{$fileName}";
    
    $saveData = [
        'metadata' => [
            'region' => $this->region,
            'type' => 'parkings',
            'data_type' => 'block_items',
            'timestamp' => now()->toIso8601String(),
            'block_id' => $blockId,
            'items_count' => is_array($parkingsData['data'] ?? null) ? count($parkingsData['data']) : 0,
        ],
        'data' => $parkingsData,
    ];
    
    Storage::disk('local')->put($path, json_encode($saveData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// Аналогично для домов, коммерции
private function saveHousesData(string $blockId, array $housesData, ?array $plansData = null): void
{
    // Сохранить дома
    $fileName = "houses_block_{$blockId}.json";
    $path = "trendagent/parsing/{$this->region}/houses/items/{$fileName}";
    
    $saveData = [
        'metadata' => [
            'region' => $this->region,
            'type' => 'houses',
            'data_type' => 'block_items',
            'timestamp' => now()->toIso8601String(),
            'block_id' => $blockId,
            'items_count' => is_array($housesData['data'] ?? null) ? count($housesData['data']) : 0,
        ],
        'data' => $housesData,
    ];
    
    Storage::disk('local')->put($path, json_encode($saveData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // Сохранить планировки
    if ($plansData) {
        $plansFileName = "plans_block_{$blockId}.json";
        $plansPath = "trendagent/parsing/{$this->region}/houses/plans/{$plansFileName}";
        
        $plansaveData = [
            'metadata' => [
                'region' => $this->region,
                'type' => 'houses',
                'data_type' => 'plans',
                'timestamp' => now()->toIso8601String(),
                'block_id' => $blockId,
                'plans_count' => is_array($plansData['data'] ?? null) ? count($plansData['data']) : 0,
            ],
            'data' => $plansData,
        ];
        
        Storage::disk('local')->put($plansPath, json_encode($plansaveData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
```

---

### ЭТАП 5: Тестирование (1-2 часа)

#### 5.1. Тестовые запуски

```bash
# 1. Паркинги (100 комплексов)
php artisan trendagent:parse --region=spb --type=parkings --limit=100 --details --save-raw

# Проверить:
# - Количество комплексов
# - Количество машиномест
# - Структуру данных

# 2. Дома (100 комплексов)
php artisan trendagent:parse --region=spb --type=houses --limit=100 --details --save-raw

# 3. Участки (100 штук)
php artisan trendagent:parse --region=spb --type=plots --limit=100 --save-raw

# 4. Коммерция (100 комплексов)
php artisan trendagent:parse --region=spb --type=commercial --limit=100 --details --save-raw
```

#### 5.2. Проверка результатов

```bash
# Подсчет объектов
find storage/app/private/trendagent/parsing/spb/parkings/items/ -name "*.json" | wc -l
find storage/app/private/trendagent/parsing/spb/houses/items/ -name "*.json" | wc -l
find storage/app/private/trendagent/parsing/spb/plots/items/ -name "*.json" | wc -l
find storage/app/private/trendagent/parsing/spb/commercial/items/ -name "*.json" | wc -l
```

---

### ЭТАП 6: Полный парсинг (4-6 часов)

```bash
# Запустить парсинг всех типов
nohup php artisan trendagent:parse --region=spb --type=parkings --limit=10000 --details --save-raw > parkings.log 2>&1 &
nohup php artisan trendagent:parse --region=spb --type=houses --limit=10000 --details --save-raw > houses.log 2>&1 &
nohup php artisan trendagent:parse --region=spb --type=plots --limit=10000 --save-raw > plots.log 2>&1 &
nohup php artisan trendagent:parse --region=spb --type=commercial --limit=10000 --details --save-raw > commercial.log 2>&1 &
```

---

## 📊 ОЖИДАЕМЫЕ РЕЗУЛЬТАТЫ

| Тип | Комплексов/Поселков | Объектов | Планировок | Итого файлов |
|-----|-------------------|----------|------------|--------------|
| **Квартиры** | 344 ЖК | 54,993 | - | 688 |
| **Паркинги** | 50 ЖК | 3,644 | - | 50 |
| **Дома** | 60 комплексов | 1,023 | 60 | 120 |
| **Участки** | - | 2,370 | - | ~60 |
| **Коммерция** | 167 ЖК | 1,771 | 167 | 334 |
| **ВСЕГО** | 621 | 63,801 | 227 | ~1,252 |

---

## ✅ ЧЕКЛИСТ РЕАЛИЗАЦИИ

### Этап 1: Исследование
- [ ] Найти endpoint для домов комплекса
- [ ] Найти endpoint для коммерческих помещений
- [ ] Протестировать endpoints в Postman/curl

### Этап 2: API методы
- [ ] Добавить `getBlockHouses()` в `TrendSsoApiAuth.php`
- [ ] Добавить `getBlockCommercial()` в `TrendSsoApiAuth.php`
- [ ] Протестировать новые методы

### Этап 3: ParseCommand
- [ ] Исправить `parseParkings()` → `parseParkingsType()`
- [ ] Исправить `parseHouses()` → `parseHousesType()`
- [ ] Исправить `parsePlots()` → `parsePlotsType()`
- [ ] Исправить `parseCommercial()` → `parseCommercialType()`
- [ ] Добавить `getComplexesByRoom()` метод
- [ ] Обновить `parseType()` для вызова новых методов

### Этап 4: Сохранение
- [ ] Добавить `saveParkingsData()`
- [ ] Добавить `saveHousesData()`
- [ ] Добавить `saveCommercialData()`
- [ ] Проверить структуру директорий

### Этап 5: Тестирование
- [ ] Тест паркингов (limit=100)
- [ ] Тест домов (limit=100)
- [ ] Тест участков (limit=100)
- [ ] Тест коммерции (limit=100)
- [ ] Проверить данные в файлах

### Этап 6: Полный парсинг
- [ ] Запустить полный парсинг всех типов
- [ ] Мониторинг процесса
- [ ] Проверить логи на ошибки
- [ ] Подсчитать финальные результаты
- [ ] Создать итоговый отчет

---

**Оценка времени**: 10-15 часов  
**Приоритет**: 🔴 Критический  
**Статус**: 🟡 Готов к реализации после согласования
