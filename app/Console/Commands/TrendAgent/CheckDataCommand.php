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
        
        foreach ($regions as $region) {
            $apartmentsCount = 0;
            
            // Способ 1: Через комплексы (если комплексы есть)
            $apartmentsViaComplexes = Apartment::whereHas('complex', function($query) use ($region) {
                $query->where('region_id', $region->id);
            })->count();
            
            // Способ 2: Через raw_data (если комплексов нет)
            if ($apartmentsViaComplexes == 0) {
                // Ищем в raw_data поле city.guid
                $apartmentsViaRawData = Apartment::whereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.guid')) = ?", [$region->code])
                    ->count();
                $apartmentsCount = $apartmentsViaRawData;
            } else {
                $apartmentsCount = $apartmentsViaComplexes;
            }
            
            $tableData[] = [
                $region->code,
                $region->name,
                number_format($apartmentsCount, 0, ',', ' '),
            ];
        }
        
        // Также показываем квартиры без региона (NULL или неизвестный регион)
        // Сначала проверяем, какие city.guid есть в raw_data
        $unknownRegions = Apartment::selectRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.guid')) as city_guid, COUNT(*) as count")
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.guid')) IS NOT NULL")
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.guid')) NOT IN (SELECT code FROM trendagent_regions)")
            ->groupBy('city_guid')
            ->get();
        
        foreach ($unknownRegions as $unknown) {
            if ($unknown->city_guid) {
                $tableData[] = [
                    $unknown->city_guid,
                    'Неизвестный регион (' . $unknown->city_guid . ')',
                    number_format($unknown->count, 0, ',', ' '),
                ];
            }
        }
        
        // Квартиры без city.guid в raw_data
        $apartmentsWithoutCityGuid = Apartment::whereRaw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.guid')) IS NULL")
            ->whereDoesntHave('complex', function($query) {
                $query->whereNotNull('region_id');
            })
            ->count();
        
        if ($apartmentsWithoutCityGuid > 0) {
            $tableData[] = [
                '?',
                'Без региона (NULL)',
                number_format($apartmentsWithoutCityGuid, 0, ',', ' '),
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
