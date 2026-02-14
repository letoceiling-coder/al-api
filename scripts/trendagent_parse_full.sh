#!/bin/bash
# TrendAgent Full Parse — все регионы, все объекты, все свойства
# Запуск: bash scripts/trendagent_parse_full.sh
# Сервер: root@89.169.39.244, проект: /var/www/AL

PROJECT_DIR="${PROJECT_DIR:-/var/www/AL}"
LOG_DIR="${PROJECT_DIR}/storage/logs"
LOG_FILE="${LOG_DIR}/trendagent_parse.log"
TIMING_FILE="${LOG_DIR}/trendagent_parse_timing.json"

mkdir -p "$LOG_DIR"
cd "$PROJECT_DIR" || exit 1

START_TS=$(date +%s)
START_ISO=$(date -Iseconds 2>/dev/null || date "+%Y-%m-%dT%H:%M:%S%z")

{
  echo "=========================================="
  echo "TrendAgent Full Parse — START"
  echo "Started: $START_ISO"
  echo "Project: $PROJECT_DIR"
  echo "=========================================="
} | tee -a "$LOG_FILE"

php artisan trendagent:parse \
  --type=all \
  --details=true \
  --save-raw=true \
  --save-db=true \
  --images=false \
  --limit=0 \
  2>&1 | tee -a "$LOG_FILE"

EXIT_CODE=${PIPESTATUS[0]}
END_TS=$(date +%s)
END_ISO=$(date "+%Y-%m-%dT%H:%M:%S%z" 2>/dev/null || date)
DURATION_SEC=$((END_TS - START_TS))
DURATION_MIN=$((DURATION_SEC / 60))
DURATION_REM=$((DURATION_SEC % 60))

{
  echo ""
  echo "=========================================="
  echo "TrendAgent Full Parse — FINISH"
  echo "Ended:   $END_ISO"
  echo "Duration: ${DURATION_MIN}m ${DURATION_REM}s (${DURATION_SEC} sec)"
  echo "Exit code: $EXIT_CODE"
  echo "=========================================="
} | tee -a "$LOG_FILE"

# Сохраняем замеры в JSON для последующего анализа
echo "{
  \"started_at\": \"$START_ISO\",
  \"finished_at\": \"$END_ISO\",
  \"duration_seconds\": $DURATION_SEC,
  \"duration_human\": \"${DURATION_MIN}m ${DURATION_REM}s\",
  \"exit_code\": $EXIT_CODE
}" > "$TIMING_FILE"

exit $EXIT_CODE
