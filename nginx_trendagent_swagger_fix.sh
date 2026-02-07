#!/bin/bash

# Скрипт для добавления исключений Swagger в конфигурацию Nginx
# Использование: ./nginx_trendagent_swagger_fix.sh

CONFIG_FILE="/etc/nginx/sites-available/api.siteaccess.ru.conf"

if [ ! -f "$CONFIG_FILE" ]; then
    echo "Ошибка: Файл конфигурации не найден: $CONFIG_FILE"
    exit 1
fi

# Проверяем, есть ли уже исключения для swagger
if grep -q "location = /trendagent/swagger" "$CONFIG_FILE"; then
    echo "Исключения для Swagger уже добавлены в конфигурацию."
    exit 0
fi

# Находим блок location /trendagent
if ! grep -q "location /trendagent" "$CONFIG_FILE"; then
    echo "Ошибка: Блок location /trendagent не найден в конфигурации."
    exit 1
fi

# Создаем резервную копию
cp "$CONFIG_FILE" "${CONFIG_FILE}.backup.$(date +%Y%m%d_%H%M%S)"

# Добавляем исключения ПЕРЕД блоком location /trendagent
sed -i '/location \/trendagent {/i\
    # Исключения для Swagger - должны обрабатываться Laravel\
    location = /trendagent/swagger {\
        try_files $uri $uri/ /index.php?$query_string;\
    }\
\
    location = /trendagent/swagger.json {\
        try_files $uri $uri/ /index.php?$query_string;\
        add_header Access-Control-Allow-Origin *;\
        add_header Access-Control-Allow-Methods "GET, OPTIONS";\
        add_header Access-Control-Allow-Headers "Content-Type";\
    }\
' "$CONFIG_FILE"

# Проверяем синтаксис
if nginx -t; then
    echo "Конфигурация успешно обновлена. Перезапустите Nginx: systemctl reload nginx"
else
    echo "Ошибка в конфигурации. Восстановите из резервной копии."
    exit 1
fi
