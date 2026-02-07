#!/bin/bash

# Скрипт для разрешения конфликтов и обновления на сервере

echo "🔧 Разрешение конфликтов..."

cd /var/www/AL || exit

# 1. Сохранить локальные изменения в stash (если нужно сохранить)
echo "💾 Сохранение локальных изменений..."
git stash

# 2. Удалить конфликтующие неотслеживаемые файлы
echo "🗑️  Удаление конфликтующих файлов..."
rm -f public/trendagent/assets/index-CkKWxhNq.css
rm -f public/trendagent/assets/index-CzMGkjVf.js

# 3. Получить изменения из GitHub
echo "📥 Получение изменений из GitHub..."
git pull origin main

# 4. Очистить кэш
echo "🧹 Очистка кэша..."
php artisan route:clear
php artisan config:clear
php artisan view:clear

# 5. Обновить зависимости (если нужно)
echo "📦 Обновление зависимостей..."
composer install --no-dev --optimize-autoloader

# 6. Пересобрать кэш
echo "🔨 Пересборка кэша..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Обновление завершено!"
