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
        'images' => ['total' => 0, 'downloaded' => 0, 'errors' => 0],
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
            ? ['complexes', 'apartments', 'parkings', 'houses', 'plots', 'commercial']
            : [$type];
        
        foreach ($types as $objectType) {
            $this->info("Parsing {$objectType}...");
            $this->parseType($objectType);
        }
        
        // Сохраняем статистику
        $this->saveStatistics();
        
        $this->info('Parsing completed!');
        $this->displayStatistics();
        
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
        // Аналогично parseApartments
        $this->info("Parkings parsing not yet implemented");
    }
    
    /**
     * Парсинг домов
     */
    protected function parseHouses(): void
    {
        // Аналогично parseApartments
        $this->info("Houses parsing not yet implemented");
    }
    
    /**
     * Парсинг участков
     */
    protected function parsePlots(): void
    {
        // Аналогично parseApartments
        $this->info("Plots parsing not yet implemented");
    }
    
    /**
     * Парсинг коммерции
     */
    protected function parseCommercial(): void
    {
        // Аналогично parseApartments
        $this->info("Commercial parsing not yet implemented");
    }
    
    /**
     * Получить список объектов
     */
    protected function fetchObjectsList(string $type, int $offset, int $count): ?array
    {
        try {
            $phone = env('TRENDAGENT_PHONE', '+79045393434');
            $password = env('TRENDAGENT_PASSWORD', 'nwBvh4q');
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF',
                'Content-Type' => 'application/json',
            ])->post('https://api.siteaccess.ru/trendagent/objects/list', [
                'phone' => $phone,
                'password' => $password,
                'city' => $this->region,
                'count' => $count,
                'offset' => $offset,
            ]);
            
            if (!$response->successful()) {
                $this->error("HTTP error: {$response->status()}");
                return null;
            }
            
            $data = $response->json();
            return $data['data'] ?? $data;
            
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
            $phone = env('TRENDAGENT_PHONE', '+79045393434');
            $password = env('TRENDAGENT_PASSWORD', 'nwBvh4q');
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF',
                'Content-Type' => 'application/json',
            ])->post('https://api.siteaccess.ru/trendagent/apartments', [
                'phone' => $phone,
                'password' => $password,
                'city' => $this->region,
                'count' => $count,
                'offset' => $offset,
            ]);
            
            if (!$response->successful()) {
                $this->error("HTTP error: {$response->status()}");
                return null;
            }
            
            $data = $response->json();
            return $data['data'] ?? $data;
            
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
            $phone = env('TRENDAGENT_PHONE', '+79045393434');
            $password = env('TRENDAGENT_PASSWORD', 'nwBvh4q');
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF',
                'Content-Type' => 'application/json',
            ])->post("https://api.siteaccess.ru/trendagent/apartments/{$complexId}", [
                'phone' => $phone,
                'password' => $password,
                'options' => [
                    'unified' => true,
                    'buildings' => true,
                    'apartments' => true,
                    'plans' => true,
                    'progress' => true,
                    'finishings' => true,
                    'advantages' => true,
                    'nearby_places' => true,
                    'min_price' => true,
                    'videos' => true,
                    'files' => true,
                ],
            ]);
            
            if (!$response->successful()) {
                $this->error("HTTP error: {$response->status()}");
                return null;
            }
            
            $data = $response->json();
            return $data['data'] ?? $data;
            
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
            $phone = env('TRENDAGENT_PHONE', '+79045393434');
            $password = env('TRENDAGENT_PASSWORD', 'nwBvh4q');
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF',
                'Content-Type' => 'application/json',
            ])->post("https://api.siteaccess.ru/trendagent/apartments/{$blockId}/flat/{$apartmentId}", [
                'phone' => $phone,
                'password' => $password,
            ]);
            
            if (!$response->successful()) {
                $this->error("HTTP error: {$response->status()}");
                return null;
            }
            
            $data = $response->json();
            return $data['data'] ?? $data;
            
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
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($path, $json);
    }
    
    /**
     * Сохранить детальные данные
     */
    protected function saveDetailsData(string $type, string $filename, array $data): void
    {
        $path = "{$this->basePath}/details/{$type}/{$filename}";
        $directory = dirname($path);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($path, $json);
    }
    
    /**
     * Логировать ошибку
     */
    protected function logError(string $type, string $objectId, string $message): void
    {
        $errorsPath = "{$this->basePath}/metadata/errors.json";
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
        
        file_put_contents($errorsPath, json_encode($errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Сохранить статистику
     */
    protected function saveStatistics(): void
    {
        $statisticsPath = "{$this->basePath}/metadata/statistics.json";
        $directory = dirname($statisticsPath);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $statistics = [
            'region' => $this->region,
            'parsed_at' => now()->toIso8601String(),
            'statistics' => $this->statistics,
        ];
        
        file_put_contents($statisticsPath, json_encode($statistics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Отобразить статистику
     */
    protected function displayStatistics(): void
    {
        $this->info("\n=== Statistics ===");
        foreach ($this->statistics as $type => $stats) {
            if (is_array($stats) && isset($stats['total'])) {
                $this->line("{$type}: {$stats['parsed']}/{$stats['total']} (errors: {$stats['errors']})");
            }
        }
    }
}
