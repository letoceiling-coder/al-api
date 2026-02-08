<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TrendAgent\TrendSsoApiAuth;
use App\Services\TrendAgent\ImageDownloader;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\TrendAgent\Region;
use App\Models\TrendAgent\Complex;
use App\Models\TrendAgent\Apartment;
use App\Models\TrendAgent\Parking;
use App\Models\TrendAgent\ParkingPlace;
use App\Models\TrendAgent\House;
use App\Models\TrendAgent\PlotSettlement;
use App\Models\TrendAgent\Plot;
use App\Models\TrendAgent\Commercial;
use App\Models\TrendAgent\Contractor;
use App\Models\TrendAgent\ContractorProject;
use App\Services\TrendAgent\CityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as LogFacade;

class TrendAgentParse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trendagent:parse 
                            {--region= : Region code (spb, msk, ekb, nsk, krd, rnd, crimea, kzn, ufa, dubai). Если не указан, парсятся все регионы}
                            {--type=all : Type of objects (all, apartments, parkings, houses, plots, commercial, complexes)}
                            {--limit=0 : Limit number of objects (0 = no limit)}
                            {--offset=0 : Offset for pagination}
                            {--details=true : Parse detailed pages}
                            {--images=true : Download images}
                            {--save-raw=true : Save raw data to files}
                            {--save-db=true : Save data to database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse TrendAgent data for specified region and object types';

    protected $apiAuth;
    protected $imageDownloader;
    protected $region;
    protected $basePath;
    protected $regionModel;
    protected $allRegionsStatistics = []; // Статистика по всем регионам
    protected $shouldSaveToDb = true; // Флаг сохранения в БД
    protected $statistics = [
        'complexes' => ['total' => 0, 'parsed' => 0, 'errors' => 0],
        'apartments' => ['total' => 0, 'parsed' => 0, 'errors' => 0],
        'parkings' => ['total' => 0, 'parsed' => 0, 'errors' => 0],
        'houses' => ['total' => 0, 'parsed' => 0, 'errors' => 0],
        'plots' => ['total' => 0, 'parsed' => 0, 'errors' => 0],
        'commercial' => ['total' => 0, 'parsed' => 0, 'errors' => 0],
        'contractors' => ['total' => 0, 'parsed' => 0, 'errors' => 0],
        'images' => ['total' => 0, 'downloaded' => 0, 'errors' => 0],
        'by_type_total' => [], // Сохраняем total из API для каждого типа
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Инициализация
        $this->apiAuth = new TrendSsoApiAuth();
        $this->imageDownloader = new ImageDownloader();
        
        // Проверяем флаг сохранения в БД
        $saveDbOption = $this->shouldSaveToDb;
        $this->shouldSaveToDb = filter_var($saveDbOption, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($this->shouldSaveToDb === null) {
            // Если не удалось распарсить, проверяем как строку
            $this->shouldSaveToDb = in_array(strtolower($saveDbOption), ['true', '1', 'yes', 'on'], true);
        }
        
        if ($this->shouldSaveToDb) {
            $this->info("💾 Сохранение в БД: ВКЛЮЧЕНО");
        } else {
            $this->warn("💾 Сохранение в БД: ОТКЛЮЧЕНО");
        }
        $this->newLine();
        
        // Авторизация
        if (!$this->authenticate()) {
            $this->error('Authentication failed');
            return 1;
        }
        
        // Определяем регионы для парсинга
        $regionOption = $this->option('region');
        $regions = [];
        
        if (empty($regionOption)) {
            // Если регион не указан, парсим все регионы
            $regions = CityService::getAllCityKeys();
            $this->info("🌍 Регион не указан. Будет выполнен парсинг всех регионов: " . implode(', ', $regions));
        } else {
            // Парсим только указанный регион
            $regions = [$regionOption];
            $this->info("📍 Парсинг региона: {$regionOption}");
        }
        
        $this->newLine();
        
        // Парсим каждый регион
        foreach ($regions as $regionCode) {
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("🌍 Парсинг региона: {$regionCode} ({$this->getRegionName($regionCode)})");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->newLine();
            
            $this->region = $regionCode;
            $this->basePath = storage_path("trendagent/parsing/{$this->region}");
            
            // Получаем или создаем регион в БД
            $this->regionModel = Region::firstOrCreate(
                ['code' => $this->region],
                ['name' => $this->getRegionName($this->region)]
            );
            
            // Определяем типы объектов для парсинга
            $type = $this->option('type');
            $types = $type === 'all' 
                ? ['complexes', 'apartments', 'parkings', 'houses', 'plots', 'commercial', 'contractors']
                : [$type];
            
            // Парсим каждый тип объекта
            foreach ($types as $objectType) {
                $this->info("📦 Парсинг типа: {$objectType} для региона {$regionCode}");
                $this->parseType($objectType);
                $this->newLine();
            }
            
            // Сохраняем статистику для региона
            $this->saveStatistics();
            
            // Сохраняем статистику региона в общую статистику
            $this->allRegionsStatistics[$regionCode] = $this->statistics;
            
            // Сбрасываем статистику для следующего региона
            $this->statistics = [
                'complexes' => ['total' => 0, 'parsed' => 0, 'errors' => 0],
                'apartments' => ['total' => 0, 'parsed' => 0, 'errors' => 0],
                'parkings' => ['total' => 0, 'parsed' => 0, 'errors' => 0],
                'houses' => ['total' => 0, 'parsed' => 0, 'errors' => 0],
                'plots' => ['total' => 0, 'parsed' => 0, 'errors' => 0],
                'commercial' => ['total' => 0, 'parsed' => 0, 'errors' => 0],
                'contractors' => ['total' => 0, 'parsed' => 0, 'errors' => 0],
                'images' => ['total' => 0, 'downloaded' => 0, 'errors' => 0],
                'by_type_total' => [],
            ];
        }
        
        // Выводим общую статистику по всем регионам
        $this->newLine();
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("✅ Парсинг завершен для всех регионов!");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->newLine();
        
        // Выводим сводную статистику по всем регионам
        $this->displayAllRegionsStatistics();
        
        // Выводим точные данные из API
        $this->newLine();
        $this->info("📊 Точные данные из API:");
        $this->displayExactData();
        
        // Выводим отчет по парсингу и БД
        $this->displayParsingAndDbReport();
        
        // Очищаем PID файл после завершения (если запущен через веб-интерфейс)
        $this->clearParserPid();
        
        return 0;
    }
    
    /**
     * Авторизация в TrendAgent API
     */
    protected function authenticate(): bool
    {
        $phone = env('TRENDAGENT_PHONE', '+79045393434');
        $password = env('TRENDAGENT_PASSWORD', 'nwBvh4q');
        
        try {
            $authData = $this->apiAuth->authenticate($phone, $password);
            return $authData['authenticated'] ?? false;
        } catch (\Exception $e) {
            $this->error("Authentication error: {$e->getMessage()}");
            return false;
        }
    }
    
    /**
     * Парсинг определенного типа объектов
     */
    protected function parseType(string $type): void
    {
        switch ($type) {
            case 'complexes':
                $this->parseComplexes();
                break;
            case 'apartments':
                $this->parseApartments();
                break;
            case 'parkings':
                $this->parseParkings();
                break;
            case 'houses':
                $this->parseHouses();
                break;
            case 'plots':
                $this->parsePlots();
                break;
            case 'commercial':
                $this->parseCommercial();
                break;
            case 'contractors':
                $this->parseContractors();
                break;
        }
    }
    
    /**
     * Парсинг комплексов
     */
    protected function parseComplexes(): void
    {
        $offset = (int) $this->option('offset');
        $limit = (int) $this->option('limit');
        $count = 100;
        $parsed = 0;
        
        $this->info("Fetching complexes list...");
        
        do {
            $data = $this->fetchObjectsList('complexes', $offset, $count);
            
            if (!$data || empty($data['data'] ?? [])) {
                break;
            }
            
            // Сохраняем total из API (если еще не сохранен)
            if (!isset($this->statistics['by_type_total']['complexes']) && isset($data['total'])) {
                $this->statistics['by_type_total']['complexes'] = $data['total'];
            }
            
            $items = $data['data'];
            $this->statistics['complexes']['total'] += count($items);
            
            foreach ($items as $item) {
                if ($limit > 0 && $parsed >= $limit) {
                    break 2;
                }
                
                $complexId = $item['id'] ?? $item['_id'] ?? null;
                if (!$complexId) {
                    continue;
                }
                
                // Сохраняем сырые данные списка
                if ($this->option('save-raw')) {
                    $this->saveRawData('complexes', "list_offset_{$offset}.json", $data);
                }
                
                // Парсим детали
                if ($this->option('details')) {
                    $this->parseComplexDetails($complexId, $item);
                }
                
                $parsed++;
                $this->statistics['complexes']['parsed']++;
                
                $this->line("  Parsed complex: {$complexId} ({$parsed})");
            }
            
            // Проверяем, нужно ли продолжать пагинацию
            $totalFromApi = $this->statistics['by_type_total']['complexes'] ?? null;
            
            // Продолжаем, если:
            // 1. Получили полную страницу (count($items) === $count)
            // 2. ИЛИ если есть total из API и offset + count < total (есть еще данные)
            // 3. И limit не достигнут
            $shouldContinue = false;
            if (count($items) === $count) {
                $shouldContinue = true;
            } elseif ($totalFromApi !== null && ($offset + $count) < $totalFromApi) {
                $shouldContinue = true;
            }
            
            if ($limit > 0 && $parsed >= $limit) {
                $shouldContinue = false;
            }
            
            $offset += $count;
        } while ($shouldContinue);
    }
    
    /**
     * Парсинг деталей комплекса
     */
    protected function parseComplexDetails(string $complexId, array $listItem): void
    {
        try {
            $details = $this->fetchComplexDetails($complexId);
            
            // Сохраняем детальные данные
            if ($this->option('details')) {
                if ($details && isset($details['success']) && $details['success']) {
                    $this->saveDetailsData('complexes', "{$complexId}.json", $details);
                    // Сохраняем в БД
                    if ($this->shouldSaveToDb) {
                        $this->saveComplexToDb($details, $complexId);
                    }
                } else {
                    // Сохраняем хотя бы данные из списка, если детали не получены
                    $this->saveDetailsData('complexes', "{$complexId}.json", ['data' => $listItem, 'source' => 'list']);
                    // Пытаемся сохранить в БД из данных списка
                    if ($this->shouldSaveToDb) {
                        $this->saveComplexToDb(['data' => $listItem], $complexId);
                    }
                    $this->statistics['complexes']['errors']++;
                }
            } elseif ($this->shouldSaveToDb) {
                // Если детали не парсим, все равно сохраняем в БД из данных списка
                $this->saveComplexToDb(['data' => $listItem], $complexId);
            }
            
            // Скачиваем изображения (только если детали получены)
            if ($this->option('images') && $details && isset($details['success']) && $details['success']) {
                $this->downloadObjectImages('complex', $complexId, $details);
                
                // Обновляем URL изображений в данных
                $details = $this->updateImageUrlsInData($details, 'complex', $complexId);
            }
            
        } catch (\Exception $e) {
            // Сохраняем данные из списка при ошибке
            if ($this->option('details')) {
                $this->saveDetailsData('complexes', "{$complexId}.json", ['data' => $listItem, 'source' => 'list', 'error' => $e->getMessage()]);
            }
            $this->error("Error parsing complex {$complexId}: {$e->getMessage()}");
            $this->statistics['complexes']['errors']++;
            $this->logError('complexes', $complexId, $e->getMessage());
        }
    }
    
    /**
     * Парсинг квартир
     */
    protected function parseApartments(): void
    {
        $offset = (int) $this->option('offset');
        $limit = (int) $this->option('limit');
        $count = 100;
        $parsed = 0;
        
        $this->info("Fetching apartments list...");
        
        do {
            $data = $this->fetchApartmentsList($offset, $count);
            
            if (!$data || empty($data['data'] ?? [])) {
                break;
            }
            
            // Сохраняем total из API (если еще не сохранен)
            if (!isset($this->statistics['by_type_total']['apartments']) && isset($data['total'])) {
                $this->statistics['by_type_total']['apartments'] = $data['total'];
            }
            
            $items = $data['data'];
            $this->statistics['apartments']['total'] += count($items);
            
            // Сохраняем сырые данные один раз за страницу (вне цикла)
            if ($this->option('save-raw')) {
                $this->saveRawData('apartments', "list_offset_{$offset}.json", $data);
            }
            
            foreach ($items as $item) {
                if ($limit > 0 && $parsed >= $limit) {
                    break 2;
                }
                
                $apartmentId = $item['id'] ?? $item['_id'] ?? null;
                if (!$apartmentId) {
                    continue;
                }
                
                // Парсим детали
                if ($this->option('details')) {
                    $this->parseApartmentDetails($apartmentId, $item);
                } elseif ($this->shouldSaveToDb) {
                    // Если детали не парсим, все равно сохраняем в БД из данных списка
                    $this->saveApartmentToDb([], $apartmentId, $item);
                }
                
                $parsed++;
                $this->statistics['apartments']['parsed']++;
                
                $this->line("  Parsed apartment: {$apartmentId} ({$parsed})");
            }
            
            // Проверяем, нужно ли продолжать пагинацию
            $totalFromApi = $this->statistics['by_type_total']['apartments'] ?? null;
            
            // Продолжаем, если:
            // 1. Получили полную страницу (count($items) === $count)
            // 2. ИЛИ если есть total из API и offset + count < total (есть еще данные)
            // 3. И limit не достигнут
            $shouldContinue = false;
            if (count($items) === $count) {
                $shouldContinue = true;
            } elseif ($totalFromApi !== null && ($offset + $count) < $totalFromApi) {
                $shouldContinue = true;
            }
            
            if ($limit > 0 && $parsed >= $limit) {
                $shouldContinue = false;
            }
            
            $offset += $count;
        } while ($shouldContinue);
    }
    
    /**
     * Парсинг деталей квартиры
     */
    protected function parseApartmentDetails(string $apartmentId, array $listItem): void
    {
        try {
            // Пытаемся получить block_id, но не останавливаемся, если его нет
            // API поддерживает получение деталей без block_id (fallback)
            $blockId = $listItem['block_id'] ?? $listItem['block'] ?? $listItem['block_id'] ?? null;
            
            $details = $this->fetchApartmentDetails($blockId, $apartmentId);
            
            if (!$details) {
                $this->statistics['apartments']['errors']++;
                // Логируем, но не останавливаем парсинг
                $this->warn("  ⚠️  Не удалось получить детали квартиры {$apartmentId}" . ($blockId ? " (block_id: {$blockId})" : " (без block_id)"));
                // Пытаемся сохранить в БД из данных списка
                if ($this->shouldSaveToDb) {
                    $this->saveApartmentToDb([], $apartmentId, $listItem);
                }
                return;
            }
            
            // Сохраняем детальные данные
            if ($this->option('details')) {
                $this->saveDetailsData('apartments', "{$apartmentId}.json", $details);
                // Сохраняем в БД
                if ($this->shouldSaveToDb) {
                    $this->saveApartmentToDb($details, $apartmentId, $listItem);
                }
            } elseif ($this->shouldSaveToDb) {
                // Если детали не парсим, все равно сохраняем в БД из данных списка
                $this->saveApartmentToDb([], $apartmentId, $listItem);
            }
            
            // Скачиваем изображения
            if ($this->option('images')) {
                $this->downloadObjectImages('apartment', $apartmentId, $details);
            }
            
        } catch (\Exception $e) {
            $this->error("Error parsing apartment {$apartmentId}: {$e->getMessage()}");
            $this->statistics['apartments']['errors']++;
            $this->logError('apartments', $apartmentId, $e->getMessage());
        }
    }
    
    /**
     * Парсинг паркингов
     */
    protected function parseParkings(): void
    {
        $offset = (int) $this->option('offset');
        $limit = (int) $this->option('limit');
        $count = 50; // Для машиномест используем count=50
        $parsed = 0;
        
        $this->info("Fetching parking places list...");
        
        try {
            $apiClient = new \App\Services\TrendAgent\TrendAgentApiClient();
            $authResult = $apiClient->authenticate();
            if (!$authResult['success']) {
                $this->error("Authentication failed");
                return;
            }
            
            // Получаем total из первого запроса (машиноместа)
            $firstParams = [
                'city' => $this->region,
                'count' => 1,
                'offset' => 0,
            ];
            $firstData = $apiClient->getParkingPlacesList($firstParams);
            if (isset($firstData['total'])) {
                $this->statistics['by_type_total']['parkings'] = $firstData['total'];
            }
            
            do {
                $params = [
                    'city' => $this->region,
                    'count' => $count,
                    'offset' => $offset,
                ];
                
                $data = $apiClient->getParkingPlacesList($params);
                
                if (!$data || !isset($data['success']) || !$data['success'] || empty($data['data'] ?? [])) {
                    break;
                }
                
                $items = $data['data'];
                $this->statistics['parkings']['total'] += count($items);
                // Обновляем total из API, если он есть
                if (isset($data['total'])) {
                    $this->statistics['by_type_total']['parkings'] = $data['total'];
                }
                
                foreach ($items as $item) {
                    if ($limit > 0 && $parsed >= $limit) {
                        break 2;
                    }
                    
                    $placeId = $item['_id'] ?? $item['id'] ?? null;
                    if (!$placeId) {
                        continue;
                    }
                    
                    if ($this->option('save-raw') && $parsed === 0) {
                        // Сохраняем сырые данные только для первой страницы
                        $this->saveRawData('parkings', "list_offset_{$offset}.json", $data);
                    }
                    
                    if ($this->option('details')) {
                        // Для машиномест детали могут быть в самом объекте
                        // Но попробуем получить дополнительные детали, если доступны
                        try {
                            // Сохраняем данные из списка (они уже содержат основную информацию)
                            $this->saveDetailsData('parkings', "{$placeId}.json", ['data' => $item, 'source' => 'list']);
                            // Сохраняем в БД
                            if ($this->shouldSaveToDb) {
                                $this->saveParkingToDb(['data' => $item], $placeId);
                            }
                        } catch (\Exception $e) {
                            // В случае ошибки все равно сохраняем базовые данные
                            $this->saveDetailsData('parkings', "{$placeId}.json", ['data' => $item, 'source' => 'list', 'error' => $e->getMessage()]);
                            // Пытаемся сохранить в БД
                            if ($this->shouldSaveToDb) {
                                $this->saveParkingToDb(['data' => $item], $placeId);
                            }
                        }
                    }
                    
                    $parsed++;
                    $this->statistics['parkings']['parsed']++;
                    $this->line("  Parsed parking place: {$placeId} ({$parsed})");
                }
                
                // Проверяем, нужно ли продолжать пагинацию
                $totalFromApi = $this->statistics['by_type_total']['parkings'] ?? null;
                
                // Продолжаем, если:
                // 1. Получили полную страницу (count($items) === $count)
                // 2. ИЛИ если есть total из API и offset + count < total (есть еще данные)
                // 3. И limit не достигнут
                $shouldContinue = false;
                if (count($items) === $count) {
                    $shouldContinue = true;
                } elseif ($totalFromApi !== null && ($offset + $count) < $totalFromApi) {
                    $shouldContinue = true;
                }
                
                if ($limit > 0 && $parsed >= $limit) {
                    $shouldContinue = false;
                }
                
                $offset += $count;
            } while ($shouldContinue);
            
        } catch (\Exception $e) {
            $this->error("Error parsing parkings: {$e->getMessage()}");
            $this->statistics['parkings']['errors']++;
        }
    }
    
    /**
     * Парсинг домов
     */
    protected function parseHouses(): void
    {
        $offset = (int) $this->option('offset');
        $limit = (int) $this->option('limit');
        $count = 100;
        $parsed = 0;
        
        $this->info("Fetching houses list...");
        
        try {
            $apiClient = new \App\Services\TrendAgent\TrendAgentApiClient();
            $authResult = $apiClient->authenticate();
            if (!$authResult['success']) {
                $this->error("Authentication failed");
                return;
            }
            
            do {
                $params = [
                    'city' => $this->region,
                    'count' => $count,
                    'offset' => $offset,
                ];
                
                $data = $apiClient->getHouses($params);
                
                if (!$data || !isset($data['success']) || !$data['success'] || empty($data['data'] ?? [])) {
                    break;
                }
                
                // Сохраняем total из API (если еще не сохранен)
                if (!isset($this->statistics['by_type_total']['houses']) && isset($data['total'])) {
                    $this->statistics['by_type_total']['houses'] = $data['total'];
                }
                
                $items = $data['data'];
                $this->statistics['houses']['total'] += count($items);
                
                foreach ($items as $item) {
                    if ($limit > 0 && $parsed >= $limit) {
                        break 2;
                    }
                    
                    $houseId = $item['_id'] ?? $item['id'] ?? null;
                    if (!$houseId) {
                        continue;
                    }
                    
                    if ($this->option('save-raw')) {
                        $this->saveRawData('houses', "list_offset_{$offset}.json", $data);
                    }
                    
                    if ($this->option('details')) {
                        try {
                            $details = $apiClient->getHouseDetails($houseId);
                            if ($details && isset($details['success']) && $details['success']) {
                                $this->saveDetailsData('houses', "{$houseId}.json", $details);
                                // Сохраняем в БД
                                if ($this->shouldSaveToDb) {
                                    $this->saveHouseToDb($details, $houseId);
                                }
                            } else {
                                // Сохраняем хотя бы данные из списка, если детали не получены
                                $this->saveDetailsData('houses', "{$houseId}.json", ['data' => $item, 'source' => 'list']);
                                // Пытаемся сохранить в БД из данных списка
                                $this->saveHouseToDb(['data' => $item], $houseId);
                            }
                        } catch (\Exception $e) {
                            // Сохраняем данные из списка при ошибке
                            $this->saveDetailsData('houses', "{$houseId}.json", ['data' => $item, 'source' => 'list', 'error' => $e->getMessage()]);
                            // Пытаемся сохранить в БД из данных списка
                            if ($this->shouldSaveToDb) {
                                $this->saveHouseToDb(['data' => $item], $houseId);
                            }
                        }
                    }
                    
                    $parsed++;
                    $this->statistics['houses']['parsed']++;
                    $this->line("  Parsed house: {$houseId} ({$parsed})");
                }
                
                // Проверяем, нужно ли продолжать пагинацию
                $totalFromApi = $this->statistics['by_type_total']['houses'] ?? null;
                
                // Продолжаем, если:
                // 1. Получили полную страницу (count($items) === $count)
                // 2. ИЛИ если есть total из API и offset + count < total (есть еще данные)
                // 3. И limit не достигнут
                $shouldContinue = false;
                if (count($items) === $count) {
                    $shouldContinue = true;
                } elseif ($totalFromApi !== null && ($offset + $count) < $totalFromApi) {
                    $shouldContinue = true;
                }
                
                if ($limit > 0 && $parsed >= $limit) {
                    $shouldContinue = false;
                }
                
                $offset += $count;
            } while ($shouldContinue);
            
        } catch (\Exception $e) {
            $this->error("Error parsing houses: {$e->getMessage()}");
            $this->statistics['houses']['errors']++;
        }
    }
    
    /**
     * Парсинг участков
     */
    protected function parsePlots(): void
    {
        $offset = (int) $this->option('offset');
        $limit = (int) $this->option('limit');
        $count = 100;
        $parsed = 0;
        
        $this->info("Fetching plots list...");
        
        try {
            $apiClient = new \App\Services\TrendAgent\TrendAgentApiClient();
            $authResult = $apiClient->authenticate();
            if (!$authResult['success']) {
                $this->error("Authentication failed");
                return;
            }
            
            do {
                $params = [
                    'city' => $this->region,
                    'count' => $count,
                    'offset' => $offset,
                ];
                
                $data = $apiClient->getPlots($params);
                
                if (!$data || !isset($data['success']) || !$data['success'] || empty($data['data'] ?? [])) {
                    break;
                }
                
                // Сохраняем total из API (если еще не сохранен)
                // Для участков сохраняем количество поселков и участков
                if (!isset($this->statistics['by_type_total']['plots']) && isset($data['plots_count'])) {
                    $this->statistics['by_type_total']['plots'] = $data['plots_count']; // Количество участков
                }
                // Также сохраняем количество поселков, если нужно
                if (isset($data['total'])) {
                    $this->statistics['by_type_total']['villages'] = $data['total']; // Количество поселков
                }
                
                $items = $data['data'];
                $this->statistics['plots']['total'] += count($items);
                
                foreach ($items as $item) {
                    if ($limit > 0 && $parsed >= $limit) {
                        break 2;
                    }
                    
                    $plotId = $item['_id'] ?? $item['id'] ?? null;
                    if (!$plotId) {
                        continue;
                    }
                    
                    if ($this->option('save-raw')) {
                        $this->saveRawData('plots', "list_offset_{$offset}.json", $data);
                    }
                    
                    if ($this->option('details')) {
                        try {
                            $details = $apiClient->getPlotDetails($plotId);
                            if ($details && isset($details['success']) && $details['success']) {
                                $this->saveDetailsData('plots', "{$plotId}.json", $details);
                                // Сохраняем в БД
                                if ($this->shouldSaveToDb) {
                                    $this->savePlotToDb($details, $plotId);
                                }
                            } else {
                                // Сохраняем хотя бы данные из списка, если детали не получены
                                $this->saveDetailsData('plots', "{$plotId}.json", ['data' => $item, 'source' => 'list']);
                                // Пытаемся сохранить в БД из данных списка
                                if ($this->shouldSaveToDb) {
                                    $this->savePlotToDb(['data' => $item], $plotId);
                                }
                            }
                        } catch (\Exception $e) {
                            // Сохраняем данные из списка при ошибке
                            $this->saveDetailsData('plots', "{$plotId}.json", ['data' => $item, 'source' => 'list', 'error' => $e->getMessage()]);
                            // Пытаемся сохранить в БД из данных списка
                            if ($this->shouldSaveToDb) {
                                $this->savePlotToDb(['data' => $item], $plotId);
                            }
                        }
                    }
                    
                    $parsed++;
                    $this->statistics['plots']['parsed']++;
                    $this->line("  Parsed plot: {$plotId} ({$parsed})");
                }
                
                // Проверяем, нужно ли продолжать пагинацию
                $totalFromApi = $this->statistics['by_type_total']['plots'] ?? null;
                
                // Продолжаем, если:
                // 1. Получили полную страницу (count($items) === $count)
                // 2. ИЛИ если есть total из API и offset + count < total (есть еще данные)
                // 3. И limit не достигнут
                $shouldContinue = false;
                if (count($items) === $count) {
                    $shouldContinue = true;
                } elseif ($totalFromApi !== null && ($offset + $count) < $totalFromApi) {
                    $shouldContinue = true;
                }
                
                if ($limit > 0 && $parsed >= $limit) {
                    $shouldContinue = false;
                }
                
                $offset += $count;
            } while ($shouldContinue);
            
        } catch (\Exception $e) {
            $this->error("Error parsing plots: {$e->getMessage()}");
            $this->statistics['plots']['errors']++;
        }
    }
    
    /**
     * Парсинг коммерции
     */
    protected function parseCommercial(): void
    {
        $offset = (int) $this->option('offset');
        $limit = (int) $this->option('limit');
        $count = 100;
        $parsed = 0;
        
        $this->info("Fetching commercial list...");
        
        try {
            $apiClient = new \App\Services\TrendAgent\TrendAgentApiClient();
            $authResult = $apiClient->authenticate();
            if (!$authResult['success']) {
                $this->error("Authentication failed");
                return;
            }
            
            do {
                $params = [
                    'city' => $this->region,
                    'count' => $count,
                    'offset' => $offset,
                ];
                
                $data = $apiClient->getCommercial($params);
                
                if (!$data || !isset($data['success']) || !$data['success'] || empty($data['data'] ?? [])) {
                    break;
                }
                
                // Сохраняем total из API (если еще не сохранен)
                // Для коммерции сохраняем количество помещений
                if (!isset($this->statistics['by_type_total']['commercial']) && isset($data['total'])) {
                    $this->statistics['by_type_total']['commercial'] = $data['total']; // Количество помещений
                }
                // Также сохраняем количество ЖК с коммерцией, если нужно
                if (isset($data['blocks_count'])) {
                    $this->statistics['by_type_total']['commercial_blocks'] = $data['blocks_count'];
                }
                
                $items = $data['data'];
                $this->statistics['commercial']['total'] += count($items);
                
                foreach ($items as $item) {
                    if ($limit > 0 && $parsed >= $limit) {
                        break 2;
                    }
                    
                    $commercialId = $item['_id'] ?? $item['id'] ?? null;
                    if (!$commercialId) {
                        continue;
                    }
                    
                    if ($this->option('save-raw')) {
                        $this->saveRawData('commercial', "list_offset_{$offset}.json", $data);
                    }
                    
                    if ($this->option('details')) {
                        try {
                            $details = $apiClient->getCommercialDetails($commercialId);
                            if ($details && isset($details['success']) && $details['success']) {
                                $this->saveDetailsData('commercial', "{$commercialId}.json", $details);
                                // Сохраняем в БД
                                if ($this->shouldSaveToDb) {
                                    $this->saveCommercialToDb($details, $commercialId);
                                }
                            } else {
                                // Сохраняем хотя бы данные из списка, если детали не получены
                                $this->saveDetailsData('commercial', "{$commercialId}.json", ['data' => $item, 'source' => 'list']);
                                // Пытаемся сохранить в БД из данных списка
                                if ($this->shouldSaveToDb) {
                                    $this->saveCommercialToDb(['data' => $item], $commercialId);
                                }
                            }
                        } catch (\Exception $e) {
                            // Сохраняем данные из списка при ошибке
                            $this->saveDetailsData('commercial', "{$commercialId}.json", ['data' => $item, 'source' => 'list', 'error' => $e->getMessage()]);
                            // Пытаемся сохранить в БД из данных списка
                            if ($this->shouldSaveToDb) {
                                $this->saveCommercialToDb(['data' => $item], $commercialId);
                            }
                        }
                    }
                    
                    $parsed++;
                    $this->statistics['commercial']['parsed']++;
                    $this->line("  Parsed commercial: {$commercialId} ({$parsed})");
                }
                
                // Проверяем, нужно ли продолжать пагинацию
                $totalFromApi = $this->statistics['by_type_total']['commercial'] ?? null;
                
                // Продолжаем, если:
                // 1. Получили полную страницу (count($items) === $count)
                // 2. ИЛИ если есть total из API и offset + count < total (есть еще данные)
                // 3. И limit не достигнут
                $shouldContinue = false;
                if (count($items) === $count) {
                    $shouldContinue = true;
                } elseif ($totalFromApi !== null && ($offset + $count) < $totalFromApi) {
                    $shouldContinue = true;
                }
                
                if ($limit > 0 && $parsed >= $limit) {
                    $shouldContinue = false;
                }
                
                $offset += $count;
            } while ($shouldContinue);
            
        } catch (\Exception $e) {
            $this->error("Error parsing commercial: {$e->getMessage()}");
            $this->statistics['commercial']['errors']++;
        }
    }

    /**
     * Парсинг подрядчиков (проектов домов)
     */
    protected function parseContractors(): void
    {
        $offset = (int) $this->option('offset');
        $limit = (int) $this->option('limit');
        $count = 20;
        $parsed = 0;
        
        $this->info("Fetching contractors list...");
        
        try {
            $apiClient = new \App\Services\TrendAgent\TrendAgentApiClient();
            $authResult = $apiClient->authenticate();
            if (!$authResult['success']) {
                $this->error("Authentication failed");
                return;
            }
            
            do {
                $params = [
                    'city' => $this->region,
                    'count' => $count,
                    'offset' => $offset,
                ];
                
                $data = $apiClient->getContractors($params);
                
                if (!$data || !isset($data['success']) || !$data['success'] || empty($data['data'] ?? [])) {
                    break;
                }
                
                // Сохраняем total из API
                if (!isset($this->statistics['by_type_total']['contractors']) && isset($data['total'])) {
                    $this->statistics['by_type_total']['contractors'] = $data['total'];
                }
                
                $items = $data['data'];
                $this->statistics['contractors']['total'] += count($items);
                
                foreach ($items as $item) {
                    if ($limit > 0 && $parsed >= $limit) {
                        break 2;
                    }
                    
                    $contractorId = $item['_id'] ?? $item['id'] ?? null;
                    if (!$contractorId) {
                        continue;
                    }
                    
                    if ($this->option('save-raw')) {
                        $this->saveRawData('contractors', "list_offset_{$offset}.json", $data);
                    }
                    
                    if ($this->option('details')) {
                        try {
                            $details = $apiClient->getContractorProjectDetails($contractorId);
                            if ($details && isset($details['success']) && $details['success']) {
                                $this->saveDetailsData('contractors', "{$contractorId}.json", $details);
                                // Сохраняем в БД
                                if ($this->shouldSaveToDb) {
                                    $this->saveContractorToDb($details, $contractorId);
                                }
                            } else {
                                // Сохраняем хотя бы данные из списка, если детали не получены
                                $this->saveDetailsData('contractors', "{$contractorId}.json", ['data' => $item, 'source' => 'list']);
                                // Пытаемся сохранить в БД из данных списка
                                if ($this->shouldSaveToDb) {
                                    $this->saveContractorToDb(['data' => $item], $contractorId);
                                }
                            }
                        } catch (\Exception $e) {
                            // Сохраняем данные из списка при ошибке
                            $this->saveDetailsData('contractors', "{$contractorId}.json", ['data' => $item, 'source' => 'list', 'error' => $e->getMessage()]);
                            // Пытаемся сохранить в БД из данных списка
                            if ($this->shouldSaveToDb) {
                                $this->saveContractorToDb(['data' => $item], $contractorId);
                            }
                        }
                    }
                    
                    $parsed++;
                    $this->statistics['contractors']['parsed']++;
                    $this->line("  Parsed contractor: {$contractorId} ({$parsed})");
                }
                
                // Проверяем, нужно ли продолжать пагинацию
                $totalFromApi = $this->statistics['by_type_total']['contractors'] ?? null;
                
                // Продолжаем, если:
                // 1. Получили полную страницу (count($items) === $count)
                // 2. ИЛИ если есть total из API и offset + count < total (есть еще данные)
                // 3. И limit не достигнут
                $shouldContinue = false;
                if (count($items) === $count) {
                    $shouldContinue = true;
                } elseif ($totalFromApi !== null && ($offset + $count) < $totalFromApi) {
                    $shouldContinue = true;
                }
                
                if ($limit > 0 && $parsed >= $limit) {
                    $shouldContinue = false;
                }
                
                $offset += $count;
            } while ($shouldContinue);
            
        } catch (\Exception $e) {
            $this->error("Error parsing contractors: {$e->getMessage()}");
            $this->statistics['contractors']['errors']++;
        }
    }
    
    /**
     * Получить список объектов
     */
    protected function fetchObjectsList(string $type, int $offset, int $count): ?array
    {
        try {
            // Используем TrendAgentApiClient для правильных запросов
            $apiClient = new \App\Services\TrendAgent\TrendAgentApiClient();
            
            // Аутентификация
            $authResult = $apiClient->authenticate();
            if (!$authResult['success']) {
                $this->error("Authentication failed: " . ($authResult['message'] ?? 'Unknown error'));
                return null;
            }
            
            // Получаем список объектов через правильный API
            $result = $apiClient->getObjectsList($this->region, $type, $count, $offset);
            
            if (!isset($result['success']) || !$result['success']) {
                $this->error("Error fetching objects list: " . ($result['message'] ?? 'Unknown error'));
                return null;
            }
            
            return $result;
            
        } catch (\Exception $e) {
            $this->error("Error fetching objects list: {$e->getMessage()}");
            return null;
        }
    }
    
    /**
     * Получить список квартир
     */
    protected function fetchApartmentsList(int $offset, int $count): ?array
    {
        try {
            // Используем TrendAgentApiClient для правильных запросов
            $apiClient = new \App\Services\TrendAgent\TrendAgentApiClient();
            
            // Аутентификация
            $authResult = $apiClient->authenticate();
            if (!$authResult['success']) {
                $this->error("Authentication failed: " . ($authResult['message'] ?? 'Unknown error'));
                return null;
            }
            
            // Получаем список квартир через правильный API
            $params = [
                'city' => $this->region,
                'count' => $count,
                'offset' => $offset,
            ];
            
            $result = $apiClient->getApartments($params);
            
            if (!isset($result['success']) || !$result['success']) {
                $this->error("Error fetching apartments list: " . ($result['message'] ?? 'Unknown error'));
                return null;
            }
            
            return $result;
            
        } catch (\Exception $e) {
            $this->error("Error fetching apartments list: {$e->getMessage()}");
            return null;
        }
    }
    
    /**
     * Получить детали комплекса
     */
    protected function fetchComplexDetails(string $complexId): ?array
    {
        try {
            // Используем TrendAgentApiClient для правильных запросов
            $apiClient = new \App\Services\TrendAgent\TrendAgentApiClient();
            
            // Аутентификация
            $authResult = $apiClient->authenticate();
            if (!$authResult['success']) {
                $this->error("Authentication failed: " . ($authResult['message'] ?? 'Unknown error'));
                return null;
            }
            
            // Получаем детали комплекса через правильный API
            $result = $apiClient->getApartmentDetails($complexId);
            
            if (!isset($result['success']) || !$result['success']) {
                $this->error("Error fetching complex details: " . ($result['message'] ?? 'Unknown error'));
                return null;
            }
            
            return $result;
            
        } catch (\Exception $e) {
            $this->error("Error fetching complex details: {$e->getMessage()}");
            return null;
        }
    }
    
    /**
     * Получить детали квартиры
     */
    protected function fetchApartmentDetails(?string $blockId, string $apartmentId): ?array
    {
        try {
            // Используем TrendAgentApiClient для правильных запросов
            $apiClient = new \App\Services\TrendAgent\TrendAgentApiClient();
            
            // Аутентификация
            $authResult = $apiClient->authenticate();
            if (!$authResult['success']) {
                $this->error("Authentication failed: " . ($authResult['message'] ?? 'Unknown error'));
                return null;
            }
            
            // Получаем детали квартиры через правильный API
            // Метод getApartmentFlatDetails поддерживает null для blockId (использует fallback)
            $result = $apiClient->getApartmentFlatDetails($blockId ?? '', $apartmentId);
            
            if (!isset($result['success']) || !$result['success']) {
                // Не логируем ошибку здесь, чтобы не засорять вывод
                // Ошибка будет обработана в parseApartmentDetails
                return null;
            }
            
            return $result;
            
        } catch (\Exception $e) {
            // Не логируем ошибку здесь, чтобы не засорять вывод
            // Ошибка будет обработана в parseApartmentDetails
            return null;
        }
    }
    
    /**
     * Скачать изображения объекта
     */
    protected function downloadObjectImages(string $type, string $objectId, array $data): void
    {
        // Проверяем, существует ли метод
        if (!method_exists($this->imageDownloader, 'extractImageUrls')) {
            $this->warn("ImageDownloader::extractImageUrls() method not found. Skipping image download.");
            return;
        }
        
        $imageUrls = $this->imageDownloader->extractImageUrls($data);
        
        foreach ($imageUrls as $category => $urls) {
            if (empty($urls)) {
                continue;
            }
            
            $this->statistics['images']['total'] += count($urls);
            
            $downloaded = $this->imageDownloader->downloadMultiple($urls, $type, $objectId, $category);
            $this->statistics['images']['downloaded'] += count($downloaded);
            
            $this->line("  Downloaded {$category} images: " . count($downloaded) . "/" . count($urls));
        }
    }
    
    /**
     * Обновить URL изображений в данных
     */
    protected function updateImageUrlsInData(array $data, string $type, string $objectId): array
    {
        // Извлекаем все URL
        $imageUrls = $this->imageDownloader->extractImageUrls($data);
        $allUrls = array_merge(
            $imageUrls['gallery'] ?? [],
            $imageUrls['plans'] ?? [],
            $imageUrls['views'] ?? []
        );
        
        // Скачиваем и получаем маппинг
        $downloadedImages = [];
        foreach ($allUrls as $url) {
            $result = $this->imageDownloader->download($url, $type, $objectId);
            if ($result) {
                $downloadedImages[] = $result;
            }
        }
        
        // Обновляем URL в данных
        return $this->imageDownloader->updateImageUrls($data, $downloadedImages);
    }
    
    /**
     * Сохранить сырые данные
     */
    protected function saveRawData(string $type, string $filename, array $data): void
    {
        $path = "{$this->basePath}/raw/{$type}/{$filename}";
        $directory = dirname($path);
        
        // Создаем директорию с правильными правами
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
            chmod($directory, 0755);
        }
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // Сохраняем с проверкой прав
        if (is_writable($directory)) {
            file_put_contents($path, $json);
            chmod($path, 0644);
        } else {
            // Fallback на Storage facade
            $relativePath = "trendagent/parsing/{$this->region}/raw/{$type}/{$filename}";
            \Illuminate\Support\Facades\Storage::put($relativePath, $json);
        }
    }
    
    /**
     * Сохранить детальные данные
     */
    protected function saveDetailsData(string $type, string $filename, array $data): void
    {
        $path = "{$this->basePath}/details/{$type}/{$filename}";
        $directory = dirname($path);
        
        // Создаем директорию с правильными правами
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
            chmod($directory, 0755);
        }
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // Сохраняем с проверкой прав
        if (is_writable($directory)) {
            file_put_contents($path, $json);
            chmod($path, 0644);
        } else {
            // Fallback на Storage facade
            $relativePath = "trendagent/parsing/{$this->region}/details/{$type}/{$filename}";
            \Illuminate\Support\Facades\Storage::put($relativePath, $json);
        }
    }
    
    /**
     * Логировать ошибку
     */
    protected function logError(string $type, string $objectId, string $message): void
    {
        $errorsPath = "{$this->basePath}/metadata/errors.json";
        $directory = dirname($errorsPath);
        
        // Создаем директорию с правильными правами
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
            chmod($directory, 0755);
        }
        
        $errors = [];
        
        if (file_exists($errorsPath)) {
            $errors = json_decode(file_get_contents($errorsPath), true) ?? [];
        }
        
        $errors[] = [
            'type' => $type,
            'object_id' => $objectId,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];
        
        $json = json_encode($errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // Сохраняем с проверкой прав
        if (is_writable($directory) || is_writable(dirname($errorsPath))) {
            file_put_contents($errorsPath, $json);
            chmod($errorsPath, 0644);
        } else {
            // Fallback на Storage facade
            $relativePath = "trendagent/parsing/{$this->region}/metadata/errors.json";
            \Illuminate\Support\Facades\Storage::put($relativePath, $json);
        }
    }
    
    /**
     * Сохранить статистику
     */
    protected function saveStatistics(): void
    {
        $statisticsPath = "{$this->basePath}/metadata/statistics.json";
        $directory = dirname($statisticsPath);
        
        // Создаем директорию с правильными правами
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
            // Устанавливаем права на запись для веб-сервера
            chmod($directory, 0755);
        }
        
        $statistics = [
            'region' => $this->region,
            'parsed_at' => now()->toIso8601String(),
            'statistics' => $this->statistics,
        ];
        
        $json = json_encode($statistics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // Используем Storage facade для надежности
        $relativePath = "trendagent/parsing/{$this->region}/metadata/statistics.json";
        try {
            \Illuminate\Support\Facades\Storage::put($relativePath, $json);
        } catch (\Exception $e) {
            // Fallback на file_put_contents с проверкой прав
            if (is_writable($directory) || is_writable(dirname($statisticsPath))) {
                file_put_contents($statisticsPath, $json);
                chmod($statisticsPath, 0644);
            } else {
                throw new \Exception("Не удалось сохранить статистику: нет прав на запись в {$directory}");
            }
        }
    }
    
    /**
     * Отобразить статистику
     */
    protected function displayStatistics(): void
    {
        $this->info("\n=== Statistics ===");
        foreach ($this->statistics as $type => $stats) {
            // Пропускаем тип 'images' и 'by_type_total', так как у них другая структура
            if ($type === 'images' || $type === 'by_type_total') {
                continue;
            }
            
            if (is_array($stats)) {
                $parsed = $stats['parsed'] ?? 0;
                $total = $stats['total'] ?? 0;
                $errors = $stats['errors'] ?? 0;
                
                // Выводим только если есть данные или ошибки
                if ($total > 0 || $parsed > 0 || $errors > 0) {
                    $this->line("{$type}: {$parsed}/{$total} (errors: {$errors})");
                }
            }
        }
    }
    
    /**
     * Вывод точных данных из API в указанном формате
     */
    protected function displayExactData(): void
    {
        $this->newLine();
        $this->info("📊 Точные данные из API:");
        $this->newLine();
        
        // Комплексы (ЖК)
        $complexesTotal = $this->statistics['by_type_total']['complexes'] ?? $this->statistics['complexes']['total'] ?? 0;
        $this->line("Комплексы (ЖК): " . number_format($complexesTotal, 0, ',', ' '));
        
        // Подрядчики
        $contractorsTotal = $this->statistics['by_type_total']['contractors'] ?? $this->statistics['contractors']['total'] ?? 0;
        $this->line("Подрядчики: " . number_format($contractorsTotal, 0, ',', ' '));
        
        // Поселки
        $villagesTotal = $this->statistics['by_type_total']['villages'] ?? 0;
        $this->line("Поселки: " . number_format($villagesTotal, 0, ',', ' '));
        
        $this->newLine();
        
        // Квартиры
        $apartmentsTotal = $this->statistics['by_type_total']['apartments'] ?? $this->statistics['apartments']['total'] ?? 0;
        $this->line("Квартиры: " . number_format($apartmentsTotal, 0, ',', ' '));
        
        // Паркинги (машиноместа)
        $parkingsTotal = $this->statistics['by_type_total']['parkings'] ?? $this->statistics['parkings']['total'] ?? 0;
        $this->line("Паркинги (машиноместа): " . number_format($parkingsTotal, 0, ',', ' '));
        
        // Дома
        $housesTotal = $this->statistics['by_type_total']['houses'] ?? $this->statistics['houses']['total'] ?? 0;
        $this->line("Дома: " . number_format($housesTotal, 0, ',', ' '));
        
        // Участки
        $plotsTotal = $this->statistics['by_type_total']['plots'] ?? $this->statistics['plots']['total'] ?? 0;
        $this->line("Участки: " . number_format($plotsTotal, 0, ',', ' '));
        
        // Коммерция (помещения)
        $commercialTotal = $this->statistics['by_type_total']['commercial'] ?? $this->statistics['commercial']['total'] ?? 0;
        $this->line("Коммерция (помещения): " . number_format($commercialTotal, 0, ',', ' '));
        
        // Проекты домов (это то же самое, что подрядчики)
        $houseProjectsTotal = $this->statistics['by_type_total']['contractors'] ?? $this->statistics['contractors']['total'] ?? 0;
        $this->line("Проекты домов: " . number_format($houseProjectsTotal, 0, ',', ' '));
        
        $this->newLine();
    }
    
    /**
     * Получить название региона
     */
    protected function getRegionName(string $code): string
    {
        // Используем CityService для получения названия города
        $cityName = CityService::getCityName($code);
        if ($cityName) {
            return $cityName;
        }
        
        // Fallback на старый метод для обратной совместимости
        return match($code) {
            'spb' => 'Санкт-Петербург',
            'msk' => 'Москва',
            'ekb' => 'Екатеринбург',
            'nsk' => 'Новосибирск',
            'krd' => 'Краснодарский край',
            'rnd' => 'Ростов-на-Дону',
            'crimea' => 'Крым',
            'kzn' => 'Казань',
            'ufa' => 'Уфа',
            'dubai' => 'ОАЭ',
            default => ucfirst($code),
        };
    }

    /**
     * Сохранить комплекс в БД
     */
    protected function saveComplexToDb(array $data, string $externalId): void
    {
        try {
            // Извлекаем данные из структуры ответа API
            $complexData = $data['data']['data'] ?? $data['data'] ?? $data;
            
            // Извлекаем координаты из geometry
            $latitude = null;
            $longitude = null;
            if (isset($complexData['geometry']['coordinates']) && is_array($complexData['geometry']['coordinates'])) {
                $longitude = $complexData['geometry']['coordinates'][0] ?? null;
                $latitude = $complexData['geometry']['coordinates'][1] ?? null;
            }

            // Подготавливаем массивы для JSON полей (Laravel автоматически конвертирует через casts)
            $images = $complexData['plan'] ?? $complexData['images'] ?? [];
            $advantages = $complexData['advantage'] ?? $complexData['advantages'] ?? [];
            $nearbyPlaces = $complexData['nearby_places'] ?? [];
            $videos = $complexData['videos'] ?? [];
            $files = $complexData['files'] ?? [];
            
            if (!is_array($images)) $images = [];
            if (!is_array($advantages)) $advantages = [];
            if (!is_array($nearbyPlaces)) $nearbyPlaces = [];
            if (!is_array($videos)) $videos = [];
            if (!is_array($files)) $files = [];
            
            $dbData = [
                'region_id' => $this->regionModel->id,
                'external_id' => $externalId,
                'guid' => $complexData['guid'] ?? $complexData['slug'] ?? null,
                'name' => $complexData['name'] ?? '',
                'address' => $complexData['address'] ?? null,
                'description' => $complexData['description'] ?? null,
                'latitude' => $latitude ?? $complexData['latitude'] ?? $complexData['location']['latitude'] ?? null,
                'longitude' => $longitude ?? $complexData['longitude'] ?? $complexData['location']['longitude'] ?? null,
                'developer_name' => $complexData['developer']['name'] ?? null,
                'class_type' => $complexData['class_type'] ?? null,
                'deadline' => $complexData['deadline'] ?? null,
                'status' => $complexData['status'] ?? (is_numeric($complexData['status'] ?? null) ? (string)$complexData['status'] : null),
                'min_price' => $complexData['min_price'] ?? null,
                'images' => $images,
                'advantages' => $advantages,
                'nearby_places' => $nearbyPlaces,
                'videos' => $videos,
                'files' => $files,
                'raw_data' => $complexData,
            ];

            // Используем модель для правильной обработки casts
            $complex = Complex::firstOrNew(['external_id' => $externalId]);
            
            // Устанавливаем обычные поля
            foreach ($dbData as $key => $value) {
                if (!in_array($key, ['images', 'advantages', 'nearby_places', 'videos', 'files', 'raw_data'])) {
                    $complex->setAttribute($key, $value);
                }
            }
            
            // Явно устанавливаем JSON поля (Laravel автоматически конвертирует через casts)
            $complex->setAttribute('images', $images);
            $complex->setAttribute('advantages', $advantages);
            $complex->setAttribute('nearby_places', $nearbyPlaces);
            $complex->setAttribute('videos', $videos);
            $complex->setAttribute('files', $files);
            $complex->setAttribute('raw_data', $complexData);
            
            $complex->save();
            
            if ($this->option('verbose')) {
                if ($complex->wasRecentlyCreated) {
                    $this->line("  ✅ Комплекс {$externalId} создан в БД");
                }
            }
        } catch (\Exception $e) {
            $errorMsg = "Ошибка сохранения комплекса {$externalId} в БД: {$e->getMessage()}";
            $this->error("  ❌ {$errorMsg}");
            Log::error("Parser: Failed to save complex", [
                'external_id' => $externalId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Сохранить квартиру в БД
     */
    protected function saveApartmentToDb(array $details, string $apartmentId, array $listItem = []): void
    {
        try {
            // Извлекаем данные из структуры ответа API
            // API может возвращать данные в разных форматах:
            // 1. {success: true, data: {data: {...}}}
            // 2. {success: true, data: {...}}
            // 3. {...}
            $apartmentData = null;
            if (!empty($details)) {
                if (isset($details['data']['data'])) {
                    $apartmentData = $details['data']['data'];
                } elseif (isset($details['data'])) {
                    $apartmentData = $details['data'];
                } else {
                    $apartmentData = $details;
                }
            }
            
            // Если данных нет в details, используем listItem
            if (empty($apartmentData) || (!isset($apartmentData['_id']) && !isset($apartmentData['id']))) {
                $apartmentData = $listItem;
            }

            // Если все еще нет данных, пропускаем
            if (empty($apartmentData)) {
                return;
            }

            $externalId = $apartmentData['_id'] ?? $apartmentData['id'] ?? $apartmentId;
            
            // Находим комплекс
            $complexId = null;
            $blockId = $apartmentData['block_id'] ?? $apartmentData['block']['_id'] ?? $apartmentData['block'] ?? null;
            if ($blockId) {
                $complex = Complex::where('external_id', $blockId)->first();
                $complexId = $complex?->id;
            }

            // Подготавливаем массивы для JSON полей (Laravel автоматически конвертирует через casts)
            $images = $apartmentData['images'] ?? $apartmentData['gallery_images'] ?? [];
            if (!is_array($images)) {
                $images = [];
            }
            
            // Извлекаем rooms - может быть объектом {crm_id: 0, name: "Студии"} или числом
            $rooms = null;
            if (isset($apartmentData['rooms']) && is_numeric($apartmentData['rooms'])) {
                $rooms = (int)$apartmentData['rooms'];
            } elseif (isset($apartmentData['room'])) {
                if (is_array($apartmentData['room']) && isset($apartmentData['room']['crm_id'])) {
                    $rooms = (int)$apartmentData['room']['crm_id'];
                } elseif (is_numeric($apartmentData['room'])) {
                    $rooms = (int)$apartmentData['room'];
                }
            }
            
            // Извлекаем площади - должны быть числами
            $areaTotal = $apartmentData['area_total'] ?? $apartmentData['area_given'] ?? $apartmentData['area'] ?? $apartmentData['square'] ?? null;
            if ($areaTotal !== null && !is_numeric($areaTotal)) {
                $areaTotal = null;
            } elseif ($areaTotal !== null) {
                $areaTotal = (float)$areaTotal;
            }
            
            $areaLiving = $apartmentData['area_living'] ?? $apartmentData['living_area'] ?? null;
            if ($areaLiving !== null && !is_numeric($areaLiving)) {
                $areaLiving = null;
            } elseif ($areaLiving !== null) {
                $areaLiving = (float)$areaLiving;
            }
            
            $areaKitchen = $apartmentData['area_kitchen'] ?? $apartmentData['kitchen_area'] ?? null;
            if ($areaKitchen !== null && !is_numeric($areaKitchen)) {
                $areaKitchen = null;
            } elseif ($areaKitchen !== null) {
                $areaKitchen = (float)$areaKitchen;
            }
            
            // Извлекаем floor - должен быть числом
            $floor = $apartmentData['floor'] ?? null;
            if ($floor !== null && !is_numeric($floor)) {
                $floor = null;
            } elseif ($floor !== null) {
                $floor = (int)$floor;
            }
            
            // Извлекаем цены - должны быть числами
            $priceBase = $apartmentData['price_base'] ?? $apartmentData['price'] ?? null;
            if ($priceBase !== null && !is_numeric($priceBase)) {
                $priceBase = null;
            } elseif ($priceBase !== null) {
                $priceBase = (int)$priceBase;
            }
            
            $priceFull = $apartmentData['price_full'] ?? null;
            if ($priceFull !== null && !is_numeric($priceFull)) {
                $priceFull = null;
            } elseif ($priceFull !== null) {
                $priceFull = (int)$priceFull;
            }
            
            $pricePerSqm = $apartmentData['price_per_sqm'] ?? null;
            if ($pricePerSqm !== null && !is_numeric($pricePerSqm)) {
                $pricePerSqm = null;
            } elseif ($pricePerSqm !== null) {
                $pricePerSqm = (int)$pricePerSqm;
            }
            
            $dbData = [
                'complex_id' => $complexId,
                'external_id' => $externalId,
                'number' => $apartmentData['number'] ?? null,
                'rooms' => $rooms,
                'area_total' => $areaTotal,
                'area_living' => $areaLiving,
                'area_kitchen' => $areaKitchen,
                'floor' => $floor,
                'price_base' => $priceBase,
                'price_full' => $priceFull,
                'price_per_sqm' => $pricePerSqm,
                'is_exclusive' => (bool)($apartmentData['is_exclusive'] ?? false),
                'is_booked' => (bool)($apartmentData['is_booked'] ?? false),
                'is_on_request' => (bool)($apartmentData['is_on_request'] ?? false),
                'plan_image_url' => $apartmentData['plan_image']['url'] ?? $apartmentData['plan_image_url'] ?? null,
            ];

            // Используем модель для правильной обработки casts
            // Явно устанавливаем JSON поля через setAttribute для правильной конвертации
            $apartment = Apartment::firstOrNew(['external_id' => $externalId]);
            
            // Устанавливаем обычные поля
            foreach ($dbData as $key => $value) {
                if ($key !== 'images' && $key !== 'raw_data') {
                    $apartment->setAttribute($key, $value);
                }
            }
            
            // Явно устанавливаем JSON поля (Laravel автоматически конвертирует через casts)
            $apartment->setAttribute('images', $images);
            $apartment->setAttribute('raw_data', $apartmentData);
            
            $apartment->save();
            
            // Логируем только каждую 1000-ю квартиру, чтобы не засорять вывод
            if (($this->statistics['apartments']['parsed'] ?? 0) % 1000 == 0 && $this->option('verbose')) {
                if ($apartment->wasRecentlyCreated) {
                    $this->line("  ✅ Квартира {$externalId} создана в БД (всего: {$this->statistics['apartments']['parsed']})");
                }
            }
        } catch (\Exception $e) {
            $errorMsg = "Ошибка сохранения квартиры {$apartmentId} в БД: {$e->getMessage()}";
            // Логируем только каждую 100-ю ошибку, чтобы не засорять вывод
            if (($this->statistics['apartments']['parsed'] ?? 0) % 100 == 0) {
                $this->error("  ❌ {$errorMsg}");
            }
            Log::error("Parser: Failed to save apartment", [
                'external_id' => $apartmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Сохранить паркинг в БД
     */
    protected function saveParkingToDb(array $data, string $externalId): void
    {
        try {
            $parkingData = $data['data'] ?? $data;
            
            $complexId = null;
            $blockId = $parkingData['block_id'] ?? $parkingData['block'] ?? null;
            if ($blockId) {
                $complex = Complex::where('external_id', $blockId)->first();
                $complexId = $complex?->id;
            }

            // Подготавливаем массивы для JSON полей
            $images = $parkingData['images'] ?? [];
            if (!is_array($images)) $images = [];
            
            $dbData = [
                'complex_id' => $complexId,
                'external_id' => $externalId,
                'name' => $parkingData['name'] ?? null,
                'total_places' => $parkingData['total_places'] ?? null,
                'available_places' => $parkingData['available_places'] ?? null,
                'price_base' => $parkingData['price_base'] ?? $parkingData['price'] ?? null,
                'price_per_month' => $parkingData['price_per_month'] ?? null,
                'images' => $images,
                'raw_data' => $parkingData,
            ];

            // Используем модель для правильной обработки casts
            $parking = Parking::firstOrNew(['external_id' => $externalId]);
            $parking->fill($dbData);
            $parking->save();
        } catch (\Exception $e) {
            $this->warn("  ⚠️  Ошибка сохранения паркинга {$externalId} в БД: {$e->getMessage()}");
        }
    }

    /**
     * Сохранить дом в БД
     */
    protected function saveHouseToDb(array $data, string $externalId): void
    {
        try {
            $houseData = $data['data']['data'] ?? $data['data'] ?? $data;

            // Подготавливаем массивы для JSON полей
            $images = $houseData['images'] ?? [];
            if (!is_array($images)) $images = [];
            
            $dbData = [
                'region_id' => $this->regionModel->id,
                'external_id' => $externalId,
                'guid' => $houseData['guid'] ?? $houseData['slug'] ?? null,
                'name' => $houseData['name'] ?? null,
                'address' => $houseData['address'] ?? null,
                'land_area' => $houseData['land_area'] ?? null,
                'house_area' => $houseData['house_area'] ?? $houseData['area'] ?? null,
                'floors_count' => $houseData['floors_count'] ?? null,
                'rooms_count' => $houseData['rooms_count'] ?? $houseData['rooms'] ?? null,
                'price_base' => $houseData['price_base'] ?? $houseData['price'] ?? null,
                'images' => $images,
                'raw_data' => $houseData,
            ];

            // Используем модель для правильной обработки casts
            $house = House::firstOrNew(['external_id' => $externalId]);
            $house->fill($dbData);
            $house->save();
        } catch (\Exception $e) {
            $this->warn("  ⚠️  Ошибка сохранения дома {$externalId} в БД: {$e->getMessage()}");
        }
    }

    /**
     * Сохранить участок в БД
     */
    protected function savePlotToDb(array $data, string $externalId): void
    {
        try {
            $plotData = $data['data']['data'] ?? $data['data'] ?? $data;

            // Создаем или получаем поселок
            $settlementId = null;
            if (isset($plotData['village_id']) || isset($plotData['village'])) {
                $villageId = $plotData['village_id'] ?? $plotData['village'];
                $settlement = PlotSettlement::firstOrCreate(
                    ['external_id' => $villageId],
                    [
                        'region_id' => $this->regionModel->id,
                        'name' => $plotData['village_name'] ?? 'Неизвестный поселок',
                    ]
                );
                $settlementId = $settlement->id;
            }

            // Подготавливаем массивы для JSON полей
            $utilities = $plotData['utilities'] ?? [];
            if (!is_array($utilities)) $utilities = [];
            
            $dbData = [
                'region_id' => $this->regionModel->id,
                'settlement_id' => $settlementId,
                'external_id' => $externalId,
                'number' => $plotData['number'] ?? null,
                'area' => $plotData['area'] ?? null,
                'cadastral_number' => $plotData['cadastral_number'] ?? null,
                'price_base' => $plotData['price_base'] ?? $plotData['price'] ?? null,
                'utilities' => $utilities,
                'raw_data' => $plotData,
            ];

            // Используем модель для правильной обработки casts
            $plot = Plot::firstOrNew(['external_id' => $externalId]);
            $plot->fill($dbData);
            $plot->save();
        } catch (\Exception $e) {
            $this->warn("  ⚠️  Ошибка сохранения участка {$externalId} в БД: {$e->getMessage()}");
        }
    }

    /**
     * Сохранить коммерцию в БД
     */
    protected function saveCommercialToDb(array $data, string $externalId): void
    {
        try {
            $commercialData = $data['data']['data'] ?? $data['data'] ?? $data;

            $complexId = null;
            $blockId = $commercialData['block_id'] ?? $commercialData['block'] ?? null;
            if ($blockId) {
                $complex = Complex::where('external_id', $blockId)->first();
                $complexId = $complex?->id;
            }

            // Подготавливаем массивы для JSON полей
            $images = $commercialData['images'] ?? [];
            if (!is_array($images)) $images = [];
            
            $dbData = [
                'complex_id' => $complexId,
                'external_id' => $externalId,
                'name' => $commercialData['name'] ?? null,
                'area_total' => $commercialData['area_total'] ?? $commercialData['area'] ?? null,
                'price_base' => $commercialData['price_base'] ?? $commercialData['price'] ?? null,
                'rent_price' => $commercialData['rent_price'] ?? null,
                'floor' => $commercialData['floor'] ?? null,
                'images' => $images,
                'raw_data' => $commercialData,
            ];

            // Используем модель для правильной обработки casts
            $commercial = Commercial::firstOrNew(['external_id' => $externalId]);
            $commercial->fill($dbData);
            $commercial->save();
        } catch (\Exception $e) {
            $this->warn("  ⚠️  Ошибка сохранения коммерции {$externalId} в БД: {$e->getMessage()}");
        }
    }

    /**
     * Сохранить подрядчика в БД
     */
    protected function saveContractorToDb(array $data, string $externalId): void
    {
        try {
            $contractorData = $data['data']['data'] ?? $data['data'] ?? $data;

            $dbData = [
                'external_id' => $externalId,
                'name' => $contractorData['name'] ?? '',
                'description' => $contractorData['description'] ?? null,
                'logo_url' => $contractorData['logo_url'] ?? $contractorData['logo']['url'] ?? null,
                'website' => $contractorData['website'] ?? null,
                'contact_phone' => $contractorData['contact_phone'] ?? null,
                'contact_email' => $contractorData['contact_email'] ?? null,
                'raw_data' => $contractorData,
            ];

            // Используем модель для правильной обработки casts
            $contractor = Contractor::firstOrNew(['external_id' => $externalId]);
            $contractor->fill($dbData);
            $contractor->save();

            // Импортируем проекты подрядчика
            if (isset($contractorData['projects']) && is_array($contractorData['projects'])) {
                foreach ($contractorData['projects'] as $projectData) {
                    $projectId = $projectData['_id'] ?? $projectData['id'] ?? null;
                    if ($projectId) {
                        $projectDbData = [
                            'contractor_id' => $contractor->id,
                            'external_id' => $projectId,
                            'guid' => $projectData['guid'] ?? $projectData['slug'] ?? null,
                            'name' => $projectData['name'] ?? '',
                            'description' => $projectData['description'] ?? null,
                            'min_price' => $projectData['min_price'] ?? null,
                            'area_total' => $projectData['area_total'] ?? null,
                            'area_living' => $projectData['area_living'] ?? null,
                            'construction_time' => $projectData['construction_time'] ?? null,
                            'technology' => $projectData['technology'] ?? null,
                            'images' => $projectData['images'] ?? [],
                            'raw_data' => $projectData,
                        ];

                        ContractorProject::updateOrCreate(
                            ['external_id' => $projectId],
                            $projectDbData
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            $this->warn("  ⚠️  Ошибка сохранения подрядчика {$externalId} в БД: {$e->getMessage()}");
        }
    }

    /**
     * Вывести сводную статистику по всем регионам
     */
    protected function displayAllRegionsStatistics(): void
    {
        if (empty($this->allRegionsStatistics)) {
            // Если парсился один регион, используем текущую статистику
            $this->displayStatistics();
            return;
        }
        
        $this->info("📊 СВОДНАЯ СТАТИСТИКА ПО ВСЕМ РЕГИОНАМ:");
        $this->newLine();
        
        // Подсчитываем общую статистику
        $totalStats = [
            'complexes' => ['parsed' => 0, 'errors' => 0],
            'apartments' => ['parsed' => 0, 'errors' => 0],
            'parkings' => ['parsed' => 0, 'errors' => 0],
            'houses' => ['parsed' => 0, 'errors' => 0],
            'plots' => ['parsed' => 0, 'errors' => 0],
            'commercial' => ['parsed' => 0, 'errors' => 0],
            'contractors' => ['parsed' => 0, 'errors' => 0],
        ];
        
        foreach ($this->allRegionsStatistics as $regionCode => $stats) {
            foreach ($totalStats as $type => &$totals) {
                $totals['parsed'] += $stats[$type]['parsed'] ?? 0;
                $totals['errors'] += $stats[$type]['errors'] ?? 0;
            }
        }
        
        $this->table(
            ['Тип', 'Обработано', 'Ошибок'],
            [
                ['Комплексы', $totalStats['complexes']['parsed'], $totalStats['complexes']['errors']],
                ['Квартиры', $totalStats['apartments']['parsed'], $totalStats['apartments']['errors']],
                ['Паркинги', $totalStats['parkings']['parsed'], $totalStats['parkings']['errors']],
                ['Дома', $totalStats['houses']['parsed'], $totalStats['houses']['errors']],
                ['Участки', $totalStats['plots']['parsed'], $totalStats['plots']['errors']],
                ['Коммерция', $totalStats['commercial']['parsed'], $totalStats['commercial']['errors']],
                ['Подрядчики', $totalStats['contractors']['parsed'], $totalStats['contractors']['errors']],
            ]
        );
        
        $this->newLine();
        $this->info("📋 Статистика по регионам:");
        foreach ($this->allRegionsStatistics as $regionCode => $stats) {
            $regionName = $this->getRegionName($regionCode);
            $totalParsed = ($stats['complexes']['parsed'] ?? 0) + 
                          ($stats['apartments']['parsed'] ?? 0) + 
                          ($stats['parkings']['parsed'] ?? 0) + 
                          ($stats['houses']['parsed'] ?? 0) + 
                          ($stats['plots']['parsed'] ?? 0) + 
                          ($stats['commercial']['parsed'] ?? 0) + 
                          ($stats['contractors']['parsed'] ?? 0);
            $this->line("  {$regionCode} ({$regionName}): {$totalParsed} объектов");
        }
    }

    /**
     * Вывести отчет по парсингу и БД
     */
    protected function displayParsingAndDbReport(): void
    {
        $this->newLine();
        $this->info("📊 ОТЧЕТ ПО ПАРСИНГУ И БД");
        $this->newLine();
        
        // Если парсились все регионы, используем сводную статистику
        if (!empty($this->allRegionsStatistics)) {
            $totalStats = [
                'complexes' => ['parsed' => 0, 'errors' => 0],
                'apartments' => ['parsed' => 0, 'errors' => 0],
                'parkings' => ['parsed' => 0, 'errors' => 0],
                'houses' => ['parsed' => 0, 'errors' => 0],
                'plots' => ['parsed' => 0, 'errors' => 0],
                'commercial' => ['parsed' => 0, 'errors' => 0],
                'contractors' => ['parsed' => 0, 'errors' => 0],
            ];
            
            foreach ($this->allRegionsStatistics as $stats) {
                foreach ($totalStats as $type => &$totals) {
                    $totals['parsed'] += $stats[$type]['parsed'] ?? 0;
                    $totals['errors'] += $stats[$type]['errors'] ?? 0;
                }
            }
            
            $this->info("📥 Данные из парсинга (все регионы):");
            $this->table(
                ['Тип', 'Обработано', 'Ошибок'],
                [
                    ['Комплексы', $totalStats['complexes']['parsed'], $totalStats['complexes']['errors']],
                    ['Квартиры', $totalStats['apartments']['parsed'], $totalStats['apartments']['errors']],
                    ['Паркинги', $totalStats['parkings']['parsed'], $totalStats['parkings']['errors']],
                    ['Дома', $totalStats['houses']['parsed'], $totalStats['houses']['errors']],
                    ['Участки', $totalStats['plots']['parsed'], $totalStats['plots']['errors']],
                    ['Коммерция', $totalStats['commercial']['parsed'], $totalStats['commercial']['errors']],
                    ['Подрядчики', $totalStats['contractors']['parsed'], $totalStats['contractors']['errors']],
                ]
            );
        } else {
            $this->info("📥 Данные из парсинга:");
            $this->table(
                ['Тип', 'Обработано', 'Ошибок'],
                [
                    ['Комплексы', $this->statistics['complexes']['parsed'] ?? 0, $this->statistics['complexes']['errors'] ?? 0],
                    ['Квартиры', $this->statistics['apartments']['parsed'] ?? 0, $this->statistics['apartments']['errors'] ?? 0],
                    ['Паркинги', $this->statistics['parkings']['parsed'] ?? 0, $this->statistics['parkings']['errors'] ?? 0],
                    ['Дома', $this->statistics['houses']['parsed'] ?? 0, $this->statistics['houses']['errors'] ?? 0],
                    ['Участки', $this->statistics['plots']['parsed'] ?? 0, $this->statistics['plots']['errors'] ?? 0],
                    ['Коммерция', $this->statistics['commercial']['parsed'] ?? 0, $this->statistics['commercial']['errors'] ?? 0],
                    ['Подрядчики', $this->statistics['contractors']['parsed'] ?? 0, $this->statistics['contractors']['errors'] ?? 0],
                ]
            );
        }
        
        $this->newLine();
        $this->info("💾 Данные в БД:");
        $this->table(
            ['Тип', 'Записей в БД'],
            [
                ['Регионы', Region::count()],
                ['Комплексы', Complex::count()],
                ['Квартиры', Apartment::count()],
                ['Паркинги', Parking::count()],
                ['Дома', House::count()],
                ['Участки', Plot::count()],
                ['Коммерция', Commercial::count()],
                ['Подрядчики', Contractor::count()],
            ]
        );
    }

    /**
     * Очистить PID файл парсера после завершения
     */
    protected function clearParserPid(): void
    {
        try {
            $pidFile = storage_path('app/parser_pid.txt');
            if (file_exists($pidFile)) {
                // Проверяем, что процесс действительно завершен
                $pid = trim(file_get_contents($pidFile));
                if (!empty($pid)) {
                    if (PHP_OS_FAMILY === 'Windows') {
                        exec("tasklist /FI \"PID eq {$pid}\" /NH", $output);
                        $isRunning = count($output) > 0 && strpos($output[0], (string)$pid) !== false;
                    } else {
                        exec("ps -p {$pid} 2>/dev/null", $output);
                        $isRunning = count($output) > 1;
                    }
                    
                    // Если процесс не запущен, удаляем PID файл
                    if (!$isRunning) {
                        @unlink($pidFile);
                        // Также удаляем связанные файлы
                        $logFile = storage_path('app/parser_log.txt');
                        $startedAtFile = storage_path('app/parser_started_at.txt');
                        if (file_exists($logFile)) @unlink($logFile);
                        if (file_exists($startedAtFile)) @unlink($startedAtFile);
                    }
                }
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки при очистке PID
        }
    }
}
