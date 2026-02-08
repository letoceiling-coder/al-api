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
        $apartmentsWithoutRegion = Apartment::whereDoesntHave('complex', function($query) {
            $query->whereNotNull('region_id');
        })
        ->whereRaw("(JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.guid')) IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.guid')) NOT IN (SELECT code FROM trendagent_regions))")
        ->count();
        
        if ($apartmentsWithoutRegion > 0) {
            $tableData[] = [
                '?',
                'Неизвестный регион',
                number_format($apartmentsWithoutRegion, 0, ',', ' '),
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
