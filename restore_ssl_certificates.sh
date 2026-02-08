#!/bin/bash
# Скрипт для восстановления всех SSL сертификатов

set -e

echo "=== Восстановление SSL сертификатов ==="

# Список доменов для восстановления
DOMAINS=(
    "api.siteaccess.ru"
    "auto.siteaccess.ru"
    "essens-store.ru"
    "insales.siteaccess.ru"
    "neekloai.ru"
    "p-d-a-b.neeklo.ru"
    "file-to-text.siteaacess.ru"
)

# Функция для обновления сертификата
update_certificate() {
    local domain=$1
    echo ""
    echo "=== Обновление сертификата для $domain ==="
    
    # Проверяем, существует ли конфигурация Nginx
    if [ ! -f "/etc/nginx/sites-available/$domain" ]; then
        echo "⚠️  Конфигурация Nginx для $domain не найдена, пропускаем..."
        return 1
    fi
    
    # Проверяем, включена ли конфигурация
    if [ ! -L "/etc/nginx/sites-enabled/$domain" ]; then
        echo "⚠️  Конфигурация для $domain не включена, создаем симлинк..."
        ln -sf "/etc/nginx/sites-available/$domain" "/etc/nginx/sites-enabled/$domain"
    fi
    
    # Обновляем сертификат через certbot
    echo "🔄 Обновление сертификата через certbot..."
    if certbot --nginx -d "$domain" --non-interactive --agree-tos --redirect 2>&1 | tee /tmp/certbot_${domain}.log; then
        echo "✅ Сертификат для $domain успешно обновлен"
        return 0
    else
        echo "❌ Ошибка при обновлении сертификата для $domain"
        cat /tmp/certbot_${domain}.log
        return 1
    fi
}

# Обновляем сертификаты для всех доменов
SUCCESS=0
FAILED=0

for domain in "${DOMAINS[@]}"; do
    if update_certificate "$domain"; then
        ((SUCCESS++))
    else
        ((FAILED++))
    fi
done

# Проверяем конфигурацию Nginx
echo ""
echo "=== Проверка конфигурации Nginx ==="
if nginx -t; then
    echo "✅ Конфигурация Nginx корректна"
    echo "🔄 Перезагрузка Nginx..."
    systemctl reload nginx
    echo "✅ Nginx перезагружен"
else
    echo "❌ Ошибка в конфигурации Nginx!"
    exit 1
fi

# Итоговая статистика
echo ""
echo "=== Итоги ==="
echo "✅ Успешно обновлено: $SUCCESS"
echo "❌ Ошибок: $FAILED"
echo ""
echo "=== Список всех сертификатов ==="
certbot certificates
