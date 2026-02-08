#!/bin/bash
# Скрипт для добавления location /trendagent/db в Nginx конфигурацию

CONFIG_FILE="/etc/nginx/sites-available/api.siteaccess.ru"
BACKUP_FILE="/etc/nginx/sites-available/api.siteaccess.ru.backup_db_$(date +%Y%m%d_%H%M%S)"

# Создаем резервную копию
cp "$CONFIG_FILE" "$BACKUP_FILE"

# Проверяем, есть ли уже блок для /trendagent/db
if grep -q "location = /trendagent/db" "$CONFIG_FILE"; then
    echo "Location block for /trendagent/db already exists"
    exit 0
fi

# Создаем временный файл с блоком конфигурации
cat > /tmp/trendagent_db_block.txt << 'EOF'
    # TrendAgent DB Interface - обрабатывается Laravel (ПЕРЕД общим location /trendagent/)
    location = /trendagent/db {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
EOF

# Находим строку с location ~ ^/trendagent/(authenticate|cities) и добавляем после неё
sed -i '/location ~ \^\/trendagent\/(authenticate|cities)\$ {/r /tmp/trendagent_db_block.txt' "$CONFIG_FILE"

# Проверяем конфигурацию
if nginx -t; then
    systemctl reload nginx
    echo "Nginx configuration updated successfully"
    echo "Location block for /trendagent/db has been added"
else
    echo "Nginx configuration test failed. Restoring backup..."
    cp "$BACKUP_FILE" "$CONFIG_FILE"
    exit 1
fi
