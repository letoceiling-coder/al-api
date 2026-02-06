#!/bin/bash
# Скрипт для добавления блока /trendagent в конфигурацию Nginx

CONFIG_FILE="/etc/nginx/sites-available/api.siteaccess.ru"

# Проверяем, есть ли уже блок
if grep -q "location /trendagent" "$CONFIG_FILE"; then
    echo "Block /trendagent already exists"
    exit 0
fi

# Создаем временный файл с блоком
cat > /tmp/trendagent_block.txt << 'EOF'
    # TrendAgent React app
    location /trendagent {
        alias /var/www/AL/public/trendagent;
        try_files $uri $uri/ /trendagent/index.html;
        
        # Кэширование статических файлов
        location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
            expires 1y;
            add_header Cache-Control "public, immutable";
        }
        
        # Отключить кэширование для index.html
        location = /trendagent/index.html {
            add_header Cache-Control "no-cache, no-store, must-revalidate";
            add_header Pragma "no-cache";
            add_header Expires "0";
        }
    }

EOF

# Находим строку "# Swagger documentation" и вставляем перед ней
sed -i '/# Swagger documentation/r /tmp/trendagent_block.txt' "$CONFIG_FILE"

echo "Block /trendagent added successfully"
