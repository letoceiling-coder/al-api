<?php

namespace App\Console\Commands\TrendAgent;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
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
use Exception;

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
                            {--dry-run : Пробный запуск без сохранения в БД}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импорт данных из парсинга в базу данных';

    private string $region;
    private array $basePaths;
    private bool $dryRun;
    private array $statistics = [
        'imported' => 0,
        'updated' => 0,
        'errors' => 0,
    ];

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

        $this->info("📥 Начинаю импорт данных TrendAgent");
        $this->info("📍 Регион: {$this->region}");
        if ($this->dryRun) {
            $this->warn("⚠️  Режим пробного запуска (dry-run) - данные не будут сохранены");
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

        // Создаем или получаем регион
        $region = $this->getOrCreateRegion();

        $type = $this->option('type');
        $types = $type === 'all' 
            ? ['complexes', 'apartments', 'parkings', 'houses', 'plots', 'commercial', 'contractors']
            : [$type];

        foreach ($types as $objectType) {
            $this->info("📦 Импорт типа: {$objectType}");
            $this->importType($objectType, $region);
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
        // Ищем файлы во всех возможных путях
        $files = [];
        foreach ($this->basePaths as $basePath) {
            $detailsPath = "{$basePath}/details/{$type}";
            if (File::exists($detailsPath)) {
                $foundFiles = File::glob("{$detailsPath}/*.json");
                $files = array_merge($files, $foundFiles);
            }
        }
        
        $total = count($files);
        
        if ($total === 0) {
            $this->warn("   ⚠️  Файлы не найдены. Проверены пути:");
            foreach ($this->basePaths as $basePath) {
                $this->line("      - {$basePath}/details/{$type}");
            }
            return;
        }

        $this->info("   📁 Найдено файлов: {$total}");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($files as $file) {
            try {
                $data = json_decode(File::get($file), true);
                
                if (!$data) {
                    $this->statistics['errors']++;
                    $bar->advance();
                    continue;
                }

                match($type) {
                    'complexes' => $this->importComplex($data, $region),
                    'apartments' => $this->importApartment($data, $region),
                    'parkings' => $this->importParking($data, $region),
                    'houses' => $this->importHouse($data, $region),
                    'plots' => $this->importPlot($data, $region),
                    'commercial' => $this->importCommercial($data, $region),
                    'contractors' => $this->importContractor($data),
                    default => null,
                };

                $bar->advance();
            } catch (Exception $e) {
                $this->statistics['errors']++;
                $this->error("   ❌ Ошибка при импорте {$file}: {$e->getMessage()}");
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Импорт комплекса
     */
    private function importComplex(array $data, Region $region): void
    {
        $externalId = $data['id'] ?? $data['_id'] ?? null;
        if (!$externalId) {
            return;
        }

        $complexData = [
            'region_id' => $region->id,
            'external_id' => $externalId,
            'guid' => $data['guid'] ?? $data['slug'] ?? null,
            'name' => $data['name'] ?? '',
            'address' => $data['address'] ?? null,
            'description' => $data['description'] ?? null,
            'latitude' => $data['latitude'] ?? $data['location']['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? $data['location']['longitude'] ?? null,
            'developer_name' => $data['developer']['name'] ?? null,
            'class_type' => $data['class_type'] ?? null,
            'deadline' => $data['deadline'] ?? null,
            'status' => $data['status'] ?? null,
            'min_price' => $data['min_price'] ?? null,
            'images' => $data['images'] ?? [],
            'advantages' => $data['advantages'] ?? [],
            'nearby_places' => $data['nearby_places'] ?? [],
            'videos' => $data['videos'] ?? [],
            'files' => $data['files'] ?? [],
            'raw_data' => $data,
        ];

        if ($this->dryRun) {
            $this->statistics['imported']++;
            return;
        }

        $complex = Complex::updateOrCreate(
            ['external_id' => $externalId],
            $complexData
        );

        if ($complex->wasRecentlyCreated) {
            $this->statistics['imported']++;
        } else {
            $this->statistics['updated']++;
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

        $apartmentData = [
            'complex_id' => $complexId,
            'external_id' => $externalId,
            'number' => $data['number'] ?? null,
            'rooms' => $data['rooms'] ?? null,
            'area_total' => $data['area_total'] ?? $data['area'] ?? null,
            'area_living' => $data['area_living'] ?? null,
            'area_kitchen' => $data['area_kitchen'] ?? null,
            'floor' => $data['floor'] ?? null,
            'price_base' => $data['price_base'] ?? $data['price'] ?? null,
            'price_full' => $data['price_full'] ?? null,
            'price_per_sqm' => $data['price_per_sqm'] ?? null,
            'is_exclusive' => $data['is_exclusive'] ?? false,
            'is_booked' => $data['is_booked'] ?? false,
            'is_on_request' => $data['is_on_request'] ?? false,
            'plan_image_url' => $data['plan_image']['url'] ?? $data['plan_image_url'] ?? null,
            'images' => $data['images'] ?? $data['gallery_images'] ?? [],
            'raw_data' => $data,
        ];

        if ($this->dryRun) {
            $this->statistics['imported']++;
            return;
        }

        $apartment = Apartment::updateOrCreate(
            ['external_id' => $externalId],
            $apartmentData
        );

        if ($apartment->wasRecentlyCreated) {
            $this->statistics['imported']++;
        } else {
            $this->statistics['updated']++;
        }
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
            $blockId = $data['block_id'] ?? $data['block'];
            $complex = Complex::where('external_id', $blockId)->first();
            $complexId = $complex?->id;
        }

        $parkingData = [
            'complex_id' => $complexId,
            'external_id' => $externalId,
            'name' => $data['name'] ?? null,
            'total_places' => $data['total_places'] ?? null,
            'available_places' => $data['available_places'] ?? null,
            'price_base' => $data['price_base'] ?? $data['price'] ?? null,
            'price_per_month' => $data['price_per_month'] ?? null,
            'images' => $data['images'] ?? [],
            'raw_data' => $data,
        ];

        if ($this->dryRun) {
            $this->statistics['imported']++;
            return;
        }

        $parking = Parking::updateOrCreate(
            ['external_id' => $externalId],
            $parkingData
        );

        if ($parking->wasRecentlyCreated) {
            $this->statistics['imported']++;
        } else {
            $this->statistics['updated']++;
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

        $houseData = [
            'region_id' => $region->id,
            'external_id' => $externalId,
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
            $this->statistics['imported']++;
            return;
        }

        $house = House::updateOrCreate(
            ['external_id' => $externalId],
            $houseData
        );

        if ($house->wasRecentlyCreated) {
            $this->statistics['imported']++;
        } else {
            $this->statistics['updated']++;
        }
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
                ]);
            }
            $settlementId = $settlement?->id;
        }

        $plotData = [
            'region_id' => $region->id,
            'settlement_id' => $settlementId,
            'external_id' => $externalId,
            'number' => $data['number'] ?? null,
            'area' => $data['area'] ?? null,
            'cadastral_number' => $data['cadastral_number'] ?? null,
            'price_base' => $data['price_base'] ?? $data['price'] ?? null,
            'utilities' => $data['utilities'] ?? [],
            'raw_data' => $data,
        ];

        if ($this->dryRun) {
            $this->statistics['imported']++;
            return;
        }

        $plot = Plot::updateOrCreate(
            ['external_id' => $externalId],
            $plotData
        );

        if ($plot->wasRecentlyCreated) {
            $this->statistics['imported']++;
        } else {
            $this->statistics['updated']++;
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

        $commercialData = [
            'complex_id' => $complexId,
            'external_id' => $externalId,
            'name' => $data['name'] ?? null,
            'area_total' => $data['area_total'] ?? $data['area'] ?? null,
            'price_base' => $data['price_base'] ?? $data['price'] ?? null,
            'rent_price' => $data['rent_price'] ?? null,
            'floor' => $data['floor'] ?? null,
            'images' => $data['images'] ?? [],
            'raw_data' => $data,
        ];

        if ($this->dryRun) {
            $this->statistics['imported']++;
            return;
        }

        $commercial = Commercial::updateOrCreate(
            ['external_id' => $externalId],
            $commercialData
        );

        if ($commercial->wasRecentlyCreated) {
            $this->statistics['imported']++;
        } else {
            $this->statistics['updated']++;
        }
    }

    /**
     * Импорт подрядчика
     */
    private function importContractor(array $data): void
    {
        $externalId = $data['id'] ?? $data['_id'] ?? null;
        if (!$externalId) {
            return;
        }

        $contractorData = [
            'external_id' => $externalId,
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? null,
            'logo_url' => $data['logo_url'] ?? $data['logo']['url'] ?? null,
            'website' => $data['website'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'raw_data' => $data,
        ];

        if ($this->dryRun) {
            $this->statistics['imported']++;
            return;
        }

        $contractor = Contractor::updateOrCreate(
            ['external_id' => $externalId],
            $contractorData
        );

        if ($contractor->wasRecentlyCreated) {
            $this->statistics['imported']++;
        } else {
            $this->statistics['updated']++;
        }

        // Импортируем проекты подрядчика
        if (isset($data['projects']) && is_array($data['projects'])) {
            foreach ($data['projects'] as $projectData) {
                $this->importContractorProject($projectData, $contractor);
            }
        }
    }

    /**
     * Импорт проекта подрядчика
     */
    private function importContractorProject(array $data, Contractor $contractor): void
    {
        $externalId = $data['id'] ?? $data['_id'] ?? null;
        if (!$externalId) {
            return;
        }

        $projectData = [
            'contractor_id' => $contractor->id,
            'external_id' => $externalId,
            'guid' => $data['guid'] ?? $data['slug'] ?? null,
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? null,
            'min_price' => $data['min_price'] ?? null,
            'area_total' => $data['area_total'] ?? null,
            'area_living' => $data['area_living'] ?? null,
            'construction_time' => $data['construction_time'] ?? null,
            'technology' => $data['technology'] ?? null,
            'images' => $data['images'] ?? [],
            'raw_data' => $data,
        ];

        if ($this->dryRun) {
            return;
        }

        ContractorProject::updateOrCreate(
            ['external_id' => $externalId],
            $projectData
        );
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
                ['Импортировано', $this->statistics['imported']],
                ['Обновлено', $this->statistics['updated']],
                ['Ошибок', $this->statistics['errors']],
            ]
        );
    }
}
