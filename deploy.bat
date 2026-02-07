@echo off
echo 🚀 Начало автоматического деплоя...
echo.

ssh root@89.169.39.244 "cd /var/www/AL && git stash || true && rm -f public/trendagent/assets/index-CkKWxhNq.css public/trendagent/assets/index-CzMGkjVf.js && git fetch origin && git reset --hard origin/main && php artisan route:clear && php artisan config:clear && php artisan route:cache && php artisan config:cache && echo ✅ Деплой завершен! && php artisan route:list --path=trendagent/apartments/flat"

echo.
echo 🎉 Деплой завершен!
pause
