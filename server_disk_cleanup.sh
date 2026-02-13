#!/bin/bash
# Безопасная очистка диска на сервере. Запуск: на сервере root: bash server_disk_cleanup.sh
# Рекомендуется сначала прочитать SERVER_DISK_ANALYSIS.md

set -e

echo "=== Текущее состояние диска ==="
df -h /
echo ""

echo "--- 1. Очистка старых бинарных логов MySQL (освободит ~9-10 ГБ) ---"
# Оставляем логи за последние 2 дня
mysql -e "PURGE BINARY LOGS BEFORE DATE(NOW() - INTERVAL 2 DAY);" 2>/dev/null && echo "OK: старые binlog удалены" || echo "Пропуск: mysql недоступен или ошибка"
echo ""

echo "--- 2. Ограничение размера systemd journal до 100 МБ ---"
journalctl --vacuum-size=100M
echo ""

echo "--- 3. Очистка кэша npm в /root ---"
npm cache clean --force 2>/dev/null && echo "OK" || rm -rf /root/.npm/_cacache 2>/dev/null; echo "npm cache очищен"
echo ""

echo "=== Состояние диска после очистки ==="
df -h /
echo ""
echo "Готово. При необходимости разверните сайты: cd /var/www/AL && bash deploy_sites_nginx.sh"
