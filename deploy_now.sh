#!/bin/bash
# Скрипт для быстрого деплоя - выполнить на сервере

cd /var/www/AL
git pull origin main
php artisan route:clear
php artisan config:clear
php artisan config:cache
echo "✅ Деплой завершен!"
