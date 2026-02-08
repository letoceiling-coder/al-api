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
                            {--type=all : Тип объектов (all, apartments, parkings, houses, plots, commercial, complexes, contractors)}
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
        'by_type_total' => [], // Сохраняем total из API для каждого типа
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
                $types = ['complexes', 'apartments', 'parkings', 'houses', 'plots', 'commercial', 'contractors'];
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

            // Выводим точные данные из API
            $this->newLine();
            $this->info("📊 Точные данные из API:");
            $this->displayExactData();

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
        
        // Для паркингов используем больший лимит, т.к. они считаются по машиноместам
        $displayLimit = $this->limit;
        if ($type === 'parkings') {
            // Для паркингов лимит применяется к комплексам, а не к машиноместам
            $displayLimit = $this->limit;
        }
        
        $bar = $this->output->createProgressBar($displayLimit);
        $bar->start();

        $processed = 0;
        $errors = 0;
        $currentOffset = $this->offset;
        $pageSize = 100; // API возвращает максимум ~40-100 объектов
        $totalFromApi = null; // Общее количество из API

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
                    case 'contractors':
                        $result = $this->parseContractors($currentOffset, $bar);
                        break;
                    default:
                        $this->warn("⚠️  Неизвестный тип: {$type}");
                        return;
                }

                $pageProcessed = $result['processed'];
                $pageTotal = $result['total'] ?? null;
                $processed += $pageProcessed;
                $errors += $result['errors'];
                
                // Сохраняем total из API (если еще не сохранен)
                if ($totalFromApi === null && $pageTotal !== null) {
                    $totalFromApi = $pageTotal;
                    // Сохраняем total в статистику
                    $this->statistics['by_type_total'][$type] = $totalFromApi;
                    // Обновляем прогресс-бар с реальным total, если он больше лимита
                    if ($totalFromApi > $this->limit) {
                        $bar->setMaxSteps(min($this->limit, $totalFromApi));
                    }
                }
                
                // Если получили 0 объектов - значит это конец
                if ($pageProcessed === 0) {
                    break;
                }
                
                // Для паркингов offset увеличиваем по комплексам, а не по машиноместам
                if ($type === 'parkings') {
                    // Для паркингов result['processed'] - это количество машиномест
                    // Но offset должен увеличиваться по количеству комплексов
                    $complexesCount = $result['complexes_processed'] ?? $pageProcessed;
                    $currentOffset += $complexesCount;
                } else {
                    // Для остальных типов offset увеличиваем по количеству обработанных объектов
                    $currentOffset += $pageProcessed;
                }
                
                // Проверяем, достигли ли мы конца (если offset >= total)
                if ($totalFromApi !== null && $currentOffset >= $totalFromApi) {
                    break;
                }
                
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

        $totalInfo = $totalFromApi !== null ? " (всего в API: {$totalFromApi})" : "";
        $this->info("✅ {$type}: обработано {$processed}{$totalInfo}, ошибок {$errors}");
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
        $total = $data['total'] ?? null;
        $processed = 0;

        foreach ($items as $item) {
            $bar->advance();
            $processed++;

            $blockId = $item['_id'] ?? $item['id'] ?? null;
            
            if ($this->parseDetails && $blockId) {
                try {
                    // Получаем детали комплекса через unified эндпоинт
                    $details = $this->apiClient->getApartmentDetails($blockId);
                    $this->saveDetailedData('complexes', $blockId, $details);
                    
                    // Получаем все квартиры комплекса через шахматку (checkerboard)
                    $this->parseComplexApartments($blockId);
                    
                    // Получаем планировки (checkerboard) для комплекса
                    $this->parseComplexCheckerboard($blockId);
                } catch (Exception $e) {
                    $this->warn("\n⚠️  Ошибка при загрузке деталей комплекса {$blockId}: " . $e->getMessage());
                }
            }
        }

        return ['processed' => $processed, 'errors' => 0, 'total' => $total];
    }
    
    /**
     * Парсинг квартир внутри комплекса
     */
    private function parseComplexApartments(string $blockId): void
    {
        try {
            // Получаем квартиры комплекса через API метод getBlockApartments
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
     * Парсинг планировок (checkerboard) для комплекса
     * Согласно BROWSER_ANALYSIS_RESULTS.md: используем checkerboards/{blockId}/apartments/
     */
    private function parseComplexCheckerboard(string $blockId): void
    {
        try {
            // Получаем корпуса для шахматки
            $buildingsResponse = $this->apiClient->getApartmentCheckerboardBuildings($blockId);
            
            if ($this->saveRaw) {
                $this->saveRawData('complexes', 'checkerboard_buildings', $blockId, $buildingsResponse);
            }
            
            $buildings = $buildingsResponse['data'] ?? [];
            $buildingsCount = count($buildings);
            
            $this->info("\n   └── Комплекс {$blockId}: {$buildingsCount} корпусов для шахматки");
            
            // Получаем все квартиры через checkerboard (метод сам обрабатывает все корпуса)
            $apartmentsResponse = $this->apiClient->getApartmentCheckerboardApartments($blockId);
            
            if ($this->saveRaw) {
                $this->saveRawData('complexes', 'checkerboard_apartments', $blockId, $apartmentsResponse);
            }
            
            $apartments = $apartmentsResponse['data'] ?? [];
            $apartmentsCount = count($apartments);
            
            $this->info("\n   └── Комплекс {$blockId}: {$apartmentsCount} квартир в шахматке");
            
        } catch (Exception $e) {
            $this->warn("\n   └── ⚠️  Ошибка парсинга шахматки комплекса {$blockId}: " . $e->getMessage());
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
        $total = $data['total'] ?? null;
        $processed = 0;
        $errors = 0;
        
        // Если включен парсинг деталей, получаем детальную информацию для каждой квартиры
        foreach ($items as $item) {
            $apartmentId = $item['_id'] ?? $item['id'] ?? null;
            $blockId = $item['block_id'] ?? $item['block']['_id'] ?? null;
            
            if ($this->parseDetails && $apartmentId) {
                try {
                    // Используем новый метод с unified эндпоинтом
                    $details = $this->apiClient->getApartmentFlatDetails($blockId ?? '', $apartmentId);
                    $this->saveDetailedData('apartments', $apartmentId, $details);
                } catch (Exception $e) {
                    $this->warn("\n⚠️  Ошибка при загрузке деталей квартиры {$apartmentId}: " . $e->getMessage());
                    $errors++;
                }
            }
            
            $processed++;
            $bar->advance();
        }

        return ['processed' => $processed, 'errors' => $errors, 'total' => $total];
    }

    /**
     * Парсинг паркингов (двухуровневый: комплексы → машиноместа)
     */
    private function parseParkings(int $offset, $bar): array
    {
        // Шаг 1: Получить комплексы с паркингами через getParkings
        $params = [
            'city' => $this->region,
            'count' => 100,
            'offset' => $offset,
        ];
        
        $data = $this->apiClient->getParkings($params);
        
        if ($this->saveRaw) {
            $this->saveRawData('parkings', 'complexes_list', $offset, $data);
        }

        // API возвращает комплексы с паркингами
        $complexes = $data['data'] ?? [];
        $total = $data['total'] ?? null;
        $totalParkings = 0;
        $errors = 0;
        $complexesProcessed = 0;
        
        $this->info("\n   Найдено комплексов с паркингами: " . count($complexes));
        
        // Шаг 2: Для каждого комплекса получить машиноместа
        foreach ($complexes as $complex) {
            $complexesProcessed++;
            $blockId = $complex['_id'] ?? $complex['id'] ?? null;
            
            if (!$blockId) {
                continue;
            }
            
            try {
                // Получаем машиноместа комплекса
                $parkingsData = $this->apiClient->getBlockParkings($blockId);
                
                if ($this->saveRaw) {
                    $this->saveParkingsData($blockId, $parkingsData);
                }
                
                $parkings = $parkingsData['data'] ?? [];
                $parkingsCount = is_array($parkings) ? count($parkings) : 0;
                $totalParkings += $parkingsCount;
                
                if ($parkingsCount > 0) {
                    $this->info("\n   └── Комплекс {$blockId}: {$parkingsCount} машиномест");
                    
                    // Если включен парсинг деталей, получаем детальную информацию для каждого паркинга
                    if ($this->parseDetails) {
                        foreach ($parkings as $parking) {
                            $parkingId = $parking['_id'] ?? $parking['id'] ?? null;
                            if ($parkingId) {
                                try {
                                    $details = $this->apiClient->getParkingDetails($parkingId);
                                    $this->saveDetailedData('parkings', $parkingId, $details);
                                } catch (Exception $e) {
                                    // Игнорируем ошибки детальной информации для паркингов
                                }
                            }
                        }
                    }
                }
                
                $bar->advance();
                
            } catch (Exception $e) {
                $this->warn("\n⚠️  Ошибка при получении паркингов комплекса {$blockId}: " . $e->getMessage());
                $errors++;
            }
        }

        return [
            'processed' => $totalParkings, 
            'errors' => $errors, 
            'total' => $total,
            'complexes_processed' => $complexesProcessed,
        ];
    }

    /**
     * Парсинг домов (прямой парсинг - API возвращает дома напрямую)
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
        $total = $data['total'] ?? null;
        $processed = 0;
        $errors = 0;
        
        // Если включен парсинг деталей, получаем детальную информацию для каждого дома
        foreach ($items as $item) {
            $houseId = $item['_id'] ?? $item['id'] ?? null;
            $blockId = $item['block_id'] ?? $item['block']['_id'] ?? null;
            
            if ($this->parseDetails && $houseId) {
                try {
                    // Дома используют тот же unified эндпоинт, что и квартиры
                    $details = $this->apiClient->getApartmentFlatDetails($blockId ?? '', $houseId);
                    $this->saveDetailedData('houses', $houseId, $details);
                } catch (Exception $e) {
                    $this->warn("\n⚠️  Ошибка при загрузке деталей дома {$houseId}: " . $e->getMessage());
                    $errors++;
                }
            }
            
            $processed++;
            $bar->advance();
        }

        return ['processed' => $processed, 'errors' => $errors, 'total' => $total];
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
        $total = $data['total'] ?? null;
        $processed = 0;
        $errors = 0;
        
        // Если включен парсинг деталей, получаем детальную информацию для каждого поселка
        foreach ($items as $item) {
            $plotId = $item['_id'] ?? $item['id'] ?? $item['village_id'] ?? null;
            $slug = $item['guid'] ?? $item['slug'] ?? null;
            
            if ($this->parseDetails && ($plotId || $slug)) {
                try {
                    // Используем slug или ID для получения детальной информации через unified
                    $details = $this->apiClient->getPlotDetails($slug ?? $plotId);
                    $this->saveDetailedData('plots', $plotId ?? $slug, $details);
                } catch (Exception $e) {
                    $this->warn("\n⚠️  Ошибка при загрузке деталей поселка {$plotId}: " . $e->getMessage());
                    $errors++;
                }
            }
            
            $processed++;
            $bar->advance();
        }

        return ['processed' => $processed, 'errors' => $errors, 'total' => $total];
    }

    /**
     * Парсинг коммерции (прямой парсинг - API возвращает помещения напрямую)
     */
    private function parseCommercial(int $offset, $bar): array
    {
        // Парсим помещения коммерции напрямую через commerce-api
        $params = [
            'city' => $this->region,
            'count' => 100,
            'offset' => $offset,
        ];
        
        $data = $this->apiClient->getCommercePremises($params);
        
        if ($this->saveRaw) {
            $this->saveRawData('commercial', 'premises_list', $offset, $data);
        }

        // API возвращает структуру: ['success' => true, 'data' => [...], 'total' => N]
        $items = $data['data'] ?? [];
        $total = $data['total'] ?? null;
        $processed = 0;
        $errors = 0;
        
        // Если включен парсинг деталей, получаем детальную информацию для каждого помещения
        foreach ($items as $item) {
            $premiseId = $item['_id'] ?? $item['id'] ?? null;
            
            if ($this->parseDetails && $premiseId) {
                try {
                    // Используем новый метод с unified эндпоинтом для коммерции
                    $details = $this->apiClient->getCommercialDetails($premiseId);
                    $this->saveDetailedData('commercial', $premiseId, $details);
                } catch (Exception $e) {
                    $this->warn("\n⚠️  Ошибка при загрузке деталей помещения {$premiseId}: " . $e->getMessage());
                    $errors++;
                }
            }
            
            $processed++;
            $bar->advance();
        }

        return ['processed' => $processed, 'errors' => $errors, 'total' => $total];
    }

    /**
     * Парсинг подрядчиков (проектов домов)
     */
    private function parseContractors(int $offset, $bar): array
    {
        $params = [
            'city' => $this->region,
            'count' => 100,
            'offset' => $offset,
        ];
        
        $data = $this->apiClient->getContractors($params);
        
        if ($this->saveRaw) {
            $this->saveRawData('contractors', 'list', $offset, $data);
        }

        // API возвращает структуру: ['success' => true, 'data' => [...], 'total' => N]
        $items = $data['data'] ?? [];
        $total = $data['total'] ?? null;
        $processed = 0;
        $errors = 0;
        
        // Если включен парсинг деталей, получаем детальную информацию для каждого проекта
        foreach ($items as $item) {
            $projectId = $item['_id'] ?? $item['id'] ?? null;
            $slug = $item['guid'] ?? $item['slug'] ?? null;
            
            if ($this->parseDetails && ($projectId || $slug)) {
                try {
                    // Используем slug или ID для получения детальной информации через unified
                    $details = $this->apiClient->getContractorProjectDetails($slug ?? $projectId);
                    $this->saveDetailedData('contractors', $projectId ?? $slug, $details);
                } catch (Exception $e) {
                    $this->warn("\n⚠️  Ошибка при загрузке деталей проекта {$projectId}: " . $e->getMessage());
                    $errors++;
                }
            }
            
            $processed++;
            $bar->advance();
        }

        return ['processed' => $processed, 'errors' => $errors, 'total' => $total];
    }

    /**
     * Вывод точных данных из API
     */
    private function displayExactData(): void
    {
        $typeNames = [
            'complexes' => 'Комплексы (ЖК)',
            'apartments' => 'Квартиры',
            'parkings' => 'Паркинги (машиноместа)',
            'houses' => 'Дома',
            'plots' => 'Участки',
            'commercial' => 'Коммерция (помещения)',
            'contractors' => 'Подрядчики (проекты домов)',
        ];

        $rows = [];
        $hasData = false;

        foreach ($typeNames as $type => $name) {
            $processed = $this->statistics['by_type'][$type] ?? 0;
            $total = $this->statistics['by_type_total'][$type] ?? null;
            
            // Выводим данные, если они были обработаны или есть total из API
            if ($processed > 0 || $total !== null) {
                $hasData = true;
                if ($total !== null) {
                    $rows[] = [$name, number_format($total, 0, ',', ' ')];
                } else {
                    $rows[] = [$name, number_format($processed, 0, ',', ' ') . " (total из API недоступен)"];
                }
            } else {
                // Выводим 0, если тип был в списке для парсинга, но данных нет
                if (in_array($type, ['complexes', 'apartments', 'parkings', 'houses', 'plots', 'commercial', 'contractors'])) {
                    $hasData = true;
                    $rows[] = [$name, "0 (не обработано)"];
                }
            }
        }

        // Если есть данные, выводим таблицу
        if ($hasData) {
            $this->table(['Тип объекта', 'Всего в API'], $rows);
        } else {
            $this->warn("⚠️  Данные из API недоступны");
        }

        // Дополнительно выводим информацию о поселках (если есть)
        // Поселки получаются через plots, но это отдельные объекты
        $plotsTotal = $this->statistics['by_type_total']['plots'] ?? null;
        if ($plotsTotal !== null) {
            $this->newLine();
            $this->info("ℹ️  Примечание: Поселки получаются через API участков. Количество поселков может отличаться от количества участков.");
        }
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
     * Сохранить машиноместа комплекса
     */
    private function saveParkingsData(string $blockId, array $parkingsData): void
    {
        $filename = "trendagent/parsing/{$this->region}/raw/parkings/items/block_{$blockId}.json";
        
        $content = [
            'metadata' => [
                'region' => $this->region,
                'type' => 'parkings',
                'data_type' => 'block_items',
                'block_id' => $blockId,
                'timestamp' => now()->toIso8601String(),
                'items_count' => is_array($parkingsData['data'] ?? null) ? count($parkingsData['data']) : 0,
            ],
            'data' => $parkingsData,
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
