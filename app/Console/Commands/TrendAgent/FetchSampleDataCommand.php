<?php

namespace App\Console\Commands\TrendAgent;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use App\Services\TrendAgent\Core\ObjectType;
use App\Services\TrendAgent\CityService;
use App\Services\TrendAgent\SampleDataService;

/**
 * Тестовое получение небольшого массива данных по всем типам объектов TrendAgent.
 * Показывает структуру каталога и деталей по каждому типу для оценки объёма данных.
 */
class FetchSampleDataCommand extends Command
{
    protected $signature = 'trendagent:fetch-sample-data
                            {--city=spb : Код города (spb, msk, ...)}
                            {--per-type=3 : Количество элементов в каталоге на тип}
                            {--no-detail : Не запрашивать детали по первому элементу}
                            {--no-db : Не использовать БД/кэш (город только spb/msk, кэш в памяти)}
                            {--json= : Путь к файлу для сохранения полного JSON}';

    protected $description = 'Получить тестовый массив данных по всем типам объектов (ЖК, квартиры, паркинги, дома, участки, коммерция, проекты домов, посёлки)';

    public function handle(SampleDataService $sampleDataService): int
    {
        $cityKey = $this->option('city');
        $perType = (int) $this->option('per-type');
        $fetchDetail = !$this->option('no-detail');
        $jsonPath = $this->option('json');
        $noDb = $this->option('no-db');

        if ($noDb) {
            Config::set('cache.default', 'array');
            $cityInfo = $this->getFallbackCity($cityKey);
            if (!$cityInfo) {
                $this->error("С флагом --no-db доступны только города: spb, msk. Укажите --city=spb или --city=msk");
                return 1;
            }
            $this->line('<comment>Режим --no-db: БД и кэш не используются, кэш в памяти.</comment>');
        } else {
            $cityInfo = CityService::getCityByKey($cityKey) ?? $this->getFallbackCity($cityKey);
        }

        if (!$cityInfo || empty($cityInfo['id'])) {
            $this->error("Город не найден: {$cityKey}");
            return 1;
        }

        $this->info("Город: {$cityInfo['name']} ({$cityKey})");
        $this->info('Запрос каталога: ' . $perType . ' элементов на тип. Детали: ' . ($fetchDetail ? 'да' : 'нет'));
        $this->newLine();

        $results = $sampleDataService->fetchAllTypes($cityKey, $perType, $fetchDetail);

        foreach ($results as $type => $summary) {
            $this->info("--- {$summary['label']} ({$type}) ---");
            $this->line('  Каталог: total=' . $summary['catalog']['total'] . ', получено=' . $summary['catalog']['items_count']);
            $this->line('  Ключи элемента: ' . implode(', ', $summary['catalog']['first_item_keys'] ?: ['—']));
            if ($summary['detail']['requested_id']) {
                if ($summary['detail']['error']) {
                    $this->line('  Детали: ошибка — ' . $summary['detail']['error']);
                } else {
                    $this->line('  Детали: ключей entity=' . count($summary['detail']['entity_keys']) . ', related=' . implode(', ', $summary['detail']['related_keys'] ?: ['—']));
                    $this->line('  Медиа: photos=' . $summary['detail']['media']['photos'] . ', videos=' . $summary['detail']['media']['videos'] . ', documents=' . $summary['detail']['media']['documents']);
                }
            }
            $this->newLine();
        }

        if ($jsonPath) {
            $dir = dirname($jsonPath);
            if ($dir && !is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            file_put_contents($jsonPath, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("Полный JSON сохранён: {$jsonPath}");
        }

        $this->info('Готово. Итог по типам: каталог (total, ключи первого элемента), детали (entity keys, related, media).');
        return 0;
    }

    /**
     * Запасной список городов без обращения к кэшу/БД
     */
    private function getFallbackCity(string $key): ?array
    {
        $cities = [
            'spb' => ['id' => '58c665588b6aa52311afa01b', 'name' => 'Санкт-Петербург'],
            'msk' => ['id' => '5a5cb42159042faa9a218d04', 'name' => 'Москва'],
        ];
        return $cities[$key] ?? null;
    }
}
