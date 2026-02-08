<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class DeployTrendagentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:trendagent
                            {--skip-git : Пропустить отправку в git (только для локального запуска)}
                            {--skip-build : Пропустить сборку проекта (npm run build)}
                            {--skip-migrations : Пропустить выполнение миграций}
                            {--skip-cache : Пропустить очистку кеша}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Развертывание проекта TrendAgent: git push, обновление на сервере, сборка, миграции, очистка кеша';

    private string $projectPath;
    private bool $isServer;
    private array $report = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->projectPath = base_path('projects/trendagent');
        $this->isServer = $this->detectServer();

        $this->info('🚀 Начинаю развертывание проекта TrendAgent');
        $this->newLine();

        if (!$this->isServer) {
            $this->info('📍 Режим: Локальная машина');
            $this->deployLocal();
        } else {
            $this->info('📍 Режим: Сервер');
            $this->deployServer();
        }

        $this->displayReport();

        return Command::SUCCESS;
    }

    /**
     * Определение, запущена ли команда на сервере
     */
    private function detectServer(): bool
    {
        // Проверяем по hostname или другим признакам
        $hostname = gethostname();
        $isServer = str_contains($hostname, 'titdsftsmw') || 
                   str_contains($hostname, 'server') ||
                   file_exists('/var/www/AL/projects/trendagent');
        
        return $isServer;
    }

    /**
     * Развертывание на локальной машине
     */
    private function deployLocal(): void
    {
        if (!$this->option('skip-git')) {
            $this->step('📤 Отправка в Git', function() {
                return $this->gitPush();
            });
        } else {
            $this->report[] = ['step' => '📤 Отправка в Git', 'status' => 'skipped', 'message' => 'Пропущено (--skip-git)'];
        }

        $this->info('');
        $this->warn('💡 Теперь выполните на сервере: php artisan deploy:trendagent');
    }

    /**
     * Развертывание на сервере
     */
    private function deployServer(): void
    {
        // 1. Обновление из Git
        $this->step('📥 Обновление из Git', function() {
            return $this->gitPull();
        });

        // 2. Установка зависимостей Composer
        $this->step('📦 Установка зависимостей Composer', function() {
            return $this->composerInstall();
        });

        // 3. Установка зависимостей NPM
        $this->step('📦 Установка зависимостей NPM', function() {
            return $this->npmInstall();
        });

        // 4. Сборка проекта
        if (!$this->option('skip-build')) {
            $this->step('🔨 Сборка проекта (npm run build)', function() {
                return $this->npmBuild();
            });
        } else {
            $this->report[] = ['step' => '🔨 Сборка проекта', 'status' => 'skipped', 'message' => 'Пропущено (--skip-build)'];
        }

        // 5. Выполнение миграций
        if (!$this->option('skip-migrations')) {
            $this->step('🗄️  Выполнение миграций', function() {
                return $this->runMigrations();
            });
        } else {
            $this->report[] = ['step' => '🗄️  Выполнение миграций', 'status' => 'skipped', 'message' => 'Пропущено (--skip-migrations)'];
        }

        // 6. Очистка кеша
        if (!$this->option('skip-cache')) {
            $this->step('🧹 Очистка кеша', function() {
                return $this->clearCache();
            });
        } else {
            $this->report[] = ['step' => '🧹 Очистка кеша', 'status' => 'skipped', 'message' => 'Пропущено (--skip-cache)'];
        }
    }

    /**
     * Выполнение шага с обработкой результата
     */
    private function step(string $stepName, callable $callback): void
    {
        $this->info("⏳ {$stepName}...");
        
        try {
            $result = $callback();
            
            if ($result['success']) {
                $this->report[] = [
                    'step' => $stepName,
                    'status' => 'success',
                    'message' => $result['message'] ?? 'Успешно',
                    'output' => $result['output'] ?? null
                ];
                $this->info("✅ {$stepName} - завершено");
            } else {
                $this->report[] = [
                    'step' => $stepName,
                    'status' => 'error',
                    'message' => $result['message'] ?? 'Ошибка',
                    'output' => $result['output'] ?? null
                ];
                $this->error("❌ {$stepName} - ошибка: " . ($result['message'] ?? 'Неизвестная ошибка'));
            }
        } catch (\Exception $e) {
            $this->report[] = [
                'step' => $stepName,
                'status' => 'error',
                'message' => $e->getMessage(),
                'output' => null
            ];
            $this->error("❌ {$stepName} - исключение: " . $e->getMessage());
        }
    }

    /**
     * Отправка изменений в Git
     */
    private function gitPush(): array
    {
        $basePath = base_path();
        
        // Проверяем, есть ли изменения
        $statusResult = $this->executeCommand('git status --porcelain', $basePath);
        
        if (empty(trim($statusResult['output']))) {
            return [
                'success' => true,
                'message' => 'Нет изменений для отправки',
                'output' => $statusResult['output']
            ];
        }

        // Добавляем все изменения
        $addResult = $this->executeCommand('git add -A', $basePath);
        if (!$addResult['success']) {
            return [
                'success' => false,
                'message' => 'Ошибка при добавлении файлов в git',
                'output' => $addResult['output'] . $addResult['error']
            ];
        }

        // Коммитим
        $commitResult = $this->executeCommand('git commit -m "Deploy TrendAgent project: ' . date('Y-m-d H:i:s') . '"', $basePath);
        if (!$commitResult['success']) {
            // Возможно, нет изменений для коммита
            if (str_contains($commitResult['error'], 'nothing to commit')) {
                return [
                    'success' => true,
                    'message' => 'Нет изменений для коммита',
                    'output' => $commitResult['output']
                ];
            }
        }

        // Отправляем в репозиторий
        $pushResult = $this->executeCommand('git push', $basePath);
        if (!$pushResult['success']) {
            return [
                'success' => false,
                'message' => 'Ошибка при отправке в git',
                'output' => $pushResult['output'] . $pushResult['error']
            ];
        }

        return [
            'success' => true,
            'message' => 'Изменения отправлены в git',
            'output' => $pushResult['output']
        ];
    }

    /**
     * Обновление из Git на сервере
     */
    private function gitPull(): array
    {
        $basePath = base_path();
        
        $result = $this->executeCommand('git pull', $basePath);
        
        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Ошибка при обновлении из git',
                'output' => $result['output'] . $result['error']
            ];
        }

        return [
            'success' => true,
            'message' => 'Проект обновлен из git',
            'output' => $result['output']
        ];
    }

    /**
     * Установка зависимостей Composer
     */
    private function composerInstall(): array
    {
        $basePath = base_path();
        
        $result = $this->executeCommand('composer install --no-dev --optimize-autoloader', $basePath);
        
        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Ошибка при установке зависимостей Composer',
                'output' => $result['output'] . $result['error']
            ];
        }

        return [
            'success' => true,
            'message' => 'Зависимости Composer установлены',
            'output' => $result['output']
        ];
    }

    /**
     * Установка зависимостей NPM
     */
    private function npmInstall(): array
    {
        if (!File::exists($this->projectPath . '/package.json')) {
            return [
                'success' => false,
                'message' => 'package.json не найден',
                'output' => null
            ];
        }

        $result = $this->executeCommand('npm install', $this->projectPath);
        
        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Ошибка при установке зависимостей NPM',
                'output' => $result['output'] . $result['error']
            ];
        }

        return [
            'success' => true,
            'message' => 'Зависимости NPM установлены',
            'output' => $result['output']
        ];
    }

    /**
     * Сборка проекта
     */
    private function npmBuild(): array
    {
        if (!File::exists($this->projectPath . '/package.json')) {
            return [
                'success' => false,
                'message' => 'package.json не найден',
                'output' => null
            ];
        }

        $result = $this->executeCommand('npm run build', $this->projectPath);
        
        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Ошибка при сборке проекта',
                'output' => $result['output'] . $result['error']
            ];
        }

        return [
            'success' => true,
            'message' => 'Проект успешно собран',
            'output' => $result['output']
        ];
    }

    /**
     * Выполнение команды в оболочке
     */
    private function executeCommand(string $command, string $workingDirectory): array
    {
        try {
            $result = Process::path($workingDirectory)->run($command);
            return [
                'success' => $result->successful(),
                'output' => $result->output(),
                'error' => $result->errorOutput()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Выполнение миграций
     */
    private function runMigrations(): array
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
            
            return [
                'success' => true,
                'message' => 'Миграции выполнены',
                'output' => $output
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при выполнении миграций: ' . $e->getMessage(),
                'output' => null
            ];
        }
    }

    /**
     * Очистка кеша
     */
    private function clearCache(): array
    {
        $results = [];
        
        try {
            // Очистка кеша приложения
            Artisan::call('cache:clear');
            $results[] = 'Кеш приложения очищен';
            
            // Очистка кеша конфигурации
            Artisan::call('config:clear');
            $results[] = 'Кеш конфигурации очищен';
            
            // Очистка кеша маршрутов
            Artisan::call('route:clear');
            $results[] = 'Кеш маршрутов очищен';
            
            // Очистка кеша представлений
            Artisan::call('view:clear');
            $results[] = 'Кеш представлений очищен';
            
            // Оптимизация
            Artisan::call('config:cache');
            $results[] = 'Конфигурация закэширована';
            
            Artisan::call('route:cache');
            $results[] = 'Маршруты закэшированы';
            
            return [
                'success' => true,
                'message' => 'Кеш очищен и оптимизирован',
                'output' => implode("\n", $results)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при очистке кеша: ' . $e->getMessage(),
                'output' => implode("\n", $results)
            ];
        }
    }

    /**
     * Вывод отчета о проделанной работе
     */
    private function displayReport(): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('📊 ОТЧЕТ О РАЗВЕРТЫВАНИИ');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        foreach ($this->report as $item) {
            $status = $item['status'];
            $step = $item['step'];
            $message = $item['message'];

            switch ($status) {
                case 'success':
                    $this->info("✅ {$step}");
                    $this->line("   {$message}");
                    $successCount++;
                    break;
                case 'error':
                    $this->error("❌ {$step}");
                    $this->line("   {$message}");
                    $errorCount++;
                    break;
                case 'skipped':
                    $this->comment("⏭️  {$step}");
                    $this->line("   {$message}");
                    $skippedCount++;
                    break;
            }

            if (!empty($item['output']) && $this->option('verbose')) {
                $this->line("   <fg=gray>" . substr($item['output'], 0, 200) . "...</>");
            }

            $this->newLine();
        }

        $this->info('═══════════════════════════════════════════════════════════');
        $this->info("📈 Статистика:");
        $this->info("   ✅ Успешно: {$successCount}");
        $this->info("   ❌ Ошибок: {$errorCount}");
        $this->info("   ⏭️  Пропущено: {$skippedCount}");
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        if ($errorCount > 0) {
            $this->warn('⚠️  Развертывание завершено с ошибками!');
            $this->warn('💡 Проверьте вывод выше для деталей.');
        } else {
            $this->info('🎉 Развертывание успешно завершено!');
        }
    }
}
