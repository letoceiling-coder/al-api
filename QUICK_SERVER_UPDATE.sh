#!/bin/bash
# Быстрое обновление парсера на сервере

cd /var/www/AL

echo "🔄 Обновление кода из Git..."
git pull

echo "🧹 Очистка кеша Laravel..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo "✅ Обновление завершено!"
echo ""
echo "Теперь можно запустить парсер:"
echo "php artisan trendagent:parse --region=spb --type=all --details --save-raw"
