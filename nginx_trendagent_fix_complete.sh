#!/bin/bash
# Полная исправленная конфигурация nginx для TrendAgent

CONFIG_FILE="/etc/nginx/sites-available/api.siteaccess.ru"
BACKUP_FILE="/etc/nginx/sites-available/api.siteaccess.ru.backup6"

# Создаем резервную копию
cp "$CONFIG_FILE" "$BACKUP_FILE"

# Создаем полную правильную конфигурацию
cat > "$CONFIG_FILE" << 'ENDOFFILE'
server {
    listen 80;
    listen [::]:80;
    server_name api.siteaccess.ru;
    return 301 https://api.siteaccess.ru$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name api.siteaccess.ru;

    ssl_certificate /etc/letsencrypt/live/api.siteaccess.ru/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.siteaccess.ru/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    root /var/www/AL/public;
    index index.php;
    client_max_body_size 100M;

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

    location / {
        # Главная страница - документация
        location = / {
            try_files /index_docs.html =404;
        }
        
        location /docs {
            try_files /index_docs.html =404;
        }
        
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
ENDOFFILE

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
