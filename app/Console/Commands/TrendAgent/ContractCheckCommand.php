<?php

namespace App\Console\Commands\TrendAgent;

use App\Services\TrendAgent\ContractValidatorService;
use Illuminate\Console\Command;

class ContractCheckCommand extends Command
{
    protected $signature = 'trendagent:contract-check
                            {--base-url= : Base URL для запросов (default: app.url, игнорируется при --internal)}
                            {--internal : Вызов контроллеров напрямую (без HTTP, для CI)}';

    protected $description = 'Проверка контракта DB API: cities, objects-list, apartments list, apartment detail';

    public function handle(): int
    {
        $useInternal = $this->option('internal');
        $baseUrl = $this->option('base-url') ?: config('app.url');
        $service = new ContractValidatorService($baseUrl, $useInternal);

        $this->info('Проверка контракта TrendAgent DB API...');
        $this->info("Base URL: {$baseUrl}");
        $this->newLine();

        config(['trendagent.data_source' => 'db']);
        $results = $service->validateAll();

        $allOk = true;
        $output = [];

        foreach ($results as $name => $result) {
            $ok = $result['ok'] ?? false;
            $errors = $result['errors'] ?? [];
            $warnings = $result['warnings'] ?? [];

            if (!$ok) {
                $allOk = false;
            }

            $output[$name] = [
                'ok' => $ok,
                'errors' => $errors,
                'warnings' => $warnings,
            ];

            if ($this->option('json')) {
                continue;
            }

            $icon = $ok ? '✓' : '✗';
            $this->line("{$icon} {$name}");
            foreach ($errors as $e) {
                $this->error('  - ' . ($e['path'] ?? '') . ': ' . ($e['message'] ?? '') . ' ' . json_encode($e));
            }
            foreach ($warnings as $w) {
                $this->warn('  ~ ' . ($w['message'] ?? ''));
            }
        }

        if ($this->option('json')) {
            $this->line(json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        $this->newLine();
        if ($allOk) {
            $this->info('Контракт соблюдён.');
            return 0;
        }
        $this->error('Обнаружены несоответствия контракту.');
        return 1;
    }
}
