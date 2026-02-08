#!/bin/bash
CONFIG="/etc/nginx/sites-available/api.siteaccess.ru"

# Проверяем наличие блока
if grep -q "location = /trendagent/db" "$CONFIG"; then
    echo "Block already exists"
    exit 0
fi

# Находим строку с location ~ ^/trendagent/(authenticate|cities)
LINE=$(grep -n "location ~ \^/trendagent/(authenticate|cities)" "$CONFIG" | cut -d: -f1)

if [ -z "$LINE" ]; then
    echo "Could not find location block for trendagent"
    exit 1
fi

# Добавляем блок после найденной строки
sed -i "${LINE}a\\
    # TrendAgent DB Interface\\
    location = /trendagent/db {\\
        try_files \$uri \$uri/ /index.php?\$query_string;\\
    }\\
" "$CONFIG"

# Проверяем и перезагружаем
if nginx -t; then
    systemctl reload nginx
    echo "Configuration updated successfully"
else
    echo "Configuration test failed"
    exit 1
fi
