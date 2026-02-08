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

        // Квартиры связаны с регионами через комплексы
        $regions = Region::all();
        $tableData = [];
        foreach ($regions as $region) {
            // Считаем квартиры через комплексы региона
            $apartmentsCount = Apartment::whereHas('complex', function($query) use ($region) {
                $query->where('region_id', $region->id);
            })->count();
            
            $tableData[] = [
                $region->code,
                $region->name,
                number_format($apartmentsCount, 0, ',', ' '),
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
