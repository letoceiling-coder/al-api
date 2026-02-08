<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TrendAgent\TrendSsoApiAuth;
use App\Services\TrendAgent\ImageDownloader;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TrendAgentParse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trendagent:parse 
                            {--region=spb : Region code (spb, msk, etc.)}
                            {--type=all : Type of objects (all, apartments, parkings, houses, plots, commercial, complexes)}
                            {--limit=0 : Limit number of objects (0 = no limit)}
                            {--offset=0 : Offset for pagination}
                            {--details=true : Parse detailed pages}
                            {--images=true : Download images}
                            {--save-raw=true : Save raw data to files}';

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
        $this->region = $this->option('region');
        $this->basePath = storage_path("trendagent/parsing/{$this->region}");
        
        $this->info("Starting TrendAgent parsing for region: {$this->region}");
        
        // Инициализация
        $this->apiAuth = new TrendSsoApiAuth();
        $this->imageDownloader = new ImageDownloader();
        
        // Авторизация
        if (!$this->authenticate()) {
            $this->error('Authentication failed');
            return 1;
        }
        
        $type = $this->option('type');
        $types = $type === 'all' 
            ? ['complexes', 'apartments', 'parkings', 'houses', 'plots', 'commercial', 'contractors']
            : [$type];
        
        foreach ($types as $objectType) {
            $this->info("Parsing {$objectType}...");
            $this->parseType($objectType);
        }
        
        // Сохраняем статистику
        $this->saveStatistics();
        
        $this->info('Parsing completed!');
        $this->displayStatistics();
        
        // Выводим точные данные из API
        $this->newLine();
        $this->info("📊 Точные данные из API:");
        $this->displayExactData();
        
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
            
            $offset += $count;
            
        } while (count($items) === $count && ($limit === 0 || $parsed < $limit));
    }
    
    /**
     * Парсинг деталей комплекса
     */
    protected function parseComplexDetails(string $complexId, array $listItem): void
    {
        try {
            $details = $this->fetchComplexDetails($complexId);
            
            if (!$details) {
                $this->statistics['complexes']['errors']++;
                return;
            }
            
            // Сохраняем детальные данные
            if ($this->option('save-raw')) {
                $this->saveDetailsData('complexes', "{$complexId}.json", $details);
            }
            
            // Скачиваем изображения
            if ($this->option('images')) {
                $this->downloadObjectImages('complex', $complexId, $details);
            }
            
            // Обновляем URL изображений в данных
            if ($this->option('images')) {
                $details = $this->updateImageUrlsInData($details, 'complex', $complexId);
            }
            
        } catch (\Exception $e) {
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
            
            foreach ($items as $item) {
                if ($limit > 0 && $parsed >= $limit) {
                    break 2;
                }
                
                $apartmentId = $item['id'] ?? $item['_id'] ?? null;
                if (!$apartmentId) {
                    continue;
                }
                
                // Сохраняем сырые данные
                if ($this->option('save-raw')) {
                    $this->saveRawData('apartments', "list_offset_{$offset}.json", $data);
                }
                
                // Парсим детали
                if ($this->option('details')) {
                    $this->parseApartmentDetails($apartmentId, $item);
                }
                
                $parsed++;
                $this->statistics['apartments']['parsed']++;
                
                $this->line("  Parsed apartment: {$apartmentId} ({$parsed})");
            }
            
            $offset += $count;
            
        } while (count($items) === $count && ($limit === 0 || $parsed < $limit));
    }
    
    /**
     * Парсинг деталей квартиры
     */
    protected function parseApartmentDetails(string $apartmentId, array $listItem): void
    {
        try {
            // Нужен block_id для деталей квартиры
            $blockId = $listItem['block_id'] ?? $listItem['block'] ?? null;
            if (!$blockId) {
                return;
            }
            
            $details = $this->fetchApartmentDetails($blockId, $apartmentId);
            
            if (!$details) {
                $this->statistics['apartments']['errors']++;
                return;
            }
            
            // Сохраняем детальные данные
            if ($this->option('save-raw')) {
                $this->saveDetailsData('apartments', "{$apartmentId}.json", $details);
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
                        $this->saveDetailsData('parkings', "{$placeId}.json", ['data' => $item]);
                    }
                    
                    $parsed++;
                    $this->statistics['parkings']['parsed']++;
                    $this->line("  Parsed parking place: {$placeId} ({$parsed})");
                }
                
                $offset += $count;
            } while (count($items) === $count && ($limit === 0 || $parsed < $limit));
            
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
                        $details = $apiClient->getHouseDetails($houseId);
                        if ($details && isset($details['success']) && $details['success']) {
                            $this->saveDetailsData('houses', "{$houseId}.json", $details);
                        }
                    }
                    
                    $parsed++;
                    $this->statistics['houses']['parsed']++;
                    $this->line("  Parsed house: {$houseId} ({$parsed})");
                }
                
                $offset += $count;
            } while (count($items) === $count && ($limit === 0 || $parsed < $limit));
            
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
                        $details = $apiClient->getPlotDetails($plotId);
                        if ($details && isset($details['success']) && $details['success']) {
                            $this->saveDetailsData('plots', "{$plotId}.json", $details);
                        }
                    }
                    
                    $parsed++;
                    $this->statistics['plots']['parsed']++;
                    $this->line("  Parsed plot: {$plotId} ({$parsed})");
                }
                
                $offset += $count;
            } while (count($items) === $count && ($limit === 0 || $parsed < $limit));
            
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
                        $details = $apiClient->getCommercialDetails($commercialId);
                        if ($details && isset($details['success']) && $details['success']) {
                            $this->saveDetailsData('commercial', "{$commercialId}.json", $details);
                        }
                    }
                    
                    $parsed++;
                    $this->statistics['commercial']['parsed']++;
                    $this->line("  Parsed commercial: {$commercialId} ({$parsed})");
                }
                
                $offset += $count;
            } while (count($items) === $count && ($limit === 0 || $parsed < $limit));
            
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
                        $details = $apiClient->getContractorProjectDetails($contractorId);
                        if ($details && isset($details['success']) && $details['success']) {
                            $this->saveDetailsData('contractors', "{$contractorId}.json", $details);
                        }
                    }
                    
                    $parsed++;
                    $this->statistics['contractors']['parsed']++;
                    $this->line("  Parsed contractor: {$contractorId} ({$parsed})");
                }
                
                $offset += $count;
            } while (count($items) === $count && ($limit === 0 || $parsed < $limit));
            
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
    protected function fetchApartmentDetails(string $blockId, string $apartmentId): ?array
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
            $result = $apiClient->getApartmentFlatDetails($blockId, $apartmentId);
            
            if (!isset($result['success']) || !$result['success']) {
                $this->error("Error fetching apartment details: " . ($result['message'] ?? 'Unknown error'));
                return null;
            }
            
            return $result;
            
        } catch (\Exception $e) {
            $this->error("Error fetching apartment details: {$e->getMessage()}");
            return null;
        }
    }
    
    /**
     * Скачать изображения объекта
     */
    protected function downloadObjectImages(string $type, string $objectId, array $data): void
    {
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
