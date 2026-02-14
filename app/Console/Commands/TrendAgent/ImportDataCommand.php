<?php

namespace App\Console\Commands\TrendAgent;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\TrendAgent\Region;
use App\Models\TrendAgent\Complex;
use App\Models\TrendAgent\Building;
use App\Models\TrendAgent\Apartment;
use App\Models\TrendAgent\Parking;
use App\Models\TrendAgent\ParkingPlace;
use App\Models\TrendAgent\House;
use App\Models\TrendAgent\PlotSettlement;
use App\Models\TrendAgent\Plot;
use App\Models\TrendAgent\Commercial;
use App\Models\TrendAgent\Contractor;
use App\Models\TrendAgent\ContractorProject;
use App\Models\TrendAgent\NearbyPlace;
use App\Models\TrendAgent\FloorPlan;
use App\Models\TrendAgent\TrendAgentImage;
use App\Models\TrendAgent\FinishingType;
use App\Models\TrendAgent\Status;
use App\Models\TrendAgent\SyncRun;
use App\Services\TrendAgent\TrendAgentImageStoreService;
use App\Services\TrendAgent\ImportNormalizers;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trendagent:import-data
                            {--region=spb : Регион для импорта (spb, msk, и т.д.)}
                            {--type=all : Тип объектов (all, apartments, parkings, houses, plots, commercial, complexes, contractors)}
                            {--dry-run : Пробный запуск без сохранения в БД и без скачивания файлов}
                            {--batch=500 : Размер пачки для обработки}
                            {--fail-fast=0 : 1=остановиться при первой ошибке пачки}
                            {--download-images=0 : 1=скачивать изображения локально}
                            {--images-disk= : Диск для изображений (default: public)}
                            {--images-dir= : Подпапка для изображений (default: trendagent)}
                            {--timeout=30 : Таймаут HTTP для скачивания (сек)}
                            {--max-image-size-mb=25 : Макс. размер изображения (MB)}
                            {--retries=2 : Кол-во повторных попыток скачивания}
                            {--deactivate-missing=0 : 1=деактивировать записи, отсутствующие в источнике}
                            {--missing-days=7 : Дней без last_seen для деактивации}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импорт данных из парсинга в базу данных';

    private string $region;
    private array $basePaths;
    private bool $dryRun;
    private bool $downloadImages;
    private int $batchSize;
    private bool $failFast;
    private bool $deactivateMissing;
    private int $missingDays;
    private TrendAgentImageStoreService $imageStore;
    private array $statistics = [
        'imported' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];
    private array $statsByType = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->region = $this->option('region');
        // Проверяем оба возможных пути
        $this->basePaths = [
            storage_path("trendagent/parsing/{$this->region}"),
            storage_path("app/private/trendagent/parsing/{$this->region}"),
        ];
        $this->dryRun = $this->option('dry-run');

        $this->downloadImages = (bool) (int) $this->option('download-images');
        $this->batchSize = max(1, (int) $this->option('batch'));
        $this->failFast = (bool) (int) $this->option('fail-fast');
        $this->deactivateMissing = (bool) (int) $this->option('deactivate-missing');
        $this->missingDays = max(1, (int) $this->option('missing-days'));

        $timeout = (int) $this->option('timeout');
        $maxMb = (int) $this->option('max-image-size-mb');
        $retries = (int) $this->option('retries');
        $this->imageStore = new TrendAgentImageStoreService(
            $this->option('images-disk') ?: null,
            $this->option('images-dir') ?: null,
            $timeout > 0 ? $timeout : 30,
            $maxMb > 0 ? $maxMb : 25,
            $retries >= 0 ? $retries : 2
        );

        $this->info("📥 Начинаю импорт данных TrendAgent");
        $this->info("📍 Регион: {$this->region}");
        if ($this->dryRun) {
            $this->warn("⚠️  Режим пробного запуска (dry-run) - данные не будут сохранены");
        }
        if ($this->downloadImages) {
            $this->info("🖼️  Режим скачивания изображений: включен");
        }
        $this->newLine();

        // Проверяем наличие хотя бы одного пути
        $foundPath = null;
        foreach ($this->basePaths as $path) {
            if (File::exists($path)) {
                $foundPath = $path;
                break;
            }
        }

        if (!$foundPath) {
            $this->error("❌ Директория с данными не найдена. Проверены пути:");
            foreach ($this->basePaths as $path) {
                $this->line("   - {$path}");
            }
            $this->warn("💡 Сначала запустите парсинг: php artisan trendagent:parse --region={$this->region} --type=all --details --save-raw");
            return 1;
        }

        $region = $this->getOrCreateRegion();

        $type = $this->option('type');
        $allTypes = [
            'complexes', 'apartments', 'parkings', 'parking_places',
            'houses', 'plots', 'commercial', 'contractors',
        ];
        $types = $type === 'all'
            ? $allTypes
            : [$type];

        foreach ($types as $objectType) {
            $this->statsByType[$objectType] = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];
            $this->info("📦 Импорт типа: {$objectType}");
            $syncRun = null;
            $startTime = microtime(true);
            try {
                if (!$this->dryRun && class_exists(SyncRun::class)) {
                    $syncRun = SyncRun::create([
                        'started_at' => now(),
                        'region' => $this->region,
                        'type' => $objectType,
                        'flags' => [
                            'download_images' => $this->downloadImages,
                            'deactivate_missing' => $this->deactivateMissing,
                            'batch' => $this->batchSize,
                        ],
                        'status' => 'running',
                    ]);
                }
                $this->importType($objectType, $region);
                if ($syncRun) {
                    $stats = $this->statsByType[$objectType] ?? [];
                    $syncRun->update([
                        'finished_at' => now(),
                        'created_count' => $stats['created'] ?? 0,
                        'updated_count' => $stats['updated'] ?? 0,
                        'skipped_count' => $stats['skipped'] ?? 0,
                        'error_count' => $stats['errors'] ?? 0,
                        'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                        'status' => 'success',
                    ]);
                }
            } catch (Exception $e) {
                if ($syncRun) {
                    $syncRun->update([
                        'finished_at' => now(),
                        'created_count' => $this->statsByType[$objectType]['created'] ?? 0,
                        'updated_count' => $this->statsByType[$objectType]['updated'] ?? 0,
                        'skipped_count' => $this->statsByType[$objectType]['skipped'] ?? 0,
                        'error_count' => ($this->statsByType[$objectType]['errors'] ?? 0) + 1,
                        'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                        'status' => 'failed',
                        'error_summary' => mb_substr($e->getMessage(), 0, 1000),
                    ]);
                }
                Log::error('TrendAgent import failed', ['type' => $objectType, 'message' => $e->getMessage()]);
                throw $e;
            }
            $this->newLine();
        }

        $this->displayStatistics();

        return 0;
    }

    /**
     * Получить или создать регион
     */
    private function getOrCreateRegion(): Region
    {
        $region = Region::where('code', $this->region)->first();
        
        if (!$region) {
            if (!$this->dryRun) {
                $region = Region::create([
                    'code' => $this->region,
                    'name' => $this->getRegionName($this->region),
                ]);
                $this->info("✅ Создан регион: {$region->name}");
            } else {
                $this->info("🔍 [DRY-RUN] Будет создан регион: {$this->getRegionName($this->region)}");
                $region = new Region(['code' => $this->region, 'name' => $this->getRegionName($this->region)]);
            }
        }

        return $region;
    }

    /**
     * Получить название региона
     */
    private function getRegionName(string $code): string
    {
        return match($code) {
            'spb' => 'Санкт-Петербург',
            'msk' => 'Москва',
            default => ucfirst($code),
        };
    }

    /**
     * Импорт данных конкретного типа
     */
    private function importType(string $type, Region $region): void
    {
        if ($type === 'parking_places') {
            $this->importParkingPlacesFromRaw($region);
            return;
        }

        $files = [];
        foreach ($this->basePaths as $basePath) {
            $detailsPath = "{$basePath}/details/{$type}";
            if (File::exists($detailsPath)) {
                $foundFiles = File::glob("{$detailsPath}/*.json");
                $files = array_merge($files, $foundFiles);
            }
        }

        $total = count(array_unique($files));

        if ($total === 0) {
            $this->warn("   ⚠️  Файлы не найдены. Проверены пути:");
            foreach ($this->basePaths as $basePath) {
                $this->line("      - {$basePath}/details/{$type}");
            }
            return;
        }

        $this->info("   📁 Найдено файлов: {$total} (batch={$this->batchSize})");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $apartmentBlockMap = $type === 'apartments' ? $this->loadApartmentBlockMap() : [];
        $uniqueFiles = array_values(array_unique($files));
        $batches = array_chunk($uniqueFiles, $this->batchSize);

        foreach ($batches as $batchFiles) {
            $batchFailed = false;
            try {
                if (!$this->dryRun) {
                    DB::beginTransaction();
                }
                foreach ($batchFiles as $file) {
                    try {
                        $fileData = json_decode(File::get($file), true);

                        if (!$fileData) {
                            $this->addStats($type, 'errors');
                            $bar->advance();
                            continue;
                        }

                        $data = $fileData['data']['data'] ?? $fileData['data'] ?? $fileData;

                        if (empty($data)) {
                            $this->addStats($type, 'errors');
                            $bar->advance();
                            continue;
                        }

                        if ($type === 'apartments' && !empty($apartmentBlockMap) && !isset($data['block_id']) && isset($data['_id'])) {
                            $data['block_id'] = $apartmentBlockMap[$data['_id']] ?? $apartmentBlockMap[$data['id'] ?? ''] ?? null;
                        }

                        match ($type) {
                            'complexes' => $this->importComplex($data, $region),
                            'apartments' => $this->importApartment($data, $region),
                            'parkings' => $this->importParking($data, $region),
                            'houses' => $this->importHouse($data, $region),
                            'plots' => $this->importPlot($data, $region),
                            'commercial' => $this->importCommercial($data, $region),
                            'contractors' => $this->importContractor($data, $region),
                            default => null,
                        };

                        $bar->advance();
                    } catch (Exception $e) {
                        $this->addStats($type, 'errors');
                        Log::error("TrendAgent import error [{$type}] {$file}", ['message' => $e->getMessage()]);
                        $this->error("   ❌ Ошибка при импорте {$file}: {$e->getMessage()}");
                        $bar->advance();
                        if ($this->failFast && !$this->dryRun) {
                            $batchFailed = true;
                            break;
                        }
                    }
                }
                if (!$this->dryRun) {
                    if ($batchFailed) {
                        DB::rollBack();
                        $this->error("   ⛔ Fail-fast: остановка импорта");
                        break;
                    }
                    DB::commit();
                }
            } catch (Exception $e) {
                if (!$this->dryRun) {
                    DB::rollBack();
                }
                $this->addStats($type, 'errors');
                Log::error("TrendAgent import batch error [{$type}]", ['message' => $e->getMessage()]);
                $this->error("   ❌ Ошибка пачки: {$e->getMessage()}");
                if ($this->failFast) {
                    break;
                }
            }
        }

        if ($this->deactivateMissing && !$this->dryRun) {
            $this->deactivateMissingRecords($type, $region);
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Деактивировать записи, не встречавшиеся в источнике (last_seen_at < now - missing_days)
     */
    private function deactivateMissingRecords(string $type, Region $region): void
    {
        $cutoff = now()->subDays($this->missingDays);
        $updated = 0;

        $map = [
            'complexes' => [Complex::class, 'region_id'],
            'apartments' => [Apartment::class, 'region_id'],
            'parkings' => [Parking::class, 'region_id'],
            'houses' => [House::class, 'region_id'],
            'plots' => [Plot::class, 'region_id'],
            'plot_settlements' => [PlotSettlement::class, 'region_id'],
            'commercial' => [Commercial::class, 'region_id'],
            'contractors' => [Contractor::class, null],
            'contractor_projects' => [ContractorProject::class, null],
        ];

        $targetTypes = match ($type) {
            'contractors' => ['contractor_projects', 'contractors'],
            default => [$type],
        };

        foreach ($targetTypes as $t) {
            if (!isset($map[$t])) {
                continue;
            }
            [$modelClass, $regionCol] = $map[$t];
            $query = $modelClass::where('is_active', true)
                ->where(function ($q) use ($cutoff) {
                    $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<', $cutoff);
                });
            if ($regionCol !== null) {
                $query->where($regionCol, $region->id);
            }
            $updated += $query->update(['is_active' => false]);
        }

        if ($updated > 0) {
            $this->info("   🔒 Деактивировано записей [{$type}]: {$updated}");
        }
    }

    private function extractString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            return $value;
        }
        if (is_array($value) && isset($value['url'])) {
            return (string) $value['url'];
        }
        if (is_array($value) && isset($value['path'], $value['file_name'])) {
            return $this->imageStore->buildUrlFromPathAndFile($value['path'], $value['file_name']);
        }
        return null;
    }

    private function addStats(string $type, string $key): void
    {
        $this->statistics[$key === 'errors' ? 'errors' : ($key === 'created' ? 'imported' : ($key === 'updated' ? 'updated' : 'skipped'))]++;
        if (isset($this->statsByType[$type])) {
            $this->statsByType[$type][$key]++;
        }
    }

    /** Загрузить маппинг apartment_id -> block_id из raw списка */
    private function loadApartmentBlockMap(): array
    {
        $map = [];
        foreach ($this->basePaths as $basePath) {
            $rawPath = "{$basePath}/raw/apartments";
            if (!File::exists($rawPath)) {
                continue;
            }
            foreach (File::glob("{$rawPath}/*.json") as $file) {
                $content = json_decode(File::get($file), true);
                $items = $content['data'] ?? [];
                foreach ($items as $item) {
                    $id = $item['_id'] ?? $item['id'] ?? null;
                    $blockId = $item['block_id'] ?? null;
                    if ($id && $blockId) {
                        $map[$id] = $blockId;
                    }
                }
            }
        }
        return $map;
    }

    /** Импорт мест парковки из raw списка */
    private function importParkingPlacesFromRaw(Region $region): void
    {
        $items = [];
        foreach ($this->basePaths as $basePath) {
            $rawPath = "{$basePath}/raw/parkings";
            if (!File::exists($rawPath)) {
                continue;
            }
            foreach (File::glob("{$rawPath}/*.json") as $file) {
                $content = json_decode(File::get($file), true);
                $data = $content['data'] ?? [];
                $items = array_merge($items, is_array($data) ? $data : []);
            }
        }

        if (empty($items)) {
            $this->warn("   ⚠️  Файлы raw/parkings не найдены или пусты");
            return;
        }

        $this->info("   📁 Найдено записей: " . count($items));
        $bar = $this->output->createProgressBar(count($items));
        $bar->start();

        foreach ($items as $item) {
            try {
                $this->importParkingPlace($item, $region);
            } catch (Exception $e) {
                $this->addStats('parking_places', 'errors');
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Импорт комплекса (+ buildings, nearby_places, floor_plans, images)
     */
    private function importComplex(array $data, Region $region): void
    {
        $externalId = $data['_id'] ?? $data['id'] ?? null;
        if (!$externalId) {
            return;
        }

        $latitude = null;
        $longitude = null;
        if (isset($data['geometry']['coordinates']) && is_array($data['geometry']['coordinates'])) {
            $longitude = $data['geometry']['coordinates'][0] ?? null;
            $latitude = $data['geometry']['coordinates'][1] ?? null;
        }

        $now = now();
        $complexData = [
            'region_id' => $region->id,
            'external_id' => $externalId,
            'last_seen_at' => $now,
            'is_active' => true,
            'guid' => $data['guid'] ?? $data['slug'] ?? null,
            'name' => $data['name'] ?? '',
            'address' => $data['address'] ?? null,
            'description' => $data['description'] ?? null,
            'latitude' => $latitude ?? $data['latitude'] ?? $data['location']['latitude'] ?? null,
            'longitude' => $longitude ?? $data['longitude'] ?? $data['location']['longitude'] ?? null,
            'developer_name' => $data['developer']['name'] ?? $data['builder']['name'] ?? null,
            'class_type' => $data['class_type'] ?? null,
            'deadline' => is_array($data['deadline'] ?? null) ? json_encode($data['deadline']) : ($data['deadline'] ?? null),
            'status' => $data['status'] ?? (is_numeric($data['status'] ?? null) ? (string) $data['status'] : null),
            'min_price' => $data['min_price'] ?? null,
            'images' => $data['plan'] ?? $data['images'] ?? [],
            'advantages' => $data['advantage'] ?? $data['advantages'] ?? [],
            'nearby_places' => $data['nearby_places'] ?? [],
            'videos' => $data['videos'] ?? [],
            'files' => $data['files'] ?? [],
            'raw_data' => $data,
        ];

        if ($this->dryRun) {
            $this->addStats('complexes', 'created');
            return;
        }

        try {
            $exists = Complex::where('external_id', $externalId)->exists();
            $complex = Complex::updateOrCreate(['external_id' => $externalId], $complexData);

            if ($complex && $complex->id) {
                $this->addStats('complexes', $exists ? 'updated' : 'created');

                $this->importBuildingsFromComplex($complex, $data['buildings'] ?? []);
                $this->importNearbyPlacesFromComplex($complex, $data);
                $this->importFloorPlanFromComplex($complex, $data);
                $this->syncImagesForEntity($complex, 'complex', $data['renderer'] ?? $data['plan'] ?? $data['images'] ?? []);
            } else {
                $this->addStats('complexes', 'errors');
            }
        } catch (\Exception $e) {
            $this->addStats('complexes', 'errors');
            $this->error("   ❌ Ошибка импорта комплекса {$externalId}: {$e->getMessage()}");
        }
    }

    private function importBuildingsFromComplex(Complex $complex, array $buildings): void
    {
        foreach ($buildings as $b) {
            $extId = $b['_id'] ?? $b['id'] ?? null;
            if (!$extId) {
                continue;
            }
            if ($this->dryRun) {
                continue;
            }
            Building::updateOrCreate(
                ['complex_id' => $complex->id, 'external_id' => $extId],
                [
                    'name' => $b['name'] ?? $b['number'] ?? null,
                    'number' => $b['number'] ?? $b['name'] ?? null,
                    'apartments_count' => $b['apartment_count'] ?? null,
                    'raw_data' => $b,
                ]
            );
        }
    }

    private function importNearbyPlacesFromComplex(Complex $complex, array $data): void
    {
        $places = [];
        foreach ($data['subways'] ?? [] as $s) {
            $places[] = [
                'name' => $s['name'] ?? null,
                'type' => 'subway',
                'distance' => $s['distance_timing'] ?? $s['distance_time'] ?? null,
            ];
        }
        foreach ($data['point_distance'] ?? [] as $p) {
            $places[] = [
                'name' => $p['name'] ?? null,
                'type' => $p['type'] ?? 'place',
                'distance' => $p['distance'] ?? null,
            ];
        }
        foreach ($data['nearby_places'] ?? [] as $p) {
            if (is_array($p)) {
                $places[] = [
                    'name' => $p['name'] ?? $p['title'] ?? null,
                    'type' => $p['type'] ?? 'place',
                    'distance' => $p['distance'] ?? null,
                ];
            }
        }

        if ($this->dryRun) {
            return;
        }
        foreach ($places as $p) {
            if (empty($p['name'])) {
                continue;
            }
            NearbyPlace::firstOrCreate(
                [
                    'complex_id' => $complex->id,
                    'name' => $p['name'],
                    'type' => $p['type'] ?? 'place',
                ],
                array_merge($p, ['complex_id' => $complex->id])
            );
        }
    }

    private function importFloorPlanFromComplex(Complex $complex, array $data): void
    {
        $plan = $data['interactive_plan'] ?? null;
        if (!$plan || !is_array($plan)) {
            return;
        }
        $path = $plan['path'] ?? null;
        $fileName = $plan['file_name'] ?? null;
        if (!$path || !$fileName) {
            return;
        }
        $url = $this->imageStore->buildUrlFromPathAndFile($path, $fileName);
        if (!$url) {
            return;
        }
        if ($this->dryRun) {
            return;
        }
        FloorPlan::firstOrCreate(
            [
                'complex_id' => $complex->id,
                'building_id' => null,
                'section_id' => null,
                'floor_id' => null,
                'floor_number' => 0,
            ],
            [
                'image_url' => $url,
                'interactive_data' => $plan,
                'raw_data' => $plan,
            ]
        );
    }

    /**
     * Синхронизация изображений для сущности (TrendAgentImage)
     */
    private function syncImagesForEntity($model, string $morphType, array $imageItems, ?string $urlKey = null): void
    {
        if ($this->dryRun || !$model?->id) {
            return;
        }

        $objectType = $morphType;
        $objectId = (string) $model->id;

        foreach ($imageItems as $index => $item) {
            $url = null;
            if (is_string($item)) {
                $url = $item;
            } elseif (is_array($item)) {
                if (isset($item['url'])) {
                    $url = $item['url'];
                } elseif (isset($item['path']) && isset($item['file_name'])) {
                    $url = $this->imageStore->buildUrlFromPathAndFile($item['path'], $item['file_name']);
                } elseif (isset($item['full'])) {
                    $url = $item['full'];
                }
            }
            if (!$url) {
                continue;
            }

            $existing = TrendAgentImage::where('object_type', $objectType)
                ->where('object_id', $objectId)
                ->where('url', $url)
                ->first();

            if ($existing) {
                if ($this->downloadImages && !$existing->local_path) {
                    $stored = $this->imageStore->storeFromUrl($url, $objectType, $objectId, $index);
                    $existing->update([
                        'local_path' => $stored['local_path'],
                        'mime' => $stored['mime'],
                        'size' => $stored['size'],
                        'hash' => $stored['hash'],
                        'download_status' => $stored['local_path'] ? 'ready' : 'failed',
                        'downloaded_at' => $stored['local_path'] ? now() : null,
                    ]);
                }
                continue;
            }

            $imgData = [
                'object_type' => $objectType,
                'object_id' => $objectId,
                'url' => $url,
                'type' => 'gallery',
                'order_index' => $index,
                'download_status' => 'pending',
            ];

            if ($this->downloadImages) {
                $stored = $this->imageStore->storeFromUrl($url, $objectType, $objectId, $index);
                $imgData['local_path'] = $stored['local_path'];
                $imgData['mime'] = $stored['mime'];
                $imgData['size'] = $stored['size'];
                $imgData['hash'] = $stored['hash'];
                $imgData['download_status'] = $stored['local_path'] ? 'ready' : 'failed';
                $imgData['downloaded_at'] = $stored['local_path'] ? now() : null;
            }

            TrendAgentImage::create($imgData);
        }
    }

    /**
     * Импорт квартиры
     */
    private function importApartment(array $data, Region $region): void
    {
        $externalId = $data['id'] ?? $data['_id'] ?? null;
        if (!$externalId) {
            return;
        }

        $complexId = null;
        if (isset($data['block_id']) || isset($data['block'])) {
            $blockId = $data['block_id'] ?? $data['block'];
            $complex = Complex::where('external_id', $blockId)->first();
            $complexId = $complex?->id;
        }

        $finishingTypeId = $this->resolveFinishingType($data);
        $statusId = $this->resolveStatus($data, 'apartment');

        $apartmentData = [
            'complex_id' => $complexId,
            'region_id' => $region->id,
            'external_id' => $externalId,
            'last_seen_at' => now(),
            'is_active' => true,
            'number' => $data['number'] ?? null,
            'rooms' => $data['rooms'] ?? $data['room'] ?? null,
            'area_total' => $data['area_total'] ?? $data['area'] ?? $data['privArea'] ?? null,
            'area_living' => $data['area_living'] ?? $data['roomsArea'] ?? null,
            'area_kitchen' => $data['area_kitchen'] ?? $data['kitchenArea'] ?? null,
            'floor' => $data['floor'] ?? $data['floor_number'] ?? null,
            'price_base' => $data['price_base'] ?? $data['base_price'] ?? $data['price'] ?? null,
            'price_full' => $data['price_full'] ?? null,
            'price_per_sqm' => $data['price_per_sqm'] ?? $data['price_m2'] ?? null,
            'finishing_type_id' => $finishingTypeId,
            'status_id' => $statusId,
            'is_exclusive' => $data['is_exclusive'] ?? $data['exclusive'] ?? false,
            'is_booked' => $data['is_booked'] ?? false,
            'is_on_request' => $data['is_on_request'] ?? false,
            'plan_image_url' => $this->extractString($data['plan_image']['url'] ?? $data['plan_image_url'] ?? null),
            'images' => $data['images'] ?? $data['gallery_images'] ?? [],
            'raw_data' => $data,
        ];

        if ($this->dryRun) {
            $this->addStats('apartments', 'created');
            return;
        }

        $apartment = Apartment::updateOrCreate(['external_id' => $externalId], $apartmentData);

        if ($apartment->wasRecentlyCreated) {
            $this->addStats('apartments', 'created');
        } else {
            $this->addStats('apartments', 'updated');
        }

        $planImg = $data['plan'] ?? $data['plan_image'] ?? null;
        $imagesToSync = $data['images'] ?? $data['gallery_images'] ?? [];
        if ($planImg && is_array($planImg) && isset($planImg['path'], $planImg['file_name'])) {
            $planUrl = $this->imageStore->buildUrlFromPathAndFile($planImg['path'], $planImg['file_name']);
            if ($planUrl) {
                $imagesToSync = array_merge([['path' => $planImg['path'], 'file_name' => $planImg['file_name']]], $imagesToSync);
            }
        }
        $this->syncImagesForEntity($apartment, 'apartment', $imagesToSync);
    }

    private function resolveFinishingType(array $data): ?int
    {
        $raw = $data['finishing']['name'] ?? $data['finishing_name'] ?? $data['finishing'] ?? null;
        $name = ImportNormalizers::finishingName($raw);
        if (!$name) {
            return null;
        }
        $code = \Illuminate\Support\Str::slug($name);
        $type = FinishingType::firstOrCreate(['code' => $code], ['name' => $name]);
        return $type->id;
    }

    private function resolveStatus(array $data, string $entityType = 'apartment'): ?int
    {
        $raw = $data['status']['name'] ?? $data['status']['label'] ?? $data['status_name'] ?? $data['status'] ?? null;
        $name = ImportNormalizers::statusName($raw);
        if (!$name) {
            return null;
        }
        $code = \Illuminate\Support\Str::slug($name);
        $status = Status::firstOrCreate(
            ['code' => $code],
            ['name' => $name, 'type' => $entityType]
        );
        return $status->id;
    }

    /**
     * Импорт паркинга
     */
    private function importParking(array $data, Region $region): void
    {
        $externalId = $data['id'] ?? $data['_id'] ?? null;
        if (!$externalId) {
            return;
        }

        $complexId = null;
        if (isset($data['block_id']) || isset($data['block'])) {
            $blockId = is_object($data['block_id'] ?? null) ? ($data['block_id']->id ?? null) : ($data['block_id'] ?? $data['block'] ?? null);
            $complex = Complex::where('external_id', $blockId)->first();
            $complexId = $complex?->id;
        }

        $now = now();
        $parkingData = [
            'complex_id' => $complexId,
            'region_id' => $region->id,
            'external_id' => $externalId,
            'last_seen_at' => $now,
            'is_active' => true,
            'name' => $data['name'] ?? null,
            'total_places' => $data['total_places'] ?? null,
            'available_places' => $data['available_places'] ?? null,
            'price_base' => $data['price_base'] ?? $data['price'] ?? null,
            'price_per_month' => $data['price_per_month'] ?? null,
            'images' => $data['images'] ?? [],
            'raw_data' => $data,
        ];

        if ($this->dryRun) {
            $this->addStats('parkings', 'created');
            return;
        }

        $parking = Parking::updateOrCreate(['external_id' => $externalId], $parkingData);

        if ($parking->wasRecentlyCreated) {
            $this->addStats('parkings', 'created');
        } else {
            $this->addStats('parkings', 'updated');
        }

        $this->syncImagesForEntity($parking, 'parking', $data['images'] ?? []);
    }

    /**
     * Импорт места парковки (из raw списка)
     */
    private function importParkingPlace(array $data, Region $region): void
    {
        $externalId = $data['_id'] ?? $data['id'] ?? null;
        if (!$externalId) {
            return;
        }

        $blockId = $data['block_id'] ?? null;
        if (!$blockId) {
            return;
        }

        $complex = Complex::where('external_id', $blockId)->first();
        if (!$complex) {
            return;
        }

        $parking = Parking::firstOrCreate(
            ['external_id' => 'parking_' . $blockId],
            [
                'complex_id' => $complex->id,
                'region_id' => $region->id,
                'name' => "Паркинг {$complex->name}",
                'raw_data' => [],
            ]
        );

        $statusId = $this->resolveStatus($data, 'parking');

        if ($this->dryRun) {
            $this->addStats('parking_places', 'created');
            return;
        }

        $place = ParkingPlace::updateOrCreate(
            ['parking_id' => $parking->id, 'external_id' => $externalId],
            [
                'number' => $data['number'] ?? null,
                'level' => $data['floor'] ?? $data['level'] ?? null,
                'status_id' => $statusId,
                'price' => $data['price'] ?? null,
                'raw_data' => $data,
            ]
        );

        if ($place->wasRecentlyCreated) {
            $this->addStats('parking_places', 'created');
        } else {
            $this->addStats('parking_places', 'updated');
        }
    }

    /**
     * Импорт дома
     */
    private function importHouse(array $data, Region $region): void
    {
        $externalId = $data['id'] ?? $data['_id'] ?? null;
        if (!$externalId) {
            return;
        }

        $now = now();
        $houseData = [
            'region_id' => $region->id,
            'external_id' => $externalId,
            'last_seen_at' => $now,
            'is_active' => true,
            'guid' => $data['guid'] ?? $data['slug'] ?? null,
            'name' => $data['name'] ?? null,
            'address' => $data['address'] ?? null,
            'land_area' => $data['land_area'] ?? null,
            'house_area' => $data['house_area'] ?? $data['area'] ?? null,
            'floors_count' => $data['floors_count'] ?? null,
            'rooms_count' => $data['rooms_count'] ?? $data['rooms'] ?? null,
            'price_base' => $data['price_base'] ?? $data['price'] ?? null,
            'images' => $data['images'] ?? [],
            'raw_data' => $data,
        ];

        if ($this->dryRun) {
            $this->addStats('houses', 'created');
            return;
        }

        $house = House::updateOrCreate(
            ['external_id' => $externalId],
            $houseData
        );

        if ($house->wasRecentlyCreated) {
            $this->addStats('houses', 'created');
        } else {
            $this->addStats('houses', 'updated');
        }

        $this->syncImagesForEntity($house, 'house', $data['images'] ?? []);
    }

    /**
     * Импорт участка
     */
    private function importPlot(array $data, Region $region): void
    {
        $externalId = $data['id'] ?? $data['_id'] ?? null;
        if (!$externalId) {
            return;
        }

        // Сначала создаем или получаем поселок
        $settlementId = null;
        if (isset($data['village_id']) || isset($data['village'])) {
            $villageId = $data['village_id'] ?? $data['village'];
            $settlement = PlotSettlement::where('external_id', $villageId)->first();
            if (!$settlement && !$this->dryRun) {
                // Создаем поселок из данных участка
                $settlement = PlotSettlement::create([
                    'region_id' => $region->id,
                    'external_id' => $villageId,
                    'name' => $data['village_name'] ?? 'Неизвестный поселок',
                    'last_seen_at' => now(),
                    'is_active' => true,
                ]);
            }
            $settlementId = $settlement?->id;
        }

        $now = now();
        $plotData = [
            'region_id' => $region->id,
            'settlement_id' => $settlementId,
            'external_id' => $externalId,
            'last_seen_at' => $now,
            'is_active' => true,
            'number' => $data['number'] ?? null,
            'area' => $data['area'] ?? null,
            'cadastral_number' => $data['cadastral_number'] ?? null,
            'price_base' => $data['price_base'] ?? $data['price'] ?? null,
            'utilities' => $data['utilities'] ?? [],
            'raw_data' => $data,
        ];

        if ($this->dryRun) {
            $this->addStats('plots', 'created');
            return;
        }

        $plot = Plot::updateOrCreate(
            ['external_id' => $externalId],
            $plotData
        );

        if ($plot->wasRecentlyCreated) {
            $this->addStats('plots', 'created');
        } else {
            $this->addStats('plots', 'updated');
        }
    }

    /**
     * Импорт коммерции
     */
    private function importCommercial(array $data, Region $region): void
    {
        $externalId = $data['id'] ?? $data['_id'] ?? null;
        if (!$externalId) {
            return;
        }

        $complexId = null;
        if (isset($data['block_id']) || isset($data['block'])) {
            $blockId = $data['block_id'] ?? $data['block'];
            $complex = Complex::where('external_id', $blockId)->first();
            $complexId = $complex?->id;
        }

        $now = now();
        $commercialData = [
            'complex_id' => $complexId,
            'region_id' => $region->id,
            'external_id' => $externalId,
            'last_seen_at' => $now,
            'is_active' => true,
            'name' => $data['name'] ?? null,
            'area_total' => $data['area_total'] ?? $data['area'] ?? null,
            'price_base' => $data['price_base'] ?? $data['price'] ?? null,
            'rent_price' => $data['rent_price'] ?? null,
            'floor' => $data['floor'] ?? null,
            'images' => $data['images'] ?? [],
            'raw_data' => $data,
        ];

        if ($this->dryRun) {
            $this->addStats('commercial', 'created');
            return;
        }

        $commercial = Commercial::updateOrCreate(
            ['external_id' => $externalId],
            $commercialData
        );

        if ($commercial->wasRecentlyCreated) {
            $this->addStats('commercial', 'created');
        } else {
            $this->addStats('commercial', 'updated');
        }

        $this->syncImagesForEntity($commercial, 'commercial', $data['images'] ?? []);
    }

    /**
     * Импорт подрядчика (details/contractors — это contractor_projects)
     */
    private function importContractor(array $data, Region $region): void
    {
        $now = now();
        $externalId = $data['id'] ?? $data['_id'] ?? $data['_raw']['_id'] ?? null;
        if (!$externalId) {
            return;
        }

        $contractorId = $data['contractor_id'] ?? $data['_raw']['contractor_id'] ?? null;
        $contractorName = $data['contractor_name']['value'] ?? $data['contractor']['name'] ?? $data['_raw']['contractor_name']['value'] ?? null;

        $contractor = null;
        if ($contractorId && !$this->dryRun) {
            $contractor = Contractor::firstOrCreate(
                ['external_id' => $contractorId],
                ['name' => $contractorName ?? 'Неизвестный подрядчик', 'raw_data' => []]
            );
        }

        $projectData = [
            'external_id' => $externalId,
            'guid' => $data['guid'] ?? $data['slug'] ?? null,
            'name' => $data['name'] ?? $data['_raw']['name']['value'] ?? '',
            'description' => $data['description'] ?? null,
            'min_price' => $data['min_price'] ?? null,
            'area_total' => $data['area_total'] ?? null,
            'area_living' => $data['area_living'] ?? null,
            'construction_time' => $data['construction_time'] ?? $data['_raw']['construction_period']['value'] ?? null,
            'technology' => $data['technology'] ?? $data['_raw']['technology_list']['value'] ?? null,
            'images' => $data['images'] ?? $data['_raw']['images'] ?? [],
            'raw_data' => $data,
        ];

        if ($this->dryRun) {
            $this->addStats('contractors', 'created');
            return;
        }

        if (!$contractor) {
            return;
        }

        $project = ContractorProject::updateOrCreate(
            ['external_id' => $externalId],
            array_merge($projectData, [
                'contractor_id' => $contractor->id,
                'last_seen_at' => $now,
                'is_active' => true,
            ])
        );

        if ($project->wasRecentlyCreated) {
            $this->addStats('contractors', 'created');
        } else {
            $this->addStats('contractors', 'updated');
        }

        $imgs = $data['images'] ?? $data['_raw']['images'] ?? [];
        $urls = [];
        foreach ($imgs as $img) {
            if (isset($img['path'], $img['file_name'])) {
                $url = $this->imageStore->buildUrlFromPathAndFile($img['path'], $img['file_name']);
                if ($url) {
                    $urls[] = ['path' => $img['path'], 'file_name' => $img['file_name']];
                }
            } elseif (isset($img['full'])) {
                $urls[] = $img['full'];
            }
        }
        $this->syncImagesForEntity($project, 'contractor_project', $urls);
    }

    /**
     * Вывести статистику
     */
    private function displayStatistics(): void
    {
        $this->newLine();
        $this->info("📊 Статистика импорта:");
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Создано', $this->statistics['imported']],
                ['Обновлено', $this->statistics['updated']],
                ['Пропущено', $this->statistics['skipped'] ?? 0],
                ['Ошибок', $this->statistics['errors']],
            ]
        );

        if (!empty($this->statsByType)) {
            $this->newLine();
            $this->info("📋 По типам:");
            $rows = [];
            foreach ($this->statsByType as $type => $stats) {
                $rows[] = [
                    $type,
                    $stats['created'] ?? 0,
                    $stats['updated'] ?? 0,
                    $stats['errors'] ?? 0,
                ];
            }
            $this->table(['Тип', 'Создано', 'Обновлено', 'Ошибок'], $rows);
        }
    }
}
