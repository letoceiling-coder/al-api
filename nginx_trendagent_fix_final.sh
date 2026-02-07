#!/bin/bash
# Скрипт для исправления конфигурации nginx для TrendAgent

CONFIG_FILE="/etc/nginx/sites-available/api.siteaccess.ru"
BACKUP_FILE="/etc/nginx/sites-available/api.siteaccess.ru.backup5"

# Создаем резервную копию
cp "$CONFIG_FILE" "$BACKUP_FILE"

# Создаем временный файл с блоками для TrendAgent
cat > /tmp/trendagent_blocks.txt << 'EOF'
    # API endpoints TrendAgent - обрабатываются Laravel (более специфичные маршруты ПЕРЕД общим)
    location = /trendagent/swagger {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location = /trendagent/swagger.json {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ ^/trendagent/(authenticate|cities|apartments|parkings|houses|plots|commercial|houseprojects|objects|block) {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Статические файлы React приложения TrendAgent
    location /trendagent {
        alias /var/www/AL/public/trendagent;
        try_files $uri $uri/ /trendagent/index.html;
        index index.html;
    }

EOF

# Вставляем блоки перед location /
sed -i '/^    location \/ {/r /tmp/trendagent_blocks.txt' "$CONFIG_FILE"

# Исправляем права доступа
chmod 755 /var/www/AL/public/trendagent
chmod 644 /var/www/AL/public/trendagent/index.html
chown -R www-data:www-data /var/www/AL/public/trendagent

# Проверяем конфигурацию
nginx -t

# Перезагружаем nginx если тест успешен
if [ $? -eq 0 ]; then
    systemctl reload nginx
    echo "Nginx configuration updated and reloaded successfully"
else
    echo "Nginx configuration test failed. Restoring backup..."
    cp "$BACKUP_FILE" "$CONFIG_FILE"
    exit 1
fi
