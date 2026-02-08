<?php

namespace App\Console\Commands\TrendAgent;

use Illuminate\Console\Command;
use App\Models\TrendAgent\Region;
use App\Models\TrendAgent\Complex;
use App\Models\TrendAgent\Apartment;
use App\Models\TrendAgent\Parking;
use App\Models\TrendAgent\House;
use App\Models\TrendAgent\Plot;
use App\Models\TrendAgent\Commercial;
use App\Models\TrendAgent\Contractor;
use App\Services\TrendAgent\CityService;

class CheckDataCommand extends Command
{
    protected $signature = 'trendagent:check-data';
    protected $description = 'Проверка количества записей в БД';

    public function handle(): int
    {
        $this->info('📊 Статистика данных в БД:');
        $this->newLine();

        $this->table(
            ['Тип', 'Количество'],
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

        // Показываем распределение квартир по регионам
        $this->newLine();
        $this->info('📊 Квартиры по регионам:');
        $this->newLine();

        // Квартиры могут быть связаны с регионами через комплексы или через raw_data
        $regions = Region::all();
        $tableData = [];
        
        // Получаем все city ID из CityService для сопоставления
        $allCities = CityService::getAllCities();
        
        foreach ($regions as $region) {
            $apartmentsCount = 0;
            
            // Способ 1: Через комплексы (если комплексы есть)
            $apartmentsViaComplexes = Apartment::whereHas('complex', function($query) use ($region) {
                $query->where('region_id', $region->id);
            })->count();
            
            // Способ 2: Через raw_data (если комплексов нет)
            if ($apartmentsViaComplexes == 0) {
                // Получаем city ID для региона из CityService
                $cityInfo = CityService::getCityByKey($region->code);
                if ($cityInfo && isset($cityInfo['id'])) {
                    // Ищем по city.id в raw_data
                    $apartmentsViaCityId = Apartment::whereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.id')) = ?", [$cityInfo['id']])
                        ->count();
                    $apartmentsCount += $apartmentsViaCityId;
                }
                
                // Также ищем по city.guid (на случай, если используется guid вместо id)
                $apartmentsViaGuid = Apartment::whereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.guid')) = ?", [$region->code])
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.id')) IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.id')) != ?", [$cityInfo['id'] ?? ''])
                    ->count();
                $apartmentsCount += $apartmentsViaGuid;
            } else {
                $apartmentsCount = $apartmentsViaComplexes;
            }
            
            $tableData[] = [
                $region->code,
                $region->name,
                number_format($apartmentsCount, 0, ',', ' '),
            ];
        }
        
        // Проверяем квартиры с неизвестными city.id или city.guid
        // Сначала получаем все известные city ID
        $knownCityIds = array_column($allCities, 'id');
        $knownCityKeys = array_keys($allCities);
        
        // Ищем квартиры с city.id, которых нет в CityService
        $unknownCityIds = Apartment::selectRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.id')) as city_id, COUNT(*) as count")
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.id')) IS NOT NULL")
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.id')) NOT IN ('" . implode("','", $knownCityIds) . "')")
            ->groupBy('city_id')
            ->get();
        
        foreach ($unknownCityIds as $unknown) {
            if ($unknown->city_id) {
                $tableData[] = [
                    $unknown->city_id,
                    'Неизвестный регион (ID: ' . substr($unknown->city_id, 0, 8) . '...)',
                    number_format($unknown->count, 0, ',', ' '),
                ];
            }
        }
        
        // Ищем квартиры с city.guid, которых нет в известных ключах
        $unknownGuids = Apartment::selectRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.guid')) as city_guid, COUNT(*) as count")
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.guid')) IS NOT NULL")
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.id')) IS NULL")
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.guid')) NOT IN ('" . implode("','", $knownCityKeys) . "')")
            ->groupBy('city_guid')
            ->get();
        
        foreach ($unknownGuids as $unknown) {
            if ($unknown->city_guid) {
                // Пробуем найти через subdomain маппинг
                $mappedKey = CityService::getCityKeyBySubdomain($unknown->city_guid);
                if ($mappedKey) {
                    // Если нашли через маппинг, добавляем к соответствующему региону
                    $found = false;
                    foreach ($tableData as &$row) {
                        if ($row[0] === $mappedKey) {
                            $row[2] = number_format((int)str_replace(' ', '', $row[2]) + $unknown->count, 0, ',', ' ');
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $region = Region::where('code', $mappedKey)->first();
                        if ($region) {
                            $tableData[] = [
                                $mappedKey,
                                $region->name,
                                number_format($unknown->count, 0, ',', ' '),
                            ];
                        }
                    }
                } else {
                    $tableData[] = [
                        $unknown->city_guid,
                        'Неизвестный регион (guid: ' . $unknown->city_guid . ')',
                        number_format($unknown->count, 0, ',', ' '),
                    ];
                }
            }
        }
        
        // Квартиры без city.id и city.guid в raw_data
        $apartmentsWithoutCity = Apartment::whereRaw("(JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.id')) IS NULL AND JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.guid')) IS NULL)")
            ->whereDoesntHave('complex', function($query) {
                $query->whereNotNull('region_id');
            })
            ->count();
        
        if ($apartmentsWithoutCity > 0) {
            $tableData[] = [
                '?',
                'Без региона (NULL)',
                number_format($apartmentsWithoutCity, 0, ',', ' '),
            ];
        }

        if (empty($tableData)) {
            $this->warn('  Нет данных по регионам');
        } else {
            $this->table(
                ['Код', 'Название', 'Квартир'],
                $tableData
            );
        }

        return 0;
    }
}
