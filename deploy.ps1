# PowerShell скрипт для деплоя
$server = "root@89.169.39.244"
$commands = @"
cd /var/www/AL
git pull origin main
php artisan route:clear
php artisan config:clear
php artisan config:cache
echo '✅ Деплой завершен!'
php artisan route:list --path=trendagent/apartments/flat
"@

Write-Host "🚀 Начало деплоя на сервер $server..." -ForegroundColor Green
ssh $server $commands
Write-Host "🎉 Деплой завершен!" -ForegroundColor Green
