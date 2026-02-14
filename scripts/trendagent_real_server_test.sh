#!/bin/bash
# TrendAgent Real Server Test
# Запуск на сервере: bash scripts/trendagent_real_server_test.sh
# Сервер: root@89.169.39.244, проект: /var/www/AL

set -e
PROJECT_DIR="${PROJECT_DIR:-/var/www/AL}"
BASE_URL="${BASE_URL:-https://api.siteaccess.ru/api}"
REGION="${REGION:-spb}"
ARTIFACTS_DIR="${PROJECT_DIR}/storage/logs/trendagent_real_test/$(date +%Y%m%d_%H%M%S)"

mkdir -p "$ARTIFACTS_DIR"
cd "$PROJECT_DIR"

echo "=== TrendAgent Real Server Test ==="
echo "Project: $PROJECT_DIR"
echo "Artifacts: $ARTIFACTS_DIR"
echo ""

# A1 — Pre-check
echo "--- A1 Pre-check ---"
{
  git rev-parse HEAD 2>/dev/null || echo "not a git repo"
  php -v
  composer -V 2>/dev/null || true
  df -h
} | tee "$ARTIFACTS_DIR/precheck.txt" 2>&1

php artisan optimize:clear 2>&1 | tee -a "$ARTIFACTS_DIR/precheck.txt"
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache

# A2 — Update code (optional, manual)
# git fetch origin && git reset --hard origin/main
# composer install --no-dev --optimize-autoloader

# A3 — Migrations
echo "--- A3 Migrations ---"
php artisan migrate --force 2>&1 | tee "$ARTIFACTS_DIR/migrate.txt"

# A4 — Config
export TRENDAGENT_DATA_SOURCE=db
php artisan config:cache 2>&1 | tee "$ARTIFACTS_DIR/config_cache.txt"

# A5 — Health
echo "--- A5 Health ---"
curl -sS "${BASE_URL}/trendagent/v1/health" | tee "$ARTIFACTS_DIR/health.json" | head -50
echo ""
curl -sS -X GET "${BASE_URL}/trendagent/v1/cities" | tee "$ARTIFACTS_DIR/cities.json" | head -30
echo ""

# A6 — SAFE import
echo "--- A6 SAFE Import ---"
php artisan trendagent:import-data --region="$REGION" --type=apartments --dry-run 2>&1 | tee "$ARTIFACTS_DIR/import_dry_run.txt"
php artisan trendagent:import-data --region="$REGION" --type=all --download-images=0 --batch=500 --timeout=15 --retries=2 2>&1 | tee "$ARTIFACTS_DIR/import_safe.txt"
php artisan trendagent:import-data --region="$REGION" --type=apartments --download-images=0 2>&1 | tee "$ARTIFACTS_DIR/import_idempotent.txt"

# A7 — Contract + Perf + Deactivate
echo "--- A7 FULL ---"
php artisan trendagent:contract-check --region="$REGION" --internal 2>&1 | tee "$ARTIFACTS_DIR/contract_check.txt" || true

if command -v curl >/dev/null 2>&1; then
  for i in 1 2 3 4 5 6 7 8 9 10; do
    curl -w "%{time_total}\n" -s -o /dev/null "http://127.0.0.1/api/trendagent/v1/health" 2>/dev/null || true
  done > "$ARTIFACTS_DIR/health_times.txt" 2>/dev/null || echo "Perf skip (no local curl/nginx)"
fi

php artisan trendagent:import-data --region="$REGION" --type=all --download-images=0 --deactivate-missing=1 --missing-days=7 2>&1 | tee "$ARTIFACTS_DIR/import_deactivate.txt" || true

# A8 — Images (optional)
echo "--- A8 IMAGES (optional) ---"
du -sh storage/app/public/trendagent 2>/dev/null || echo "No trendagent images dir" | tee "$ARTIFACTS_DIR/images_size.txt"
# php artisan trendagent:import-data --region="$REGION" --type=apartments --download-images=1 --batch=200 --timeout=20 --retries=2 --max-image-size-mb=25 2>&1 | tee "$ARTIFACTS_DIR/import_images.txt" || true

# A9 — GC
echo "--- A9 Images GC ---"
php artisan trendagent:images:gc --days=30 --dry-run 2>&1 | tee "$ARTIFACTS_DIR/gc_dry_run.txt"

# A10 — Final health
echo "--- A10 Final Health ---"
curl -sS "${BASE_URL}/trendagent/v1/health" | tee "$ARTIFACTS_DIR/health_final.json"

# API smoke
echo "--- API Smoke ---"
curl -sS -X POST "${BASE_URL}/trendagent/v1/apartments" \
  -H "Content-Type: application/json" \
  -d '{"city":"58c665588b6aa52311afa01b","count":5,"page":1}' | tee "$ARTIFACTS_DIR/api_apartments.json" | head -20
echo ""

curl -sS -X POST "${BASE_URL}/trendagent/v1/objects/list" \
  -H "Content-Type: application/json" \
  -d '{"object_type":"blocks","city":"58c665588b6aa52311afa01b","count":5}' | tee "$ARTIFACTS_DIR/api_objects_list.json" | head -20
echo ""

echo "=== Done. Artifacts: $ARTIFACTS_DIR ==="
