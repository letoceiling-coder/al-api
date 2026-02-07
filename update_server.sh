#!/bin/bash

# Скрипт для обновления файлов на сервере
# Выполнить на сервере: bash update_server.sh

echo "🔄 Обновление файлов на сервере..."

# Переход в директорию проекта
cd /var/www/AL || exit

echo "📥 Получение изменений из GitHub..."
git pull origin main

echo "🧹 Очистка кэша..."
php artisan route:clear
php artisan config:clear
php artisan view:clear

echo "📦 Обновление зависимостей (если нужно)..."
composer install --no-dev --optimize-autoloader

echo "🔨 Пересборка кэша..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Обновление завершено!"
