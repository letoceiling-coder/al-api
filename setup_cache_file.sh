#!/bin/bash
# Скрипт для настройки File Cache вместо Redis (проще и не требует Redis)

echo "🔧 Настройка File Cache вместо Redis"
echo ""

cd /var/www/AL

# Проверяем .env файл
if [ ! -f .env ]; then
    echo "❌ Файл .env не найден!"
    exit 1
fi

echo "📝 Обновление .env файла..."

# Создаем backup
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Заменяем CACHE_DRIVER на file
if grep -q "CACHE_DRIVER" .env; then
    sed -i 's/CACHE_DRIVER=.*/CACHE_DRIVER=file/' .env
    echo "✅ CACHE_DRIVER изменен на 'file'"
else
    echo "CACHE_DRIVER=file" >> .env
    echo "✅ CACHE_DRIVER добавлен как 'file'"
fi

# Заменяем SESSION_DRIVER на file (если используется redis)
if grep -q "SESSION_DRIVER" .env; then
    sed -i 's/SESSION_DRIVER=redis/SESSION_DRIVER=file/' .env
    echo "✅ SESSION_DRIVER изменен на 'file'"
fi

# Заменяем QUEUE_CONNECTION на sync (если используется redis)
if grep -q "QUEUE_CONNECTION" .env; then
    sed -i 's/QUEUE_CONNECTION=redis/QUEUE_CONNECTION=sync/' .env
    echo "✅ QUEUE_CONNECTION изменен на 'sync'"
fi

# Проверяем права на директорию cache
echo ""
echo "🔐 Настройка прав доступа..."
mkdir -p storage/framework/cache
chown -R www-data:www-data storage/framework/cache
chmod -R 775 storage/framework/cache

# Очищаем кеш
echo ""
echo "🧹 Очистка кеша Laravel..."
php artisan config:clear
php artisan cache:clear

echo ""
echo "✅ Настройка File Cache завершена!"
echo "💡 Теперь Laravel будет использовать файловый кеш вместо Redis"
