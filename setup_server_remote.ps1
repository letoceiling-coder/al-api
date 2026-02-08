# PowerShell скрипт для настройки сервера через SSH
# Автоматически подключается и настраивает Redis/File Cache

$server = "root@89.169.39.244"
$remotePath = "/var/www/AL"

Write-Host "🔧 Подключение к серверу и настройка..." -ForegroundColor Cyan
Write-Host ""

# Функция для выполнения команд на сервере
function Invoke-RemoteCommand {
    param([string]$command)
    
    Write-Host "▶️  Выполняю: $command" -ForegroundColor Yellow
    
    $fullCommand = "ssh $server `"$command`""
    $result = Invoke-Expression $fullCommand
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Успешно" -ForegroundColor Green
        return $result
    } else {
        Write-Host "❌ Ошибка (код: $LASTEXITCODE)" -ForegroundColor Red
        return $null
    }
    Write-Host ""
}

Write-Host "📥 Шаг 1: Обновление кода из Git..." -ForegroundColor Cyan
Invoke-RemoteCommand "cd $remotePath && git pull"

Write-Host ""
Write-Host "📝 Шаг 2: Настройка File Cache (рекомендуется)..." -ForegroundColor Cyan

# Проверяем, есть ли скрипт на сервере
$scriptExists = Invoke-RemoteCommand "cd $remotePath && test -f setup_cache_file.sh && echo 'exists' || echo 'not_exists'"

if ($scriptExists -match "exists") {
    Write-Host "✅ Скрипт найден, запускаю..." -ForegroundColor Green
    Invoke-RemoteCommand "cd $remotePath && bash setup_cache_file.sh"
} else {
    Write-Host "⚠️  Скрипт не найден, настраиваю вручную..." -ForegroundColor Yellow
    
    # Настраиваем File Cache вручную
    Write-Host "📝 Обновляю .env файл..." -ForegroundColor Yellow
    
    # Создаем backup
    Invoke-RemoteCommand "cd $remotePath && cp .env .env.backup.`$(date +%Y%m%d_%H%M%S)"
    
    # Обновляем CACHE_DRIVER
    Invoke-RemoteCommand "cd $remotePath && if grep -q 'CACHE_DRIVER' .env; then sed -i 's/CACHE_DRIVER=.*/CACHE_DRIVER=file/' .env; else echo 'CACHE_DRIVER=file' >> .env; fi"
    
    # Обновляем SESSION_DRIVER
    Invoke-RemoteCommand "cd $remotePath && if grep -q 'SESSION_DRIVER' .env; then sed -i 's/SESSION_DRIVER=redis/SESSION_DRIVER=file/' .env; else echo 'SESSION_DRIVER=file' >> .env; fi"
    
    # Обновляем QUEUE_CONNECTION
    Invoke-RemoteCommand "cd $remotePath && if grep -q 'QUEUE_CONNECTION' .env; then sed -i 's/QUEUE_CONNECTION=redis/QUEUE_CONNECTION=sync/' .env; else echo 'QUEUE_CONNECTION=sync' >> .env; fi"
}

Write-Host ""
Write-Host "🔐 Шаг 3: Настройка прав доступа..." -ForegroundColor Cyan
Invoke-RemoteCommand "cd $remotePath && mkdir -p storage/framework/cache && chown -R www-data:www-data storage/framework/cache && chmod -R 775 storage/framework/cache"

Write-Host ""
Write-Host "🧹 Шаг 4: Очистка кеша Laravel..." -ForegroundColor Cyan
Invoke-RemoteCommand "cd $remotePath && php artisan config:clear"
Invoke-RemoteCommand "cd $remotePath && php artisan cache:clear"

Write-Host ""
Write-Host "✅ Шаг 5: Проверка конфигурации..." -ForegroundColor Cyan
$cacheConfig = Invoke-RemoteCommand "cd $remotePath && php artisan config:show cache.default"
Write-Host "Текущий драйвер кеша: $cacheConfig" -ForegroundColor Cyan

Write-Host ""
Write-Host "🎉 Настройка завершена!" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Следующие шаги:" -ForegroundColor Cyan
Write-Host "1. Проверьте работу парсера: ssh $server 'cd $remotePath && php artisan trendagent:parse --region=spb --type=complexes --limit=10'" -ForegroundColor White
Write-Host "2. Проверьте логи: ssh $server 'cd $remotePath && tail -f storage/logs/laravel.log'" -ForegroundColor White
