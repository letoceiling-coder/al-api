#!/bin/bash
# Диагностика доступности сайтов на сервере 89.169.39.244
# Запуск: ssh root@89.169.39.244 'bash -s' < check_sites_availability.sh
# Или: скопировать на сервер и выполнить bash check_sites_availability.sh

set -e

SITES=("auto.siteaccess.ru" "p-d-a-b.neeklo.ru")
echo "=============================================="
echo "  Диагностика сайтов: ${SITES[*]}"
echo "=============================================="
echo ""

echo "--- 1. Статус Nginx ---"
systemctl is-active nginx 2>/dev/null || echo "nginx не установлен или не как systemd service"
echo ""

echo "--- 2. Конфиги в sites-available ---"
ls -la /etc/nginx/sites-available/ 2>/dev/null || echo "Каталог не найден"
echo ""

echo "--- 3. Включённые сайты (sites-enabled) ---"
ls -la /etc/nginx/sites-enabled/ 2>/dev/null || echo "Каталог не найден"
echo ""

for site in "${SITES[@]}"; do
    echo "--- 4. Конфиг для $site ---"
    if [ -f "/etc/nginx/sites-available/$site" ]; then
        echo "Файл есть: /etc/nginx/sites-available/$site"
        if [ -L "/etc/nginx/sites-enabled/$site" ] || [ -f "/etc/nginx/sites-enabled/$site" ]; then
            echo "Включён в sites-enabled: да"
        else
            echo "Включён в sites-enabled: НЕТ (сайт не активен)"
        fi
    else
        echo "Файл НЕ найден: /etc/nginx/sites-available/$site"
    fi
    echo ""
done

echo "--- 5. Проверка синтаксиса Nginx ---"
nginx -t 2>&1 || true
echo ""

echo "--- 6. SSL сертификаты (certbot) ---"
certbot certificates 2>&1 | head -80
echo ""

for site in "${SITES[@]}"; do
    echo "--- 7. Локальный запрос к $site (curl по 80/443) ---"
    curl -sI --connect-timeout 3 -H "Host: $site" http://127.0.0.1/ 2>&1 | head -5
    curl -skI --connect-timeout 3 -H "Host: $site" https://127.0.0.1/ 2>&1 | head -5
    echo ""
done

echo "--- 8. Прослушиваемые порты (80, 443) ---"
ss -tlnp | grep -E ':80 |:443 ' 2>/dev/null || netstat -tlnp 2>/dev/null | grep -E ':80 |:443 ' || true
echo ""

echo "--- 9. DNS для доменов ---"
for site in "${SITES[@]}"; do
    echo "$site: $(dig +short A "$site" 2>/dev/null || host "$site" 2>/dev/null || echo 'dig/host недоступны')"
done
echo ""

echo "--- 10. Файрвол (ufw) ---"
ufw status 2>/dev/null || echo "ufw не установлен или не активен"
echo ""

echo "=============================================="
echo "  Конец диагностики"
echo "=============================================="
