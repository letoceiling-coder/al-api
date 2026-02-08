#!/bin/bash
# Скрипт для исправления развертывания на сервере

echo "🔧 Исправление развертывания TrendAgent на сервере"
echo ""

# 1. Перейти в директорию проекта
cd /var/www/AL

# 2. Проверить текущий статус
echo "📊 Текущий статус Git:"
git status --short

# 3. Настроить отслеживание ветки
echo ""
echo "🔗 Настройка отслеживания ветки..."
git branch --set-upstream-to=origin/main main

# 4. Обновить из Git
echo ""
echo "📥 Обновление из Git..."
git pull origin main

# 5. Проверить, что файл появился
echo ""
echo "✅ Проверка файла DeployTrendagentCommand.php:"
if [ -f "app/Console/Commands/DeployTrendagentCommand.php" ]; then
    echo "   ✅ Файл найден!"
    ls -lh app/Console/Commands/DeployTrendagentCommand.php
else
    echo "   ❌ Файл не найден!"
    echo "   Проверяю коммиты..."
    git log --oneline --all | grep -i deploy | head -5
fi

# 6. Очистить кеш Laravel
echo ""
echo "🧹 Очистка кеша Laravel..."
php artisan clear-compiled
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 7. Проверить команду
echo ""
echo "🔍 Проверка команды deploy:trendagent:"
php artisan list | grep deploy || echo "   ❌ Команда не найдена"

# 8. Если команда найдена, выполнить развертывание
if php artisan list | grep -q "deploy:trendagent"; then
    echo ""
    echo "🚀 Выполнение развертывания..."
    php artisan deploy:trendagent
else
    echo ""
    echo "⚠️  Команда не найдена. Проверяю файлы..."
    echo ""
    echo "Файлы в app/Console/Commands/:"
    ls -la app/Console/Commands/ | grep -i deploy
    echo ""
    echo "Последние коммиты:"
    git log --oneline -10
fi

# 9. Проверить права доступа
echo ""
echo "🔐 Проверка прав доступа..."
chown -R www-data:www-data public/trendagent
chmod -R 755 public/trendagent

echo ""
echo "✅ Готово!"
