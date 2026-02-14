#!/bin/bash
# TrendAgent — парсинг только СПб (быстрее, чтобы заполнить table/plans)
# Запуск: bash scripts/trendagent_parse_spb.sh
# Лучше в screen: screen -S trendagent-spb && bash scripts/trendagent_parse_spb.sh

PROJECT_DIR="${PROJECT_DIR:-/var/www/AL}"
LOG_DIR="${PROJECT_DIR}/storage/logs"
LOG_FILE="${LOG_DIR}/trendagent_parse_spb.log"

mkdir -p "$LOG_DIR"
cd "$PROJECT_DIR" || exit 1

echo "=========================================="
echo "TrendAgent SPB Parse — START"
echo "Started: $(date -Iseconds 2>/dev/null || date)"
echo "Project: $PROJECT_DIR"
echo "==========================================" | tee -a "$LOG_FILE"

php artisan trendagent:parse \
  --region=spb \
  --type=all \
  --details=true \
  --save-raw=true \
  --save-db=true \
  --images=false \
  --limit=0 \
  2>&1 | tee -a "$LOG_FILE"

echo "=========================================="
echo "TrendAgent SPB Parse — FINISH"
echo "==========================================" | tee -a "$LOG_FILE"
