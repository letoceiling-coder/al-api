<?php

namespace App\Console\Commands\TrendAgent;

use Illuminate\Console\Command;
use App\Services\TrendAgent\TrendAgentApiClient;
use App\Services\TrendAgent\ImageDownloader;
use Illuminate\Support\Facades\Storage;
use Exception;

class ParseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trendagent:parse
                            {--region=spb : Регион для парсинга (spb, msk, и т.д.)}
                            {--type=all : Тип объектов (all, apartments, parkings, houses, plots, commercial, complexes)}
                            {--limit=100 : Лимит объектов для парсинга}
                            {--offset=0 : Смещение для продолжения парсинга}
                            {--details : Парсить детальные страницы}
                            {--save-raw : Сохранять сырые данные}
                            {--download-images : Скачивать изображения локально (по умолчанию только URL)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Парсинг данных TrendAgent для указанного региона и типа объектов';

    private TrendAgentApiClient $apiClient;
    private ImageDownloader $imageDownloader;
    private string $region;
    private string $type;
    private int $limit;
    private int $offset;
    private bool $parseDetails;
    private bool $saveRaw;
    private bool $downloadImages;

    private array $statistics = [
        'started_at' => null,
        'finished_at' => null,
        'total_processed' => 0,
        'total_errors' => 0,
        'by_type' => [],
        'images' => [
            'total_urls' => 0,
            'downloaded' => 0,
            'skipped' => 0,
            'errors' => 0,
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->statistics['started_at'] = now()->toIso8601String();
        
        $this->apiClient = new TrendAgentApiClient();
        $this->region = $this->option('region');
        $this->type = $this->option('type');
        $this->limit = (int) $this->option('limit');
        $this->offset = (int) $this->option('offset');
        $this->parseDetails = $this->option('details');
        $this->saveRaw = $this->option('save-raw');
        $this->downloadImages = $this->option('download-images');
        
        // Инициализируем ImageDownloader с нужным режимом
        $this->imageDownloader = new ImageDownloader($this->downloadImages);

        $this->info("🚀 Начинаю парсинг TrendAgent");
        $this->info("📍 Регион: {$this->region}");
        $this->info("📦 Тип: {$this->type}");
        $this->info("📊 Лимит: {$this->limit}, Offset: {$this->offset}");
        $this->info("📷 Изображения: " . ($this->downloadImages ? "Скачивать локально" : "Только URL"));
        $this->newLine();

        try {
            // Проверяем аутентификацию
            $this->info("🔐 Проверка аутентификации...");
            $authResult = $this->apiClient->authenticate($this->region);
            $this->info("✅ Аутентификация успешна");
            $this->newLine();

            // Парсим данные по типам
            if ($this->type === 'all') {
                $types = ['complexes', 'apartments', 'parkings', 'houses', 'plots', 'commercial'];
                foreach ($types as $type) {
                    $this->parseType($type);
                    $this->newLine();
                }
            } else {
                $this->parseType($this->type);
            }

            // Сохраняем статистику
            $this->statistics['finished_at'] = now()->toIso8601String();
            $this->saveStatistics();

            // Выводим итоги
            $this->newLine();
            $this->info("✅ Парсинг завершён успешно!");
            $this->table(
                ['Метрика', 'Значение'],
                [
                    ['Обработано объектов', $this->statistics['total_processed']],
                    ['Ошибок', $this->statistics['total_errors']],
                    ['Время начала', $this->statistics['started_at']],
                    ['Время окончания', $this->statistics['finished_at']],
                ]
            );

            if (!empty($this->statistics['by_type'])) {
                $this->newLine();
                $this->info("📊 Статистика по типам:");
                $rows = [];
                foreach ($this->statistics['by_type'] as $type => $count) {
                    $rows[] = [$type, $count];
                }
                $this->table(['Тип', 'Количество'], $rows);
            }

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error("❌ Ошибка при парсинге: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            
            $this->saveError($e);
            
            return Command::FAILURE;
        }
    }

    /**
     * Парсинг объектов указанного типа
     */
    private function parseType(string $type): void
    {
        $this->info("📦 Парсинг типа: {$type}");
        
        $bar = $this->output->createProgressBar($this->limit);
        $bar->start();

        $processed = 0;
        $errors = 0;
        $currentOffset = $this->offset;
        $pageSize = 100; // API возвращает максимум ~40-100 объектов

        try {
            // Цикл пагинации
            while ($processed < $this->limit) {
                $result = null;
                switch ($type) {
                    case 'complexes':
                        $result = $this->parseComplexes($currentOffset, $bar);
                        break;
                    case 'apartments':
                        $result = $this->parseApartments($currentOffset, $bar);
                        break;
                    case 'parkings':
                        $result = $this->parseParkings($currentOffset, $bar);
                        break;
                    case 'houses':
                        $result = $this->parseHouses($currentOffset, $bar);
                        break;
                    case 'plots':
                        $result = $this->parsePlots($currentOffset, $bar);
                        break;
                    case 'commercial':
                        $result = $this->parseCommercial($currentOffset, $bar);
                        break;
                    default:
                        $this->warn("⚠️  Неизвестный тип: {$type}");
                        return;
                }

                $pageProcessed = $result['processed'];
                $processed += $pageProcessed;
                $errors += $result['errors'];
                
                // Если получили 0 объектов - значит это конец
                if ($pageProcessed === 0) {
                    break;
                }
                
                // Увеличиваем offset для следующей страницы
                $currentOffset += $pageProcessed;
                
                // Добавляем небольшую задержку между запросами
                usleep(100000); // 0.1 секунды
            }

        } catch (Exception $e) {
            $this->error("\n❌ Ошибка при парсинге {$type}: " . $e->getMessage());
            $errors++;
        }

        $bar->finish();
        $this->newLine();

        $this->statistics['total_processed'] += $processed;
        $this->statistics['total_errors'] += $errors;
        $this->statistics['by_type'][$type] = $processed;

        $this->info("✅ {$type}: обработано {$processed}, ошибок {$errors}");
    }

    /**
     * Парсинг комплексов
     */
    private function parseComplexes(int $offset, $bar): array
    {
        $data = $this->apiClient->getObjectsList($this->region, null, 100, $offset);

        if ($this->saveRaw) {
            $this->saveRawData('complexes', 'list', $offset, $data);
        }

        // API возвращает структуру: ['success' => true, 'data' => [...], 'total' => N]
        $items = $data['data'] ?? [];
        $processed = 0;

        foreach ($items as $item) {
            $bar->advance();
            $processed++;

            $blockId = $item['_id'] ?? $item['id'] ?? null;
            
            if ($this->parseDetails && $blockId) {
                try {
                    // Получаем детали комплекса
                    $details = $this->apiClient->getApartmentDetails($blockId);
                    $this->saveDetailedData('complexes', $blockId, $details);
                    
                    // Получаем все квартиры комплекса через шахматку
                    $this->parseComplexApartments($blockId);
                } catch (Exception $e) {
                    $this->warn("\n⚠️  Ошибка при загрузке деталей комплекса {$blockId}: " . $e->getMessage());
                }
            }
        }

        return ['processed' => $processed, 'errors' => 0];
    }
    
    /**
     * Парсинг квартир внутри комплекса
     */
    private function parseComplexApartments(string $blockId): void
    {
        try {
            // Получаем квартиры комплекса через API метод getBlockApartments
            // Это точно такой же список как на /trendagent/apartments/{id}
            $apartmentsResponse = $this->apiClient->getBlockApartments($blockId);
            
            if ($this->saveRaw) {
                $this->saveRawData('complexes', 'apartments', $blockId, $apartmentsResponse);
            }
            
            $apartments = $apartmentsResponse['data'] ?? [];
            $apartmentsCount = count($apartments);
            
            $this->info("\n   └── Комплекс {$blockId}: {$apartmentsCount} квартир");
            
        } catch (Exception $e) {
            $this->warn("\n   └── ⚠️  Ошибка парсинга квартир комплекса {$blockId}: " . $e->getMessage());
        }
    }

    /**
     * Парсинг квартир
     */
    private function parseApartments(int $offset, $bar): array
    {
        $params = [
            'city' => $this->region,
            'count' => 100,
            'offset' => $offset,
        ];
        
        $data = $this->apiClient->getApartments($params);
        
        if ($this->saveRaw) {
            $this->saveRawData('apartments', 'list', $offset, $data);
        }

        // API возвращает структуру: ['success' => true, 'data' => [...], 'total' => N]
        $items = $data['data'] ?? [];
        $processed = count($items);
        
        $bar->advance($processed);

        return ['processed' => $processed, 'errors' => 0];
    }

    /**
     * Парсинг паркингов
     */
    private function parseParkings(int $offset, $bar): array
    {
        $params = [
            'city' => $this->region,
            'count' => 100,
            'offset' => $offset,
        ];
        
        $data = $this->apiClient->getParkings($params);
        
        if ($this->saveRaw) {
            $this->saveRawData('parkings', 'list', $offset, $data);
        }

        // API возвращает структуру: ['success' => true, 'data' => [...], 'total' => N]
        $items = $data['data'] ?? [];
        $processed = count($items);
        
        $bar->advance($processed);

        return ['processed' => $processed, 'errors' => 0];
    }

    /**
     * Парсинг домов
     */
    private function parseHouses(int $offset, $bar): array
    {
        $params = [
            'city' => $this->region,
            'count' => 100,
            'offset' => $offset,
        ];
        
        $data = $this->apiClient->getHouses($params);
        
        if ($this->saveRaw) {
            $this->saveRawData('houses', 'list', $offset, $data);
        }

        // API возвращает структуру: ['success' => true, 'data' => [...], 'total' => N]
        $items = $data['data'] ?? [];
        $processed = count($items);
        
        $bar->advance($processed);

        return ['processed' => $processed, 'errors' => 0];
    }

    /**
     * Парсинг участков
     */
    private function parsePlots(int $offset, $bar): array
    {
        $params = [
            'city' => $this->region,
            'count' => 100,
            'offset' => $offset,
        ];
        
        $data = $this->apiClient->getPlots($params);
        
        if ($this->saveRaw) {
            $this->saveRawData('plots', 'list', $offset, $data);
        }

        // API возвращает структуру: ['success' => true, 'data' => [...], 'total' => N]
        $items = $data['data'] ?? [];
        $processed = count($items);
        
        $bar->advance($processed);

        return ['processed' => $processed, 'errors' => 0];
    }

    /**
     * Парсинг коммерции
     */
    private function parseCommercial(int $offset, $bar): array
    {
        $params = [
            'city' => $this->region,
            'count' => 100,
            'offset' => $offset,
        ];
        
        $data = $this->apiClient->getCommercial($params);
        
        if ($this->saveRaw) {
            $this->saveRawData('commercial', 'list', $offset, $data);
        }

        // API возвращает структуру: ['success' => true, 'data' => [...], 'total' => N]
        $items = $data['data'] ?? [];
        $processed = count($items);
        
        $bar->advance($processed);

        return ['processed' => $processed, 'errors' => 0];
    }

    /**
     * Сохранить сырые данные списка
     */
    private function saveRawData(string $type, string $dataType, $identifier, array $data): void
    {
        // Если identifier это число - это offset, иначе - это ID объекта
        $filename = is_numeric($identifier)
            ? "trendagent/parsing/{$this->region}/raw/{$type}/{$dataType}_offset_{$identifier}.json"
            : "trendagent/parsing/{$this->region}/raw/{$type}/{$dataType}_{$identifier}.json";
        
        $content = [
            'metadata' => [
                'region' => $this->region,
                'type' => $type,
                'data_type' => $dataType,
                'timestamp' => now()->toIso8601String(),
                'identifier' => $identifier,
                'limit' => $this->limit,
                'items_count' => count($data['data'] ?? $data),
            ],
            'data' => $data,
        ];

        Storage::put($filename, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Сохранить детальные данные объекта
     */
    private function saveDetailedData(string $type, string $id, array $data): void
    {
        $filename = "trendagent/parsing/{$this->region}/details/{$type}/{$id}.json";
        
        $content = [
            'metadata' => [
                'region' => $this->region,
                'type' => $type,
                'id' => $id,
                'timestamp' => now()->toIso8601String(),
            ],
            'data' => $data,
        ];

        Storage::put($filename, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Сохранить статистику парсинга
     */
    private function saveStatistics(): void
    {
        // Добавляем статистику по изображениям
        $this->statistics['images'] = $this->imageDownloader->getStats();
        
        $filename = "trendagent/parsing/{$this->region}/metadata/statistics.json";
        Storage::put($filename, json_encode($this->statistics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Сохранить информацию об ошибке
     */
    private function saveError(Exception $e): void
    {
        $filename = "trendagent/parsing/{$this->region}/metadata/errors.json";
        
        $errors = [];
        if (Storage::exists($filename)) {
            $errors = json_decode(Storage::get($filename), true) ?? [];
        }

        $errors[] = [
            'timestamp' => now()->toIso8601String(),
            'type' => $this->type,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];

        Storage::put($filename, json_encode($errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
