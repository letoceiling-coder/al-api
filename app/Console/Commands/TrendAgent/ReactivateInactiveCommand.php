<?php

namespace App\Console\Commands\TrendAgent;

use App\Models\TrendAgent\Complex;
use App\Models\TrendAgent\Apartment;
use App\Models\TrendAgent\Parking;
use Illuminate\Console\Command;

class ReactivateInactiveCommand extends Command
{
    protected $signature = 'trendagent:reactivate-inactive
                            {--dry-run : Показать, что будет реактивировано}
                            {--tables=complexes,apartments,parkings : Таблицы через запятую}';

    protected $description = 'Реактивировать все записи с is_active=false (после ошибочной деактивации)';

    public function handle(): int
    {
        $tables = array_map('trim', explode(',', $this->option('tables')));
        $dryRun = $this->option('dry-run');

        $map = [
            'complexes' => Complex::class,
            'apartments' => Apartment::class,
            'parkings' => Parking::class,
        ];

        foreach ($tables as $table) {
            if (!isset($map[$table])) {
                continue;
            }
            $model = $map[$table];
            if (!\Illuminate\Support\Facades\Schema::hasColumn((new $model)->getTable(), 'is_active')) {
                continue;
            }
            $count = $model::where('is_active', false)->count();
            if ($count === 0) {
                $this->info("   [{$table}] Нет неактивных записей");
                continue;
            }
            if ($dryRun) {
                $this->warn("   [{$table}] Будет реактивировано: {$count}");
                continue;
            }
            $updated = $model::where('is_active', false)->update(['is_active' => true]);
            $this->info("   [{$table}] Реактивировано: {$updated}");
        }

        return 0;
    }
}
