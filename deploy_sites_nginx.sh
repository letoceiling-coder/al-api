#!/bin/bash
# Развернуть Nginx-конфиги для auto.siteaccess.ru и p-d-a-b.neeklo.ru на сервере.
# Запускать на сервере из каталога проекта: cd /var/www/AL && bash deploy_sites_nginx.sh
# Или с локальной машины: ssh root@89.169.39.244 "cd /var/www/AL && bash deploy_sites_nginx.sh"

set -e

SITES=("auto.siteaccess.ru" "p-d-a-b.neeklo.ru")
CONFIGS=("nginx_auto_siteaccess_ru.conf" "nginx_p_d_a_b_neeklo_ru.conf")
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "=== Развёртывание конфигов Nginx для ${SITES[*]} ==="
echo "Каталог: $SCRIPT_DIR"
echo ""

for i in "${!SITES[@]}"; do
    domain="${SITES[$i]}"
    config_file="${CONFIGS[$i]}"
    target_name="$domain"
    src="$SCRIPT_DIR/$config_file"
    dst_available="/etc/nginx/sites-available/$target_name"

    if [ ! -f "$src" ]; then
        echo "⚠️  Файл не найден: $src"
        continue
    fi

    echo "--- $domain ---"
    cp "$src" "$dst_available"
    echo "  Скопирован в $dst_available"

    if [ ! -L "/etc/nginx/sites-enabled/$target_name" ] && [ ! -f "/etc/nginx/sites-enabled/$target_name" ]; then
        ln -sf "$dst_available" "/etc/nginx/sites-enabled/$target_name"
        echo "  Включён в sites-enabled"
    else
        echo "  Уже включён в sites-enabled"
    fi
    echo ""
done

echo "--- Проверка конфигурации Nginx ---"
if ! nginx -t; then
    echo "❌ Ошибка в конфигурации Nginx. Исправьте и запустите снова."
    exit 1
fi

echo "--- Перезагрузка Nginx ---"
systemctl reload nginx
echo "✅ Nginx перезагружен"
echo ""

echo "--- SSL (certbot) для доменов ---"
for domain in "${SITES[@]}"; do
    echo "Проверка/выпуск сертификата для $domain..."
    if certbot --nginx -d "$domain" --non-interactive --agree-tos --redirect --register-unsafely-without-email 2>/dev/null; then
        echo "  ✅ $domain — сертификат в порядке"
    else
        echo "  ⚠️  $domain — certbot не выполнен (если нужен email: certbot --nginx -d $domain --email your@email.ru)"
    fi
done

echo ""
echo "--- Финальная проверка Nginx ---"
nginx -t && systemctl reload nginx
echo ""
echo "=== Готово. Проверьте сайты: ==="
for domain in "${SITES[@]}"; do
    echo "  https://$domain/"
done
