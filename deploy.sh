#!/bin/bash

# Автоматический скрипт деплоя на сервер
# Использование: bash deploy.sh

SERVER="root@89.169.39.244"
PROJECT_DIR="/var/www/AL"

echo "🚀 Начало автоматического деплоя..."
echo "📡 Подключение к серверу: $SERVER"
echo ""

# Выполнение команд на сервере через SSH
ssh $SERVER << 'ENDSSH'
    set -e  # Остановка при ошибке
    
    echo "✅ Подключено к серверу"
    echo "📁 Переход в директорию проекта..."
    cd /var/www/AL || exit 1
    
    echo "💾 Сохранение локальных изменений (если есть)..."
    git stash || true
    
    echo "🗑️  Удаление конфликтующих файлов..."
    rm -f public/trendagent/assets/index-CkKWxhNq.css
    rm -f public/trendagent/assets/index-CzMGkjVf.js
    
    echo "📥 Получение изменений из GitHub..."
    git fetch origin
    git reset --hard origin/main
    
    echo "🧹 Очистка кэша..."
    php artisan route:clear
    php artisan config:clear
    php artisan view:clear || true
    
    echo "📦 Обновление зависимостей..."
    composer install --no-dev --optimize-autoloader --quiet
    
    echo "🔨 Пересборка кэша..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache || true
    
    echo "✅ Проверка роутов..."
    php artisan route:list --path=trendagent/apartments/flat | head -5
    
    echo ""
    echo "✅ Деплой завершен успешно!"
    echo "📋 Статус:"
    git log -1 --oneline
ENDSSH

echo ""
echo "🎉 Деплой завершен!"
