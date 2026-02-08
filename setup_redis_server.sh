#!/bin/bash
# Скрипт для настройки Redis на сервере

echo "🔧 Настройка Redis на сервере"
echo ""

cd /var/www/AL

# Вариант 1: Установить Redis и Predis (если нужен Redis)
echo "📦 Вариант 1: Установка Redis и Predis"
echo ""

# Проверяем, установлен ли Redis
if ! command -v redis-server &> /dev/null; then
    echo "⚠️  Redis не установлен. Устанавливаем..."
    apt-get update
    apt-get install -y redis-server
    systemctl enable redis-server
    systemctl start redis-server
    echo "✅ Redis установлен и запущен"
else
    echo "✅ Redis уже установлен"
fi

# Устанавливаем Predis через Composer
echo ""
echo "📦 Установка Predis через Composer..."
composer require predis/predis --no-interaction

# Проверяем .env файл
echo ""
echo "📝 Проверка конфигурации .env..."

if [ -f .env ]; then
    # Проверяем, есть ли CACHE_DRIVER
    if grep -q "CACHE_DRIVER" .env; then
        echo "✅ CACHE_DRIVER найден в .env"
        grep "CACHE_DRIVER" .env
    else
        echo "⚠️  CACHE_DRIVER не найден, добавляем..."
        echo "CACHE_DRIVER=redis" >> .env
    fi
    
    # Проверяем REDIS настройки
    if ! grep -q "REDIS_HOST" .env; then
        echo "REDIS_HOST=127.0.0.1" >> .env
        echo "REDIS_PASSWORD=null" >> .env
        echo "REDIS_PORT=6379" >> .env
        echo "✅ REDIS настройки добавлены"
    fi
else
    echo "❌ Файл .env не найден!"
    exit 1
fi

# Проверяем работу Redis
echo ""
echo "🔍 Проверка работы Redis..."
if redis-cli ping &> /dev/null; then
    echo "✅ Redis работает"
    redis-cli ping
else
    echo "❌ Redis не отвечает. Запускаем..."
    systemctl start redis-server
    sleep 2
    if redis-cli ping &> /dev/null; then
        echo "✅ Redis запущен"
    else
        echo "❌ Не удалось запустить Redis"
    fi
fi

# Очищаем кеш Laravel
echo ""
echo "🧹 Очистка кеша Laravel..."
php artisan config:clear
php artisan cache:clear

echo ""
echo "✅ Настройка Redis завершена!"
