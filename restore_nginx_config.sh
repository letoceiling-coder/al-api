#!/bin/bash
# Восстановление полной конфигурации Nginx для api.siteaccess.ru

CONFIG_FILE="/etc/nginx/sites-available/api.siteaccess.ru"
BACKUP_FILE="/etc/nginx/sites-available/api.siteaccess.ru.backup_certbot_$(date +%Y%m%d_%H%M%S)"

# Создаем резервную копию текущей конфигурации
cp "$CONFIG_FILE" "$BACKUP_FILE"

# Создаем полную конфигурацию
cat > "$CONFIG_FILE" << 'EOF'
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
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    root /var/www/AL/public;
    index index.php index.html;
    client_max_body_size 100M;

    # API endpoints TrendAgent - обрабатываются Laravel (ПЕРЕД статическими файлами)
    location = /trendagent/swagger {
        return 301 /api/trendagent/swagger;
    }
    
    location = /trendagent/swagger.json {
        return 301 /api/trendagent/swagger.json;
    }
    
    # API endpoints TrendAgent - только POST запросы к конкретным endpoints
    location ~ ^/trendagent/(authenticate|cities)$ {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # TrendAgent DB Interface - обрабатывается Laravel
    location = /trendagent/db {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Статические файлы React приложения TrendAgent
    location /trendagent/ {
        alias /var/www/AL/public/trendagent_asset/;
        try_files $uri $uri/ /trendagent/index.html;
        index index.html;
        autoindex off;
    }
    
    location = /trendagent {
        return 301 /trendagent/;
    }

    # API endpoints (Laravel)
    location /api {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Статические файлы React приложения Frontend
    location /frontend/ {
        alias /var/www/AL/public/frontend/;
        try_files $uri $uri/ /frontend/index.html;
        index index.html;
    }
    
    location /docs {
        try_files /index_docs.html =404;
    }
    
    # Все остальные пути (кроме /api, /trendagent, /frontend) - frontend приложение
    location / {
        alias /var/www/AL/public/frontend/;
        try_files $uri $uri/ /frontend/index.html;
        index index.html;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
EOF

# Проверяем конфигурацию
if nginx -t; then
    systemctl reload nginx
    echo "✅ Конфигурация Nginx восстановлена и перезагружена"
else
    echo "❌ Ошибка в конфигурации Nginx! Восстанавливаем резервную копию..."
    cp "$BACKUP_FILE" "$CONFIG_FILE"
    exit 1
fi
